<?php
declare(strict_types=1);

require __DIR__ . "/../../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function jsonResponse(string $status, string $message): void {
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDATE SESSION + INPUT
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse("error", "Invalid request method.");
}

if (empty($_SESSION["reset_email"])) {
    jsonResponse("error", "Reset session expired. Please start again.");
}

$email = (string)$_SESSION["reset_email"];
$otpInput = trim($_POST["otp"] ?? "");
$otpPurpose = "reset_password";

if (!preg_match('/^\d{6}$/', $otpInput)) {
    jsonResponse("error", "Please enter a valid 6-digit OTP.");
}

/*
|--------------------------------------------------------------------------
| FETCH LATEST ACTIVE RESET OTP
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, otp_hash, attempts, expires_at
     FROM otp_tokens
     WHERE email = ? AND purpose = ? AND is_used = 0
     ORDER BY id DESC
     LIMIT 1"
);

if (!$stmt) {
    jsonResponse("error", "Database error while verifying OTP.");
}

mysqli_stmt_bind_param($stmt, "ss", $email, $otpPurpose);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    jsonResponse("error", "No active OTP found. Please request a new OTP.");
}

$otpId = (int)$row["id"];
$attempts = (int)$row["attempts"];
$expiresAt = $row["expires_at"] ?? null;
$storedHash = $row["otp_hash"] ?? "";

/*
|--------------------------------------------------------------------------
| CHECK ATTEMPTS / EXPIRY
|--------------------------------------------------------------------------
*/
if ($attempts >= 5) {
    jsonResponse("error", "This OTP has been blocked after too many attempts. Please request a new OTP.");
}

if (!$expiresAt || strtotime($expiresAt) < time()) {
    $markExpired = mysqli_prepare(
        $conn,
        "UPDATE otp_tokens SET is_used = 1, used_at = NOW() WHERE id = ?"
    );
    if ($markExpired) {
        mysqli_stmt_bind_param($markExpired, "i", $otpId);
        mysqli_stmt_execute($markExpired);
        mysqli_stmt_close($markExpired);
    }

    jsonResponse("error", "This OTP has expired. Please request a new OTP.");
}

/*
|--------------------------------------------------------------------------
| VERIFY OTP HASH
|--------------------------------------------------------------------------
*/
$inputHash = hash("sha256", $otpInput);

if (!hash_equals($storedHash, $inputHash)) {
    $increase = mysqli_prepare(
        $conn,
        "UPDATE otp_tokens SET attempts = attempts + 1 WHERE id = ?"
    );

    if ($increase) {
        mysqli_stmt_bind_param($increase, "i", $otpId);
        mysqli_stmt_execute($increase);
        mysqli_stmt_close($increase);
    }

    jsonResponse("error", "Incorrect OTP. Please try again.");
}

/*
|--------------------------------------------------------------------------
| MARK OTP USED + AUTHORIZE RESET
|--------------------------------------------------------------------------
*/
$markUsed = mysqli_prepare(
    $conn,
    "UPDATE otp_tokens SET is_used = 1, used_at = NOW() WHERE id = ?"
);

if (!$markUsed) {
    jsonResponse("error", "Database error while finalizing OTP verification.");
}

mysqli_stmt_bind_param($markUsed, "i", $otpId);

if (!mysqli_stmt_execute($markUsed)) {
    mysqli_stmt_close($markUsed);
    jsonResponse("error", "Unable to finalize OTP verification.");
}

mysqli_stmt_close($markUsed);

$_SESSION["reset_verified"] = true;
$_SESSION["reset_otp_id"] = $otpId;
$_SESSION["reset_step"] = 2;

jsonResponse("success", "OTP verified successfully.");