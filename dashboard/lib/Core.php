<?php
// (A) ERROR HANDLING
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// (B) DATABASE SETTINGS
define("DB_HOST", "127.0.0.1"); // use TCP for the SSH tunnel
define("DB_PORT", 3307);        // local forwarded port
define("DB_NAME", "papertrade_trade");
define("DB_CHARSET", "utf8");
define("DB_USER", "papertrade_trade");
define("DB_PASSWORD", "7Dz)kI9-MCroe/=u");

// (C) CONNECT
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$finhub_key = "";
if ($conn instanceof mysqli) {
    $finhub_keys = mysqli_query($conn, "SELECT finhub_api FROM settings WHERE id = 1 LIMIT 1");
    if ($finhub_keys instanceof mysqli_result) {
        $finhub_key = mysqli_fetch_assoc($finhub_keys)["finhub_api"] ?? "";
    }
}

define("FINNHUB_API_KEY", $finhub_key);
define("NEWS_CACHE_TTL_SECONDS", 1800);

// (D) URL
define("URL_HOST", "http://localhost/trade/dashboard/");
define("URL_PUBLIC", URL_HOST . "public/");

// (E) FILE PATHS
define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

// (F) SESSION
session_start();

// ssh -L 3307:localhost:3306 root@74.208.158.229
