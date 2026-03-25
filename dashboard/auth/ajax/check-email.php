<?php
declare(strict_types=1);

require __DIR__ . "/../../lib/Core.php";

header("Content-Type: application/json");

$email = trim($_POST["email"] ?? "");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Please enter a valid email address."
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT user_id FROM users WHERE user_email = ? LIMIT 1"
);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error."
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($user) {
    echo json_encode([
        "status" => "exists"
    ]);
    exit;
}

echo json_encode([
    "status" => "new"
]);