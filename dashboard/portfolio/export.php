<?php
require "../lib/Core.php";

$errorMessage = null;
$periodOptions = [
  "5d" => "Last 5 Days",
  "1mo" => "1 Month",
  "3mo" => "3 Months",
  "6mo" => "6 Months",
  "1y" => "1 Year",
  "max" => "Max Available"
];
$intervalOptions = [
  "1m" => "1 Minute",
  "5m" => "5 Minutes",
  "15m" => "15 Minutes",
  "1h" => "1 Hour",
  "1d" => "1 Day",
  "1wk" => "1 Week",
  "1mo" => "1 Month"
];
$defaultSymbol = "AAPL";
$defaultPeriod = "1mo";
$defaultInterval = "1d";

function sanitize_symbol($symbol) {
  $symbol = strtoupper(trim($symbol));
  return preg_replace("/[^A-Z0-9\.\-\^]/", "", $symbol);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["export_submit"])) {
  $symbolInput = sanitize_symbol($_POST["symbol"] ?? "");
  $period = $_POST["period"] ?? $defaultPeriod;
  $interval = $_POST["interval"] ?? $defaultInterval;

  if ($symbolInput === "") {
    $errorMessage = "Enter a ticker symbol before exporting.";
  } elseif (!array_key_exists($period, $periodOptions)) {
    $errorMessage = "Invalid period selected.";
  } elseif (!array_key_exists($interval, $intervalOptions)) {
    $errorMessage = "Invalid interval selected.";
  } else {
    $apiUrl = sprintf(
      "http://74.208.158.229/stock-api/chart-history?symbol=%s&period=%s&interval=%s",
      urlencode($symbolInput),
      urlencode($period),
      urlencode($interval)
    );

    $response = @file_get_contents($apiUrl);
    if ($response === false) {
      $errorMessage = "Unable to contact the export API. Try again later.";
    } else {
      $payload = json_decode($response, true);
      if (!$payload || !isset($payload["data"]) || !is_array($payload["data"]) || empty($payload["data"])) {
        $errorMessage = "No chart data returned for the requested symbol.";
      } else {
        $filename = sprintf(
          "%s_%s_%s.csv",
          $symbolInput,
          $period,
          $interval
        );
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $output = fopen("php://output", "w");
        fputcsv($output, ["timestamp", "datetime", "open", "high", "low", "close"]);
        foreach ($payload["data"] as $entry) {
          if (!isset($entry["x"], $entry["y"]) || !is_array($entry["y"]) || count($entry["y"]) !== 4) {
            continue;
          }
          $timestampMs = (int) $entry["x"];
          $open = $entry["y"][0];
          $high = $entry["y"][1];
          $low = $entry["y"][2];
          $close = $entry["y"][3];
          $timestampSec = (int) floor($timestampMs / 1000);
          $datetime = gmdate("Y-m-d H:i:s", $timestampSec);
          fputcsv($output, [$timestampMs, $datetime, $open, $high, $low, $close]);
        }
        fclose($output);
        exit;
      }
    }
  }
}

require "../lib/PAGE-top.php";

?>

<section class="content">
  <div class="container-fluid">
    <div class="card card-outline card-primary">
      <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
          <div class="mb-4 mb-lg-0">
            <h1 class="h3 mb-1"><i class="fas fa-download text-primary mr-2"></i>Data Export Studio</h1>
            <p class="text-muted mb-0">Download historical OHLC data for equities, ETFs, or crypto in seconds.</p>
          </div>
          <div class="text-right">
            <span class="badge badge-primary badge-pill px-3 py-2">CSV format · UTC timestamps</span>
          </div>
        </div>
      </div>
    </div>

    <?php if ($errorMessage) { ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?=$errorMessage; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php } ?>

    <div class="row">
      <div class="col-xl-7">
        <div class="card card-outline card-primary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Export Parameters</h3>
          </div>
          <div class="card-body">
            <form method="post" class="export-form">
              <div class="form-row">
                <div class="form-group col-lg-6">
                  <label for="export-symbol">Symbol</label>
                  <input type="text"
                         class="form-control form-control-lg"
                         id="export-symbol"
                         name="symbol"
                         placeholder="e.g. NVDA, BTC-USD"
                         value="<?=htmlspecialchars($_POST["symbol"] ?? $defaultSymbol, ENT_QUOTES); ?>"
                         required>
                  <small class="form-text text-muted">Use Yahoo-style tickers for indices and crypto (e.g. ^GSPC).</small>
                </div>
                <div class="form-group col-lg-3 col-sm-6">
                  <label for="export-period">Period</label>
                  <select class="form-control" id="export-period" name="period">
                    <?php
                    $selectedPeriod = $_POST["period"] ?? $defaultPeriod;
                    foreach ($periodOptions as $value => $label) {
                      $selected = $value === $selectedPeriod ? "selected" : "";
                      echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="form-group col-lg-3 col-sm-6">
                  <label for="export-interval">Interval</label>
                  <select class="form-control" id="export-interval" name="interval">
                    <?php
                    $selectedInterval = $_POST["interval"] ?? $defaultInterval;
                    foreach ($intervalOptions as $value => $label) {
                      $selected = $value === $selectedInterval ? "selected" : "";
                      echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-lg-12">
                  <label>Columns</label>
                  <div class="bg-light border rounded px-3 py-2 small text-muted">
                    <i class="far fa-file-alt mr-1"></i> timestamp_ms · datetime_utc · open · high · low · close
                  </div>
                </div>
              </div>
              <button type="submit" name="export_submit" class="btn btn-primary btn-lg">
                <i class="fas fa-file-csv mr-2"></i>Export CSV
              </button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-xl-5">
        <div class="card card-outline card-info h-100">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Tips</h3>
          </div>
          <div class="card-body">
            <ul class="list-unstyled export-tips mb-0">
              <li class="mb-3">
                <i class="fas fa-check text-success mr-2"></i>
                Use shorter intervals with shorter periods to keep data precise and export sizes manageable.
              </li>
              <li class="mb-3">
                <i class="fas fa-check text-success mr-2"></i>
                Crypto tickers update 24/7; equities follow exchange trading hours reflected in the data.
              </li>
              <li class="mb-3">
                <i class="fas fa-check text-success mr-2"></i>
                Combine with your favourite spreadsheet or BI tool for further analysis.
              </li>
              <li>
                <i class="fas fa-info-circle text-info mr-2"></i>
                Data pulled from the Stock API backing the trade workspace (powered by yfinance).
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .export-form .form-control-lg {
    font-size: 1.05rem;
  }
  .export-tips li {
    line-height: 1.4;
  }
</style>

<?php require "../lib/PAGE-bottom.php"; ?>
