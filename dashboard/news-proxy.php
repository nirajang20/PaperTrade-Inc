<?php
require __DIR__ . "/lib/Core.php";
require __DIR__ . "/lib/NewsRepository.php";
require __DIR__ . "/lib/NewsService.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  http_response_code(405);
  echo json_encode([
    "status" => "error",
    "message" => "Only GET requests are allowed."
  ]);
  exit();
}

if (!isset($_SESSION["user"], $_SESSION["active_email"])) {
  http_response_code(401);
  echo json_encode([
    "status" => "error",
    "message" => "Authentication required."
  ]);
  exit();
}

if (!($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "Database connection unavailable."
  ]);
  exit();
}

$repository = new NewsRepository($conn);
$service = new NewsService($repository);
$scope = strtolower(trim((string) ($_GET["scope"] ?? "portfolio")));

try {
  if ($scope === "symbol") {
    $symbol = (string) ($_GET["symbol"] ?? "");
    $payload = $service->getSymbolNews($symbol);

    echo json_encode([
      "status" => "success",
      "scope" => "symbol",
      "symbol" => $payload["papertrade_symbol"],
      "articles" => $payload["articles"],
      "cached" => !empty($payload["cached"])
    ]);
    exit();
  }

  if ($scope === "portfolio") {
    $userId = $repository->getUserIdByEmail((string) $_SESSION["active_email"]);
    if ($userId <= 0) {
      http_response_code(401);
      echo json_encode([
        "status" => "error",
        "message" => "Authenticated user could not be resolved."
      ]);
      exit();
    }

    $payload = $service->getPortfolioNews($userId);
    echo json_encode([
      "status" => "success",
      "scope" => "portfolio",
      "symbols" => $payload["symbols"],
      "articles" => $payload["articles"]
    ]);
    exit();
  }

  throw new InvalidArgumentException("Unsupported scope requested.");
} catch (InvalidArgumentException $exception) {
  http_response_code(400);
  echo json_encode([
    "status" => "error",
    "message" => $exception->getMessage()
  ]);
} catch (RuntimeException $exception) {
  http_response_code(503);
  echo json_encode([
    "status" => "error",
    "message" => $exception->getMessage()
  ]);
} catch (Throwable $exception) {
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "Unable to load news at this time."
  ]);
}
