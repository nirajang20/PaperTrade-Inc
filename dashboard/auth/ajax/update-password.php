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
| VALIDATE REQUEST + SESSION
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse("error", "Invalid request method.");
}

if (empty($_SESSION["reset_verified"]) || empty($_SESSION["reset_email"])) {
    jsonResponse("error", "Reset session expired. Please start again.");
}

$email = (string)$_SESSION["reset_email"];
$password = (string)($_POST["password"] ?? "");
$confirm  = (string)($_POST["confirm"] ?? "");

/*
|--------------------------------------------------------------------------
| VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/
if ($password === "" || $confirm === "") {
    jsonResponse("error", "Please complete both password fields.");
}

if ($password !== $confirm) {
    jsonResponse("error", "Passwords do not match.");
}

$rules = [];

if (strlen($password) < 8) {
    $rules[] = "be at least 8 characters";
}
if (!preg_match('/[A-Z]/', $password)) {
    $rules[] = "include an uppercase letter";
}
if (!preg_match('/[a-z]/', $password)) {
    $rules[] = "include a lowercase letter";
}
if (!preg_match('/\d/', $password)) {
    $rules[] = "include a number";
}
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $rules[] = "include a symbol";
}

if (!empty($rules)) {
    jsonResponse("error", "Password must " . implode(", ", $rules) . ".");
}

/*
|--------------------------------------------------------------------------
| FETCH USER
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT user_id, user_password, status
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
    jsonResponse("error", "Account could not be found.");
}

if ((int)($user["status"] ?? 0) !== 1) {
    jsonResponse("error", "This account is not active.");
}

/*
|--------------------------------------------------------------------------
| PREVENT REUSING CURRENT PASSWORD
|--------------------------------------------------------------------------
*/
$currentHash = (string)($user["user_password"] ?? "");
if ($currentHash !== "" && password_verify($password, $currentHash)) {
    jsonResponse("error", "Your new password cannot be the same as your current password.");
}

/*
|--------------------------------------------------------------------------
| UPDATE PASSWORD
|--------------------------------------------------------------------------
*/
$newHash = password_hash($password, PASSWORD_DEFAULT);

$hasLastUpdated = false;
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_updated'");
if ($checkColumn && mysqli_num_rows($checkColumn) > 0) {
    $hasLastUpdated = true;
}

if ($hasLastUpdated) {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET user_password = ?, last_updated = NOW()
         WHERE user_email = ?"
    );
} else {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET user_password = ?
         WHERE user_email = ?"
    );
}

if (!$stmt) {
    jsonResponse("error", "Database error while preparing password update.");
}

mysqli_stmt_bind_param($stmt, "ss", $newHash, $email);

if (!mysqli_stmt_execute($stmt)) {
    $dbError = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    jsonResponse("error", "Unable to update password. " . $dbError);
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| CLEAN RESET SESSION
|--------------------------------------------------------------------------
*/
unset($_SESSION["reset_email"]);
unset($_SESSION["reset_verified"]);
unset($_SESSION["reset_otp"]);
unset($_SESSION["reset_otp_id"]);
unset($_SESSION["reset_step"]);

session_regenerate_id(true);

jsonResponse("success", "Your password has been updated successfully.");