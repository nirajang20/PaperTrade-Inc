<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

if (!$conn) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-danger'>Unable to connect to the trading database.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

if (empty($active_user) || !isset($active_user["user_id"])) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-warning'>Your session has expired. Please log in again to view transactions.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

$userId = (int) $active_user["user_id"];

$transactions = [];
$symbolStats = [];
$totals = [
  "buy_value" => 0.0,
  "sell_value" => 0.0,
  "fees" => 0.0,
  "trade_count" => 0,
  "buy_count" => 0,
  "sell_count" => 0,
  "shares_traded" => 0.0
];

$stmt = $conn->prepare("
  SELECT t.transaction_id, t.side, t.quantity, t.price, t.fees, t.executed_at,
         at.symbol
  FROM transaction t
  INNER JOIN available_trade at ON at.id = t.symbol_id
  WHERE t.user_id = ?
  ORDER BY t.executed_at DESC
");

if ($stmt) {
  $stmt->bind_param("i", $userId);
  if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $symbol = strtoupper($row["symbol"]);
      if (!isset($symbolStats[$symbol])) {
        $symbolStats[$symbol] = [
          "symbol" => $symbol,
          "buy_value" => 0.0,
          "sell_value" => 0.0,
          "fees" => 0.0,
          "trades" => 0,
          "shares" => 0.0
        ];
      }

      $lineValue = (float) $row["quantity"] * (float) $row["price"];
      $feeValue = (float) $row["fees"];
      $totals["fees"] += $feeValue;
      $totals["trade_count"] += 1;
      $symbolStats[$symbol]["fees"] += $feeValue;
      $symbolStats[$symbol]["trades"] += 1;
      $symbolStats[$symbol]["shares"] += (float) $row["quantity"];
      $totals["shares_traded"] += (float) $row["quantity"];

      if ($row["side"] === "BUY") {
        $totals["buy_count"] += 1;
        $spend = $lineValue + $feeValue;
        $totals["buy_value"] += $spend;
        $symbolStats[$symbol]["buy_value"] += $spend;
        $row["total_value"] = $spend;
      } else {
        $totals["sell_count"] += 1;
        $proceeds = max($lineValue - $feeValue, 0);
        $totals["sell_value"] += $proceeds;
        $symbolStats[$symbol]["sell_value"] += $proceeds;
        $row["total_value"] = $proceeds;
      }

      $transactions[] = $row;
    }
  }
  $stmt->close();
}

$symbolStats = array_values($symbolStats);
usort($symbolStats, function ($a, $b) {
  return ($b["buy_value"] - $a["buy_value"]) ?: ($b["sell_value"] - $a["sell_value"]);
});

$topSpent = "—";
$topSpentValue = 0.0;
if (!empty($symbolStats)) {
  $topSpent = $symbolStats[0]["symbol"];
  $topSpentValue = $symbolStats[0]["buy_value"];
}

$mostTradesSymbol = "—";
$maxTrades = 0;
foreach ($symbolStats as $stat) {
  if ($stat["trades"] > $maxTrades) {
    $maxTrades = $stat["trades"];
    $mostTradesSymbol = $stat["symbol"];
  }
}

$netCashFlow = $totals["sell_value"] - $totals["buy_value"];
$allocationSeries = [];
foreach ($symbolStats as $stat) {
  if ($stat["buy_value"] <= 0) continue;
  $allocationSeries[] = [
    "symbol" => $stat["symbol"],
    "value" => round($stat["buy_value"], 2)
  ];
}

$allocationSeries = array_slice($allocationSeries, 0, 10);

?>

