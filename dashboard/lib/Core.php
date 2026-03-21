<?php
// (A) ERROR HANDLING
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// (B) DATABASE SETTINGS
define("DB_HOST", "localhost"); // must be 127.0.0.1, not localhost
define("DB_PORT", 3307);        // tunnel port
define("DB_NAME", "papertrade_trade");
define("DB_CHARSET", "utf8");
define("DB_USER", "papertrade_trade");
define("DB_PASSWORD", "7Dz)kI9-MCroe/=u");


// (C) CONNECT
$conn = mysqli_connect("127.0.0.1", "papertrade_trade", "7Dz)kI9-MCroe/=u", "papertrade_trade", 3307);
// (D) URL
define("URL_HOST", "http://localhost/trade/dashboard/");
define("URL_PUBLIC", URL_HOST . "public/");

// (E) FILE PATHS
define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

// (F) SESSION
session_start();
