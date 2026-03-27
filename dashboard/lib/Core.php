<?php
// (A) ERROR HANDLING
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// (B) LOCAL DATABASE SETTINGS (XAMPP)
define("DB_HOST", "127.0.0.1");
define("DB_PORT", 3306);  // ✅ LOCAL PORT
define("DB_NAME", "trade");
define("DB_CHARSET", "utf8");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("FINNHUB_API_KEY", getenv("FINNHUB_API_KEY") ?: ($_ENV["FINNHUB_API_KEY"] ?? ($_SERVER["FINNHUB_API_KEY"] ?? "")));
define("NEWS_CACHE_TTL_SECONDS", 1800);

// (C) CONNECT
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// (D) URL (LOCAL)
define("URL_HOST", "http://localhost/trade_se306/dashboard/");
define("URL_PUBLIC", URL_HOST . "public/");

// (E) FILE PATHS
define("PATH_LIB", __DIR__ . DIRECTORY_SEPARATOR);
define("PATH_BASE", dirname(PATH_LIB) . DIRECTORY_SEPARATOR);

// (F) SESSION
session_start();
