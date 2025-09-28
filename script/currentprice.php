<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function bad($msg, $code=400){ 
  http_response_code($code); 
  echo json_encode(["error"=>$msg]); 
  exit; 
}

if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle) {
    return $needle === '' || strpos($haystack, $needle) === 0;
  }
}

function http_get($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'StockPrice/1.0'
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return ($code===200) ? $body : null;
}

function fetch_stooq_quote($sym){
  $candidates = [];
  if (strpos($sym, '.') === false) $candidates[] = strtolower($sym).'.us';
  $candidates[] = strtolower($sym);

  foreach($candidates as $s){
    $url = "https://stooq.com/q/l/?s={$s}&f=sd2t2ohlcv&h&e=csv";
    $csv = http_get($url);
    if ($csv && str_starts_with($csv, "Symbol,")) {
      $lines = explode("\n", trim($csv));
      if (count($lines) > 1) {
        $row = str_getcsv($lines[1]);
        if (!empty($row[6]) && is_numeric($row[6])) {
          return [
            "symbol" => strtoupper($sym),
            "price" => round((float)$row[6], 2),
            "date"   => $row[1] ?? null,
            "time"   => $row[2] ?? null
          ];
        }
      }
    }
  }
  return null;
}

// ----------------------
// MAIN ENTRY
// ----------------------
if (!isset($_GET['stock'])) bad("Missing ?stock parameter");

$stocks = explode(",", $_GET['stock']); // multiple symbols allowed
$result = [];
foreach ($stocks as $s) {
  $s = trim($s);
  if ($s === '') continue;
  $quote = fetch_stooq_quote($s);
  if ($quote) $result[] = $quote;
  else $result[] = ["symbol" => strtoupper($s), "error" => "No data"];
}

echo json_encode($result, JSON_UNESCAPED_SLASHES);
