<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP WORKING<br>";

$host = "localhost";
$user = "nirajan";
$password = "papertrade_trade";
$db = "papertrade_trade";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("DB ERROR: " . mysqli_connect_error());
}

echo "DB CONNECTION OK";