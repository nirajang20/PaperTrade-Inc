
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle) {
    return $needle === '' || strpos($haystack, $needle) === 0;
  }
}

function bad($msg, $code=400){ http_response_code($code); echo json_encode(["error"=>$msg]); exit; }

function date_iso($s){
  $d = DateTime::createFromFormat('Y-m-d', $s);
  if(!$d) bad("Dates must be YYYY-MM-DD");
  $d->setTime(0,0,0);
  return $d;
}

$symbol = isset($_GET['symbol']) ? trim($_GET['symbol']) : '';
$start  = $_GET['start'] ?? '';
$end    = $_GET['end'] ?? '';

if($symbol==='' || $start==='' || $end==='') bad("Required query params: symbol, start, end");

$startD = date_iso($start);
$endD   = date_iso($end);
if($startD > $endD) bad("start must be <= end");

function http_get($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'StockHistory/1.0'
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return ($code===200) ? $body : null;
}

function fetch_stooq_csv($sym){
  $candidates = [];
  if (strpos($sym, '.') === false) $candidates[] = strtolower($sym).'.us';
  $candidates[] = strtolower($sym);
  foreach($candidates as $s){
    $url = "https://stooq.com/q/d/l/?s={$s}&i=d";
    $csv = http_get($url);
    if ($csv && str_starts_with($csv, "Date,Open,High,Low,Close,Volume")) return $csv;
  }
  return null;
}

$csv = fetch_stooq_csv($symbol);
if(!$csv){
  echo json_encode([
    "symbol"=>strtoupper($symbol), "start"=>$start, "end"=>$end, "data"=>[],
    "note"=>"No data found (invalid symbol or source unavailable)."
  ]);
  exit;
}

$lines = explode("\n", trim($csv));
array_shift($lines); // drop header
$data = [];
foreach($lines as $line){
  if($line==='') continue;
  [$d,$o,$h,$l,$c,$v] = str_getcsv($line);
  $dObj = DateTime::createFromFormat('Y-m-d',$d);
  if(!$dObj) continue;
  $dObj->setTime(0,0,0);
  if($dObj < $startD || $dObj > $endD) continue;

  $data[] = [
    "date"   => $dObj->format('Y-m-d'),
    "open"   => is_numeric($o)? (float)$o : null,
    "high"   => is_numeric($h)? (float)$h : null,
    "low"    => is_numeric($l)? (float)$l : null,
    "close"  => is_numeric($c)? (float)$c : null,
    "volume" => is_numeric($v)? (int)$v   : null
  ];
}

echo json_encode([
  "symbol"=>strtoupper($symbol),
  "start"=>$start,
  "end"=>$end,
  "count"=>count($data),
  "data"=>$data
], JSON_UNESCAPED_SLASHES);
