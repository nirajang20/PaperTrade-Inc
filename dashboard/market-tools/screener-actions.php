<?php
require "../lib/Core.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode([
    "status" => "error",
    "message" => "Method not allowed"
  ]);
  exit;
}

if (empty($active_user) || !isset($active_user["user_id"])) {
  http_response_code(401);
  echo json_encode([
    "status" => "error",
    "message" => "Please log in to manage your watchlist."
  ]);
  exit;
}

$userId = (int) $active_user["user_id"];
$payload = json_decode(file_get_contents("php://input"), true);

if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode([
    "status" => "error",
    "message" => "Invalid payload."
  ]);
  exit;
}

$action = $payload["action"] ?? "";

function sanitize_symbol($symbol) {
  $symbol = strtoupper(trim((string) $symbol));
  return preg_replace("/[^A-Z0-9\.\-\^]/", "", $symbol);
}

switch ($action) {
  case "watchlist:add":
    $symbolInput = sanitize_symbol($payload["symbol"] ?? "");
    $noteInput = isset($payload["note"]) ? trim((string) $payload["note"]) : null;

    if ($symbolInput === "") {
      http_response_code(400);
      echo json_encode([
        "status" => "error",
        "message" => "Please provide a valid ticker symbol."
      ]);
      exit;
    }

    if (!$conn) {
      http_response_code(500);
      echo json_encode([
        "status" => "error",
        "message" => "Database connection unavailable."
      ]);
      exit;
    }

    $symbolId = null;
    $stmtSymbol = $conn->prepare("SELECT id FROM available_trade WHERE symbol = ?");
    if ($stmtSymbol) {
      $stmtSymbol->bind_param("s", $symbolInput);
      if ($stmtSymbol->execute()) {
        $stmtSymbol->bind_result($symbolId);
        $stmtSymbol->fetch();
      }
      $stmtSymbol->close();
    }

    if (!$symbolId) {
      $stmtInsertSymbol = $conn->prepare("INSERT INTO available_trade (symbol) VALUES (?)");
      if ($stmtInsertSymbol) {
        $stmtInsertSymbol->bind_param("s", $symbolInput);
        if ($stmtInsertSymbol->execute()) {
          $symbolId = $stmtInsertSymbol->insert_id;
        }
        $stmtInsertSymbol->close();
      }
    }

    if (!$symbolId) {
      http_response_code(500);
      echo json_encode([
        "status" => "error",
        "message" => "Unable to register the selected ticker."
      ]);
      exit;
    }

    $stmtWatch = $conn->prepare("INSERT INTO watchlist (user_id, symbol_id, note) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE note = VALUES(note)");
    if (!$stmtWatch) {
      http_response_code(500);
      echo json_encode([
        "status" => "error",
        "message" => "Unable to prepare watchlist statement."
      ]);
      exit;
    }

    $noteParam = ($noteInput !== null && $noteInput !== "") ? $noteInput : null;
    $stmtWatch->bind_param("iis", $userId, $symbolId, $noteParam);

    if (!$stmtWatch->execute()) {
      $stmtWatch->close();
      http_response_code(500);
      echo json_encode([
        "status" => "error",
        "message" => "Unable to add the symbol to your watchlist."
      ]);
      exit;
    }

    $stmtWatch->close();
    echo json_encode([
      "status" => "success",
      "message" => "{$symbolInput} added to your watchlist."
    ]);
    exit;

  default:
    http_response_code(400);
    echo json_encode([
      "status" => "error",
      "message" => "Unsupported action."
    ]);
    exit;
}
