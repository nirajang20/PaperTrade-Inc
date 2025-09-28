<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Define tickers and labels
$symbols = [
  "amzn.us" => "AMZN",
  "aapl.us" => "AAPL",
  "tsla.us" => "TSLA",
  "amd.us"  => "AMD",
  "jpm.us"  => "JPM",
  "^spx"    => "S&P 500",
  "meta.us" => "META",
  "msft.us" => "MSFT"
];

// Build single URL with all tickers
$query = implode('+', array_keys($symbols));
$url = "https://stooq.com/q/?s=" . urlencode($query);

// Fetch HTML once
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; StockScraper/1.0)'
]);
$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$html) {
    echo json_encode(["error" => "Failed to fetch page"]);
    exit;
}

// Parse with DOM
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($doc);

$results = [];
foreach ($symbols as $stooqSym => $label) {
    $id = "aq_" . strtolower($stooqSym) . "_c4";
    $node = $xpath->query("//*[@id='$id']")->item(0);

    if ($node) {
        $val = trim($node->textContent);
        $val = str_replace(',', '', $val); // remove thousands separators
        $price = is_numeric($val) ? round((float)$val, 2) : null;
        $results[] = ["symbol" => $label, "price" => $price];
    } else {
        $results[] = ["symbol" => $label, "error" => "Not found"];
    }
}

echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
