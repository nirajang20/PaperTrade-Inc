<?php
// (A) ERROR HANDLING - CHANGE TO YOUR OWN
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);
// ini_set("log_errors", 1);
// ini_set("error_log", "PATH/error.log");

// (B) DATABASE SETTINGS - CHANGE TO YOUR OWN
define("DB_HOST", "127.0.0.1");
define("DB_PORT", 3307); // use local forwarded port
define("DB_NAME", "trade");
define("DB_CHARSET", "utf8");
define("DB_USER", "trade_user");
define("DB_PASSWORD", "trade_user");

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// (C) URL
define("URL_HOST", "http://localhost/trade/dashboard/"); // CHANGE TO YOUR OWN
define("URL_PUBLIC", URL_HOST . "public/");

// (D) FILE PATHS
define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

// (E) START SESSION
session_start();
