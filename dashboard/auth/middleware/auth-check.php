<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    empty($_SESSION["user"]) ||
    !is_array($_SESSION["user"]) ||
    empty($_SESSION["user"]["user_id"])
) {
    header("Location: /trade/dashboard/auth/login.php");
    exit;
}