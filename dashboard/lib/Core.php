<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

define("DB_HOST", "127.0.0.1");
define("DB_PORT", 3306);
define("DB_NAME", "prabin");
define("DB_CHARSET", "utf8");
define("DB_USER", "root");
define("DB_PASSWORD", "");

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

define("URL_HOST", "http://localhost/PaperTrade-Inc/dashboard/");
define("URL_PUBLIC", URL_HOST . "public/");

define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

$API_URL = "http://localhost/PaperTrade-Inc/";

session_start();
// ---------------------------------------

// <?php
// // (A) ERROR HANDLING
// error_reporting(E_ALL & ~E_NOTICE);
// ini_set("display_errors", 1);

// // (B) DATABASE SETTINGS
// define("DB_HOST", "127.0.0.1"); // must be 127.0.0.1, not localhost
// define("DB_PORT", 3307);        // tunnel port
// define("DB_NAME", "trade");
// define("DB_CHARSET", "utf8");
// define("DB_USER", "trade_user");
// define("DB_PASSWORD", "trade_user");


// // (C) CONNECT
// $conn = mysqli_connect("127.0.0.1", "papertrade_trade", "7Dz)kI9-MCroe/=u", "papertrade_trade", 3307);

// // (D) URL
// define("URL_HOST", "http://localhost/trade/dashboard/");
// define("URL_PUBLIC", URL_HOST . "public/");

// // (E) FILE PATHS
// define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
// define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

// // (F) SESSION
// session_start();

// // ---------------------------------------