<section class="content">
  <div class="container-fluid">

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-receipt text-danger mr-2"></i>Transaction Ledger</h1>
        <p class="text-muted mb-0">Review every execution, understand where you deploy capital, and monitor realized cash flow.</p>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-danger">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Total Capital Deployed</div>
            <div class="h4 font-weight-bold mb-0 text-danger">$<?=number_format($totals["buy_value"], 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3">
              <div class="progress-bar bg-danger" style="width: 100%"></div>
            </div>
            <small class="text-muted d-block mt-2"><?=$totals["buy_count"];?> buy orders</small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-success">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Realized Proceeds</div>
            <div class="h4 font-weight-bold mb-0 text-success">$<?=number_format($totals["sell_value"], 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3">
              <div class="progress-bar bg-success" style="width: 100%"></div>
            </div>
            <small class="text-muted d-block mt-2"><?=$totals["sell_count"];?> sell orders</small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-info">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Net Cash Flow</div>
            <?php $netClass = $netCashFlow >= 0 ? "text-success" : "text-danger"; ?>
            <div class="h4 font-weight-bold mb-0 <?=$netClass;?>"><?=$netCashFlow >= 0 ? "+" : "−";?>$<?=number_format(abs($netCashFlow), 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3">
              <?php
              $denominator = max($totals["buy_value"], $totals["sell_value"], 1);
              $percentage = min(abs($netCashFlow) / $denominator * 100, 100);
              ?>
              <div class="progress-bar <?=$netCashFlow >= 0 ? "bg-success" : "bg-danger"; ?>" style="width: <?=$percentage;?>%"></div>
            </div>
            <small class="text-muted d-block mt-2">Net of fees: $<?=number_format($totals["fees"], 2, ".", ","); ?></small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-secondary">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Trading Activity</div>
            <div class="h4 font-weight-bold mb-0"><?=number_format($totals["trade_count"]); ?> orders</div>
            <div class="progress progress-xs mt-3">
              <div class="progress-bar bg-secondary" style="width: 100%"></div>
            </div>
            <small class="text-muted d-block mt-2"><?=number_format($totals["shares_traded"], 4, ".", ","); ?> shares exchanged</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-4">
        <div class="card card-outline card-danger">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Highlights</h3>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0 market-highlights">
              <li class="mb-3">
                <span class="text-muted text-uppercase small d-block">Largest Allocation</span>
                <span class="font-weight-bold"><?=$topSpent ? htmlspecialchars($topSpent) : "—"; ?></span>
                <span class="text-muted d-block">$<?=number_format($topSpentValue ?? 0, 2, ".", ","); ?> deployed</span>
              </li>
              <li class="mb-3">
                <span class="text-muted text-uppercase small d-block">Most Active Symbol</span>
                <span class="font-weight-bold"><?=htmlspecialchars($mostTradesSymbol); ?></span>
                <span class="text-muted d-block"><?=$maxTrades;?> total trades</span>
              </li>
              <li class="mb-3">
                <span class="text-muted text-uppercase small d-block">Total Fees Paid</span>
                <span class="font-weight-bold">$<?=number_format($totals["fees"], 2, ".", ","); ?></span>
                <span class="text-muted d-block">Across all executions</span>
              </li>
              <li>
                <span class="text-muted text-uppercase small d-block">Unique Symbols</span>
                <span class="font-weight-bold"><?=count($symbolStats); ?></span>
                <span class="text-muted d-block">Diversification breadth</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-xl-8">
        <div class="card card-outline card-primary h-100">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Capital Allocation by Symbol</h3>
              <small class="text-muted">Top destinations of deployed cash (buy-side)</small>
            </div>
            <button class="btn btn-tool" id="refresh-allocation" type="button"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div class="card-body">
            <?php if (!empty($allocationSeries)) { ?>
              <div id="allocation-chart"
                   data-series='<?=json_encode($allocationSeries); ?>'
                   class="mb-3"
                   style="height: 260px;"></div>
            <?php } else { ?>
              <div class="text-center text-muted py-5">No buy-side transactions yet. Start trading to build your allocation profile.</div>
            <?php } ?>
            <?php if (!empty($symbolStats)) { ?>
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Symbol</th>
                      <th class="text-right">Buy Value</th>
                      <th class="text-right">Realized</th>
                      <th class="text-right">Fees</th>
                      <th class="text-right">Trades</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (array_slice($symbolStats, 0, 8) as $stat) { ?>
                      <tr>
                        <td><span class="badge badge-pill badge-secondary"><?=htmlspecialchars($stat["symbol"]); ?></span></td>
                        <td class="text-right">$<?=number_format($stat["buy_value"], 2, ".", ","); ?></td>
                        <td class="text-right">$<?=number_format($stat["sell_value"], 2, ".", ","); ?></td>
                        <td class="text-right">$<?=number_format($stat["fees"], 2, ".", ","); ?></td>
                        <td class="text-right"><?=number_format($stat["trades"]); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-dark mt-4">
      <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
          <h3 class="card-title mb-0">Execution History</h3>
          <small class="text-muted">Detailed ledger of all recorded orders.</small>
        </div>
        <?php if (!empty($transactions)) { ?>
        <div class="btn-group btn-group-sm mt-3 mt-md-0" role="group">
          <button type="button" class="btn btn-outline-secondary transaction-filter active" data-side="all">All</button>
          <button type="button" class="btn btn-outline-success transaction-filter" data-side="BUY">Buys</button>
          <button type="button" class="btn btn-outline-danger transaction-filter" data-side="SELL">Sells</button>
        </div>
        <?php } ?>
      </div>
      <div class="card-body p-0">
        <?php if (empty($transactions)) { ?>
          <div class="text-center text-muted py-5">No transactions recorded yet. Your executions will appear here.</div>
        <?php } else { ?>
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="transactions-table">
              <thead class="thead-light">
                <tr>
                  <th>Timestamp</th>
                  <th>Symbol</th>
                  <th>Side</th>
                  <th class="text-right">Quantity</th>
                  <th class="text-right">Price</th>
                  <th class="text-right">Fees</th>
                  <th class="text-right">Net Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($transactions as $row) {
                  $executed = $row["executed_at"] ? date("M d, Y · h:i A", strtotime($row["executed_at"])) : "—";
                  $sideClass = $row["side"] === "BUY" ? "badge-success" : "badge-danger";
                  $displayValue = isset($row["total_value"]) ? $row["total_value"] : (($row["side"] === "BUY")
                    ? ((float)$row["quantity"] * (float)$row["price"] + (float)$row["fees"])
                    : max((float)$row["quantity"] * (float)$row["price"] - (float)$row["fees"], 0));
                  ?>
                  <tr data-side="<?=$row["side"]; ?>">
                    <td><?=$executed; ?></td>
                    <td><span class="font-weight-bold"><?=htmlspecialchars(strtoupper($row["symbol"])); ?></span></td>
                    <td><span class="badge <?=$sideClass; ?>"><?=$row["side"]; ?></span></td>
                    <td class="text-right"><?=number_format($row["quantity"], 4, ".", ","); ?></td>
                    <td class="text-right">$<?=number_format($row["price"], 2, ".", ","); ?></td>
                    <td class="text-right">$<?=number_format($row["fees"], 2, ".", ","); ?></td>
                    <td class="text-right <?=$row["side"] === "SELL" ? "text-success" : "text-danger"; ?>">
                      <?=$row["side"] === "SELL" ? "+" : "−"; ?>$<?=number_format($displayValue, 2, ".", ","); ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </div>

  </div>
</section>

<style>
  .border-left-danger { border-left: 4px solid #dc3545 !important; }
  .border-left-success { border-left: 4px solid #28a745 !important; }
  .border-left-info { border-left: 4px solid #17a2b8 !important; }
  .border-left-secondary { border-left: 4px solid #6c757d !important; }
  .market-highlights li span.font-weight-bold { font-size: 1.1rem; }
  .progress.progress-xs { height: 4px; border-radius: 999px; background-color: rgba(0,0,0,0.05); }
  #transactions-table tbody tr { transition: background 0.15s ease; }
  #transactions-table tbody tr:hover { background-color: rgba(220,53,69,0.08); }
  .transaction-filter.active { background-color: #343a40; color: #fff; }
  .badge-soft { background: rgba(40, 167, 69, 0.08); color: #19692c; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const allocationEl = document.getElementById('allocation-chart');
  const allocationButton = document.getElementById('refresh-allocation');
  let allocationChart = null;

  function renderAllocationChart() {
    if (!allocationEl) return;
    const raw = allocationEl.dataset.series;
    if (!raw) return;
    const seriesData = JSON.parse(raw || "[]");
    if (!seriesData.length) return;

    const labels = seriesData.map(item => item.symbol);
    const values = seriesData.map(item => item.value);

    if (allocationChart) {
      allocationChart.destroy();
    }

    allocationChart = new ApexCharts(allocationEl, {
      series: values,
      chart: { type: 'donut', height: 260 },
      labels,
      legend: { position: 'right' },
      dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(1)}%` },
      tooltip: {
        y: {
          formatter: function (val) {
            return '$' + Number(val).toLocaleString();
          }
        }
      }
    });
    allocationChart.render();
  }

  renderAllocationChart();
  if (allocationButton) {
    allocationButton.addEventListener('click', function () {
      renderAllocationChart();
    });
  }

  const filterButtons = document.querySelectorAll('.transaction-filter');
  const tableRows = document.querySelectorAll('#transactions-table tbody tr');
  filterButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      const side = btn.getAttribute('data-side');
      filterButtons.forEach(function (item) { item.classList.remove('active'); });
      btn.classList.add('active');
      tableRows.forEach(function (row) {
        if (side === 'all') {
          row.classList.remove('d-none');
        } else if (row.getAttribute('data-side') === side) {
          row.classList.remove('d-none');
        } else {
          row.classList.add('d-none');
        }
      });
    });
  });
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
