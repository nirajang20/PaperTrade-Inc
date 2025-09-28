<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function fetch_html($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200) ? $html : null;
}

function parse_google_price($html) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);
    $nodes = $xpath->query("//div[contains(@class,'YMlKec') and contains(@class,'fxKbKc')]");
    if ($nodes->length > 0) {
        $text = trim($nodes->item(0)->textContent);
        $clean = str_replace(['$', ','], ['', ''], $text);
        return is_numeric($clean) ? (float)$clean : null;
    }
    return null;
}

// Your 8 symbols (Google Finance format)
$symbols = [
    "AMZN:NASDAQ",
    "AAPL:NASDAQ",
    "TSLA:NASDAQ",
    "AMD:NASDAQ",
    "JPM:NYSE",
    "META:NASDAQ",
    "MSFT:NASDAQ",
    "GOOGL:NASDAQ"
];

$results = [];
foreach ($symbols as $sym) {
    $url = "https://www.google.com/finance/quote/" . urlencode($sym);
    $html = fetch_html($url);
    if (!$html) {
        $results[] = ["symbol" => $sym, "error" => "Fetch failed"];
        continue;
    }
    $price = parse_google_price($html);
    if ($price !== null) {
        $results[] = ["symbol" => $sym, "price" => $price];
    } else {
        $results[] = ["symbol" => $sym, "error" => "Price not found"];
    }
}

echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
