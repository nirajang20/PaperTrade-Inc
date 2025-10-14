<?php
require "lib/Core.php";

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$__ISADMIN = isset($_SESSION["user"]);
if (!isset($_ISADMIN)) {
  $_ISADMIN = $__ISADMIN;
}
if (!$_ISADMIN) {
  http_response_code(401);
  echo json_encode([
    "status" => "error",
    "message" => "Authentication required."
  ]);
  exit;
}

require "lib/fetch.php";

if (empty($active_user) && !empty($_SESSION["user"]) && is_array($_SESSION["user"])) {
  $active_user = $_SESSION["user"];
}

if (empty($active_user) || !isset($active_user["user_id"])) {
  http_response_code(401);
  echo json_encode([
    "status" => "error",
    "message" => "User session not found."
  ]);
  exit;
}

if (!$conn) {
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "Database connection not available."
  ]);
  exit;
}

$userId = (int) $active_user["user_id"];
$cashBalance = isset($active_user["user_balance"]) ? (float) $active_user["user_balance"] : 0.0;
$holdings = [];

$stmtHoldings = $conn->prepare("
  SELECT at.symbol, h.quantity, h.avg_cost
  FROM holding h
  INNER JOIN available_trade at ON at.id = h.symbol_id
  WHERE h.user_id = ?
    AND h.quantity > 0
");
if ($stmtHoldings) {
  $stmtHoldings->bind_param("i", $userId);
  if ($stmtHoldings->execute()) {
    $stmtHoldings->bind_result($symbol, $quantity, $avgCost);
    while ($stmtHoldings->fetch()) {
      $symbolUpper = strtoupper($symbol);
      $holdings[$symbolUpper] = [
        "symbol" => $symbolUpper,
        "quantity" => (float) $quantity,
        "avg_cost" => (float) $avgCost,
        "market_price" => null,
        "market_value" => 0.0
      ];
    }
  }
  $stmtHoldings->close();
}

$apiSnapshots = [];
if (!empty($holdings)) {
  $symbols = array_keys($holdings);
  $chunks = array_chunk($symbols, 10);
  foreach ($chunks as $chunk) {
    $query = implode(",", array_map("urlencode", $chunk));
    $apiUrl = "http://74.208.158.229/stock-api/buy-sell-info?symbols=" . $query;
    $response = @file_get_contents($apiUrl);
    if ($response === false) {
      continue;
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
      continue;
    }
    foreach ($decoded as $item) {
      if (!empty($item["symbol"])) {
        $symbolUpper = strtoupper($item["symbol"]);
        $apiSnapshots[$symbolUpper] = $item;
      }
    }
  }
}

$holdingsValue = 0.0;
foreach ($holdings as $symbol => &$holding) {
  $snapshot = $apiSnapshots[$symbol] ?? null;
  $price = null;
  if ($snapshot && isset($snapshot["current_price"])) {
    $price = (float) $snapshot["current_price"];
  }
  if ($price === null || $price <= 0) {
    $price = $holding["avg_cost"];
  }
  $holding["market_price"] = $price;
  $holding["market_value"] = $price * $holding["quantity"];
  $holdingsValue += $holding["market_value"];
}
unset($holding);

$portfolioValue = $holdingsValue + $cashBalance;

echo json_encode([
  "status" => "success",
  "timestamp" => gmdate("c"),
  "portfolioValue" => round($portfolioValue, 2),
  "cashBalance" => round($cashBalance, 2),
  "holdingsValue" => round($holdingsValue, 2),
  "holdings" => array_values($holdings)
]);
