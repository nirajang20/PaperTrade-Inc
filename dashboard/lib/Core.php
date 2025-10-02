<?php

// (A) ERROR HANDLING
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// (B) DATABASE SETTINGS
define("DB_HOST", "localhost"); // must be 127.0.0.1, not localhost
define("DB_PORT", 3306);        // tunnel port
define("DB_NAME", "trade");
define("DB_CHARSET", "utf8");
define("DB_USER", "trade_user");
define("DB_PASSWORD", "trade_user");


// (C) CONNECT
$conn = mysqli_connect("db_host", "DB_USER", "DB_PASSWORD", "DB_NAME", DB_PORT);

// (D) URL
define("URL_HOST", "http://74.208.158.229/dashboard/");
define("URL_PUBLIC", URL_HOST . "public/");

// (E) FILE PATHS
define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

// (F) SESSION
session_start();

//ssh -fN -L 3307:127.0.0.1:3306 root@74.208.158.229 => for ssh tunnel mysql