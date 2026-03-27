<?php
declare(strict_types=1);

require __DIR__ . "/../../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_POST["req"])) {
    exit("INVALID REQUEST");
}

switch ($_POST["req"]) {

    // (A) LOGIN
    case "in":

        if (isset($_SESSION["user"]) && is_array($_SESSION["user"])) {
            exit("OK");
        }

        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            exit("Please enter a valid email address.");
        }

        if ($password === "") {
            exit("Please enter your password.");
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, user_email, user_name, user_password, avatar, user_balance, role, last_login, status
             FROM users
             WHERE user_email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            exit("Database error while preparing login.");
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user || !password_verify($password, $user["user_password"])) {
            exit("Invalid email or password.");
        }

        if ((int)($user["status"] ?? 0) !== 1) {
            exit("Your account is not active.");
        }

        session_regenerate_id(true);

        $_SESSION["user"] = array(
            "user_id"      => (int)$user["user_id"],
            "user_email"   => $user["user_email"],
            "user_name"    => $user["user_name"],
            "avatar"       => $user["avatar"] ?? "",
            "user_balance" => $user["user_balance"] ?? 0,
            "role"         => $user["role"] ?? "user",
            "last_login"   => $user["last_login"] ?? null,
            "status"       => $user["status"] ?? 1
        );

        $_SESSION["active_email"] = $user["user_email"];
        $_SESSION["welcome_back"] = true;

        $updateLogin = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?");
        if ($updateLogin) {
            $uid = (int)$user["user_id"];
            mysqli_stmt_bind_param($updateLogin, "i", $uid);
            mysqli_stmt_execute($updateLogin);
            mysqli_stmt_close($updateLogin);
        }

        exit("OK");


    // (B) LOGOUT
    case "out":
        unset($_SESSION["user"]);
        unset($_SESSION["active_email"]);
        unset($_SESSION["welcome_back"]);
        exit("OK");


    // (C) SESSION STATUS
    case "check":
        header("Content-Type: application/json");
        if (isset($_SESSION["user"]) && is_array($_SESSION["user"])) {
            echo json_encode([
                "logged_in" => true,
                "user" => $_SESSION["user"]
            ]);
        } else {
            echo json_encode([
                "logged_in" => false
            ]);
        }
        exit;


    // (D) INVALID REQUEST
    default:
        exit("INVALID REQUEST");
}