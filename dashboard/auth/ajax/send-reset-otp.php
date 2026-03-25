<?php
declare(strict_types=1);

require __DIR__ . "/../../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/
$otpEndpoint      = "https://send-email.paper-trade.org/send_otp.php";
$otpPurpose       = "reset_password";
$otpExpiryMinutes = 10;
$resendCooldown   = 60; // seconds

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

function getClientIp(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return substr($_SERVER['HTTP_CF_CONNECTING_IP'], 0, 45);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return substr(trim($parts[0]), 0, 45);
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return substr($_SERVER['REMOTE_ADDR'], 0, 45);
    }
    return '0.0.0.0';
}

function sendPostRequest(string $url, array $params): array {
    if (!function_exists('curl_init')) {
        return [
            'ok'   => false,
            'http' => 0,
            'body' => '',
            'err'  => 'cURL is not enabled on this server.'
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'ok'   => ($err === '' && $http >= 200 && $http < 300),
        'http' => $http,
        'body' => $body ?: '',
        'err'  => $err
    ];
}

/*
|--------------------------------------------------------------------------
| VALIDATE INPUT
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse("error", "Invalid request method.");
}

$email = trim($_POST["email"] ?? "");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse("error", "Please enter a valid email address.");
}

if (preg_match('/[\r\n\t]/', $email)) {
    jsonResponse("error", "Invalid email address.");
}

$ipAddress = getClientIp();

/*
|--------------------------------------------------------------------------
| CHECK USER EXISTS
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT user_id, user_email, status
     FROM users
     WHERE user_email = ?
     LIMIT 1"
);

if (!$stmt) {
    jsonResponse("error", "Database error while checking account.");
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    jsonResponse("error", "No account found with that email address.");
}

if ((int)($user["status"] ?? 0) !== 1) {
    jsonResponse("error", "This account is not active.");
}

/*
|--------------------------------------------------------------------------
| RESEND COOLDOWN
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, created_at
     FROM otp_tokens
     WHERE email = ? AND purpose = ? AND is_used = 0
     ORDER BY id DESC
     LIMIT 1"
);

if (!$stmt) {
    jsonResponse("error", "Database error while checking OTP status.");
}

mysqli_stmt_bind_param($stmt, "ss", $email, $otpPurpose);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$latestOtp = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($latestOtp && !empty($latestOtp["created_at"])) {
    $lastCreated = strtotime($latestOtp["created_at"]);
    if ($lastCreated && (time() - $lastCreated) < $resendCooldown) {
        jsonResponse("error", "Please wait before requesting another OTP.");
    }
}

/*
|--------------------------------------------------------------------------
| INVALIDATE OLD UNUSED OTPs
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "UPDATE otp_tokens
     SET is_used = 1, used_at = NOW()
     WHERE email = ? AND purpose = ? AND is_used = 0"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $email, $otpPurpose);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/*
|--------------------------------------------------------------------------
| CREATE NEW OTP
|--------------------------------------------------------------------------
*/
$otp = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
$otpHash = hash("sha256", $otp);
$expiresAt = date("Y-m-d H:i:s", strtotime("+{$otpExpiryMinutes} minutes"));

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO otp_tokens
     (email, otp_hash, purpose, ip_address, is_used, attempts, expires_at)
     VALUES (?, ?, ?, ?, 0, 0, ?)"
);

if (!$stmt) {
    jsonResponse("error", "Could not create OTP record.");
}

mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $email,
    $otpHash,
    $otpPurpose,
    $ipAddress,
    $expiresAt
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    jsonResponse("error", "Could not create OTP record.");
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| SEND OTP EMAIL
|--------------------------------------------------------------------------
*/
$sendResult = sendPostRequest($otpEndpoint, [
    "toemail" => $email,
    "otp"     => $otp
]);

$responseJson = json_decode($sendResult["body"], true);

if (
    !$sendResult["ok"] ||
    !is_array($responseJson) ||
    empty($responseJson["status"]) ||
    $responseJson["status"] !== "success"
) {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE otp_tokens
         SET is_used = 1, used_at = NOW()
         WHERE email = ? AND purpose = ? AND is_used = 0"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $email, $otpPurpose);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $apiMessage = is_array($responseJson) && isset($responseJson["message"])
        ? $responseJson["message"]
        : "Could not send OTP email right now.";

    jsonResponse("error", "OTP send failed: " . $apiMessage);
}

/*
|--------------------------------------------------------------------------
| SAVE RESET SESSION
|--------------------------------------------------------------------------
*/
$_SESSION["reset_email"] = $email;
$_SESSION["reset_verified"] = false;
$_SESSION["reset_step"] = 1;

jsonResponse("success", "A reset OTP has been sent to your email.");