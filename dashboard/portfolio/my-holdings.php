<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

if (!$conn) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-danger'>Unable to connect to the portfolio database.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

if (empty($active_user) || !isset($active_user["user_id"])) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-warning'>Your session has expired. Please log in again to view holdings.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

$userId = (int) $active_user["user_id"];
$holdings = [];
$totals = [
  "positions" => 0,
  "shares" => 0.0,
  "book_value" => 0.0,
  "market_value" => 0.0,
  "unrealized" => 0.0
];

$stmt = $conn->prepare("
  SELECT h.holding_id, at.symbol, h.quantity, h.avg_cost, h.note, h.updated_at
  FROM holding h
  INNER JOIN available_trade at ON at.id = h.symbol_id
  WHERE h.user_id = ?
  ORDER BY h.updated_at DESC, at.symbol ASC
");

if ($stmt) {
  $stmt->bind_param("i", $userId);
  if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $row["symbol"] = strtoupper($row["symbol"]);
      $row["quantity"] = (float) $row["quantity"];
      $row["avg_cost"] = (float) $row["avg_cost"];
      $holdings[$row["symbol"]] = $row;
      $totals["positions"] += 1;
      $totals["shares"] += $row["quantity"];
      $totals["book_value"] += $row["quantity"] * $row["avg_cost"];
    }
  }
  $stmt->close();
}

$marketSnapshots = [];
if (!empty($holdings)) {
  $symbols = array_keys($holdings);
  $chunks = array_chunk($symbols, 10);
  foreach ($chunks as $chunk) {
    $querySymbols = implode(",", array_map(function ($symbol) {
      return urlencode($symbol);
    }, $chunk));
    $url = "http://74.208.158.229/stock-api/buy-sell-info?symbols=" . $querySymbols;
    $response = @file_get_contents($url);
    if ($response !== false) {
      $decoded = json_decode($response, true);
      if (is_array($decoded)) {
        foreach ($decoded as $item) {
          if (!empty($item["symbol"])) {
            $marketSnapshots[strtoupper($item["symbol"])] = $item;
          }
        }
      }
    }
  }
}

foreach ($holdings as $symbol => &$holding) {
  $quantity = $holding["quantity"];
  $avgCost = $holding["avg_cost"];

  $lastPrice = null;
  $changePct = null;
  $company = null;
  $tags = [];
  if (isset($marketSnapshots[$symbol])) {
    $snapshot = $marketSnapshots[$symbol];
    $lastPrice = isset($snapshot["current_price"]) ? (float) $snapshot["current_price"] : null;
    $changePct = isset($snapshot["change_pct"]) ? (float) $snapshot["change_pct"] : null;
    $company = $snapshot["company_name"] ?? null;
    if (!empty($snapshot["tags"])) {
      if (is_array($snapshot["tags"])) {
        $tags = $snapshot["tags"];
      } elseif (is_string($snapshot["tags"])) {
        $tags = explode(",", $snapshot["tags"]);
      }
    }
  }
  $holding["company"] = $company;
  $holding["tags"] = $tags;
  $holding["last_price"] = $lastPrice;
  $holding["change_pct"] = $changePct;

  $bookValue = $quantity * $avgCost;
  $marketValue = $lastPrice !== null ? $quantity * $lastPrice : null;
  $unrealized = ($marketValue !== null) ? $marketValue - $bookValue : null;

  $holding["book_value"] = $bookValue;
  $holding["market_value"] = $marketValue;
  $holding["unrealized"] = $unrealized;

  $totals["market_value"] += $marketValue ?? $bookValue;
  $totals["unrealized"] += $unrealized ?? 0;
}
unset($holding);

$totals["book_value"] = round($totals["book_value"], 2);
$totals["market_value"] = round($totals["market_value"], 2);
$totals["unrealized"] = round($totals["unrealized"], 2);

$allocationSeries = [];
foreach ($holdings as $holding) {
  if ($holding["market_value"] !== null && $holding["market_value"] > 0) {
    $allocationSeries[] = [
      "symbol" => $holding["symbol"],
      "value" => round($holding["market_value"], 2)
    ];
  }
}
$allocationSeries = array_slice($allocationSeries, 0, 10);

$performanceSeries = [];
foreach ($holdings as $holding) {
  if ($holding["unrealized"] !== null) {
    $performanceSeries[] = [
      "symbol" => $holding["symbol"],
      "value" => round($holding["unrealized"], 2)
    ];
  }
}
usort($performanceSeries, function ($a, $b) {
  return abs($b["value"]) <=> abs($a["value"]);
});
$performanceSeries = array_slice($performanceSeries, 0, 10);

?>

<section class="content">
  <div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-briefcase text-primary mr-2"></i>Portfolio Holdings</h1>
        <p class="text-muted mb-0">Monitor allocation, track performance, and review positions in real time.</p>
      </div>
      <div class="mt-3 mt-md-0">
        <span class="badge badge-primary badge-pill px-3 py-2">As of <?=date("M d, Y h:i A"); ?></span>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-primary">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Total Market Value</div>
            <div class="h4 font-weight-bold mb-0 text-primary">$<?=number_format($totals["market_value"], 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3"><div class="progress-bar bg-primary" style="width:100%"></div></div>
            <small class="text-muted d-block mt-2">Book value: $<?=number_format($totals["book_value"], 2, ".", ","); ?></small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-success">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Unrealized P&L</div>
            <?php $unrealClass = $totals["unrealized"] >= 0 ? "text-success" : "text-danger"; ?>
            <div class="h4 font-weight-bold mb-0 <?=$unrealClass;?>"><?=$totals["unrealized"] >= 0 ? "+" : "−"; ?>$<?=number_format(abs($totals["unrealized"]), 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3">
              <?php
              $percentage = $totals["book_value"] > 0 ? min((abs($totals["unrealized"]) / $totals["book_value"]) * 100, 100) : 0;
              ?>
              <div class="progress-bar <?=$totals["unrealized"] >= 0 ? "bg-success" : "bg-danger"; ?>" style="width: <?=$percentage;?>%"></div>
            </div>
            <small class="text-muted d-block mt-2"><?=$totals["positions"]; ?> active positions</small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-info">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Total Shares</div>
            <div class="h4 font-weight-bold mb-0"><?=number_format($totals["shares"], 4, ".", ","); ?> sh</div>
            <div class="progress progress-xs mt-3"><div class="progress-bar bg-info" style="width:100%"></div></div>
            <small class="text-muted d-block mt-2">Average cost $<?=($totals["shares"] > 0) ? number_format($totals["book_value"] / $totals["shares"], 2, ".", ",") : "0.00"; ?></small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-secondary">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Top Position</div>
            <?php
            $topHolding = null;
            foreach ($holdings as $holding) {
              if ($topHolding === null || ($holding["market_value"] ?? 0) > ($topHolding["market_value"] ?? 0)) {
                $topHolding = $holding;
              }
            }
            ?>
            <?php if ($topHolding) { ?>
              <div class="h4 font-weight-bold mb-0"><?=htmlspecialchars($topHolding["symbol"]); ?></div>
              <div class="progress progress-xs mt-3"><div class="progress-bar bg-secondary" style="width:100%"></div></div>
              <small class="text-muted d-block mt-2">Value: $<?=number_format($topHolding["market_value"] ?? $topHolding["book_value"], 2, ".", ","); ?></small>
            <?php } else { ?>
              <div class="h4 font-weight-bold mb-0">—</div>
              <small class="text-muted d-block mt-2">Add holdings to populate</small>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-6">
        <div class="card card-outline card-primary">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Allocation Breakdown</h3>
              <small class="text-muted">Top 10 positions by market value</small>
            </div>
            <button class="btn btn-tool" type="button" id="refresh-allocation"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div class="card-body">
            <?php if (!empty($allocationSeries)) { ?>
              <div id="allocation-chart" class="mb-3" data-series='<?=json_encode($allocationSeries); ?>' style="height:280px;"></div>
            <?php } else { ?>
              <div class="text-center text-muted py-5">No active allocations detected.</div>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="col-xl-6">
        <div class="card card-outline card-success">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Unrealized Movers</h3>
              <small class="text-muted">Largest gains and drawdowns</small>
            </div>
            <button class="btn btn-tool" type="button" id="refresh-performance"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div class="card-body">
            <?php if (!empty($performanceSeries)) { ?>
              <div id="performance-chart" class="mb-3" data-series='<?=json_encode($performanceSeries); ?>' style="height:280px;"></div>
            <?php } else { ?>
              <div class="text-center text-muted py-5">Trade activity will populate this view.</div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-dark mt-4">
      <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
          <h3 class="card-title mb-0">Position Detail</h3>
          <small class="text-muted">Comprehensive breakdown of each holding.</small>
        </div>
        <?php if (!empty($holdings)) { ?>
        <div class="btn-group btn-group-sm mt-3 mt-md-0" role="group">
          <button type="button" class="btn btn-outline-secondary filter-holding active" data-filter="all">All</button>
          <button type="button" class="btn btn-outline-success filter-holding" data-filter="winners">Gainers</button>
          <button type="button" class="btn btn-outline-danger filter-holding" data-filter="losers">Losers</button>
        </div>
        <?php } ?>
      </div>
      <div class="card-body p-0">
        <?php if (empty($holdings)) { ?>
          <div class="text-center text-muted py-5">You do not have any holdings yet. Execute trades to build your portfolio.</div>
        <?php } else { ?>
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="holdings-table">
              <thead class="thead-light">
                <tr>
                  <th>Symbol</th>
                  <th>Company</th>
                  <th class="text-right">Quantity</th>
                  <th class="text-right">Avg Cost</th>
                  <th class="text-right">Last</th>
                  <th class="text-right">Book Value</th>
                  <th class="text-right">Market Value</th>
                  <th class="text-right">Unrealized</th>
                  <th>Tags</th>
                  <th>Note</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($holdings as $holding) {
                  $marketValue = $holding["market_value"] ?? $holding["book_value"];
                  $unrealized = $holding["unrealized"];
                  $badgeClass = ($holding["change_pct"] ?? 0) >= 0 ? "badge-success" : "badge-danger";
                  $rowClass = ($unrealized ?? 0) >= 0 ? "holding-winner" : "holding-loser";
                  $changeText = $holding["change_pct"] !== null ? number_format($holding["change_pct"], 2) . "%" : "—";
                  ?>
                  <tr class="<?=$rowClass; ?>">
                    <td>
                      <div class="d-flex align-items-center">
                        <span class="badge badge-secondary badge-pill mr-2"><?=htmlspecialchars($holding["symbol"]); ?></span>
                        <span class="badge <?=$badgeClass; ?>"><?=($holding["change_pct"] ?? 0) >= 0 ? "+" : "";?><?=$changeText; ?></span>
                      </div>
                    </td>
                    <td><?=htmlspecialchars($holding["company"] ?? "—"); ?></td>
                    <td class="text-right"><?=number_format($holding["quantity"], 4, ".", ","); ?></td>
                    <td class="text-right">$<?=number_format($holding["avg_cost"], 2, ".", ","); ?></td>
                    <td class="text-right"><?=$holding["last_price"] !== null ? "$" . number_format($holding["last_price"], 2, ".", ",") : "—"; ?></td>
                    <td class="text-right">$<?=number_format($holding["book_value"], 2, ".", ","); ?></td>
                    <td class="text-right">$<?=number_format($marketValue, 2, ".", ","); ?></td>
                    <td class="text-right <?=$unrealized >= 0 ? "text-success" : "text-danger"; ?>">
                      <?=$unrealized >= 0 ? "+" : "−"; ?>$<?=number_format(abs($unrealized), 2, ".", ","); ?>
                    </td>
                    <td>
                      <?php if (!empty($holding["tags"])) {
                        foreach ($holding["tags"] as $tag) {
                          if (!$tag) continue;
                          echo "<span class='badge badge-light mr-1 mb-1'>" . htmlspecialchars(trim($tag)) . "</span>";
                        }
                      } ?>
                    </td>
                    <td>
                      <?php if (!empty($holding["note"])) { ?>
                        <span class="badge badge-soft">Note</span>
                        <span class="d-block small text-muted"><?=nl2br(htmlspecialchars($holding["note"])); ?></span>
                      <?php } ?>
                    </td>
                    <td><?=!empty($holding["updated_at"]) ? date("M d, Y", strtotime($holding["updated_at"])) : "—"; ?></td>
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
  .border-left-primary { border-left: 4px solid #007bff !important; }
  .border-left-success { border-left: 4px solid #28a745 !important; }
  .border-left-info { border-left: 4px solid #17a2b8 !important; }
  .border-left-secondary { border-left: 4px solid #6c757d !important; }
  .progress.progress-xs { height: 4px; border-radius: 999px; background-color: rgba(0,0,0,0.05); }
  #holdings-table tbody tr:hover { background-color: rgba(0,123,255,0.06); }
  .filter-holding.active { background-color: #343a40; color: #fff; }
  .holding-winner { }
  .holding-loser { }
  .badge-soft { background: rgba(0,123,255,0.12); color: #004085; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const allocationEl = document.getElementById('allocation-chart');
  const performanceEl = document.getElementById('performance-chart');
  const allocationButton = document.getElementById('refresh-allocation');
  const performanceButton = document.getElementById('refresh-performance');

  let allocationChart = null;
  let performanceChart = null;

  function renderAllocationChart() {
    if (!allocationEl) return;
    const raw = allocationEl.dataset.series;
    if (!raw) return;
    const seriesData = JSON.parse(raw || "[]");
    if (!seriesData.length) return;

    const labels = seriesData.map(item => item.symbol);
    const values = seriesData.map(item => item.value);

    if (allocationChart) allocationChart.destroy();

    allocationChart = new ApexCharts(allocationEl, {
      series: values,
      chart: { type: 'pie', height: 280 },
      labels,
      legend: { position: 'right' },
      dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(1)}%` },
      tooltip: { y: { formatter: val => '$' + Number(val).toLocaleString() } }
    });
    allocationChart.render();
  }

  function renderPerformanceChart() {
    if (!performanceEl) return;
    const raw = performanceEl.dataset.series;
    if (!raw) return;
    const seriesData = JSON.parse(raw || "[]");
    if (!seriesData.length) return;

    const categories = seriesData.map(item => item.symbol);
    const values = seriesData.map(item => item.value);

    if (performanceChart) performanceChart.destroy();

    performanceChart = new ApexCharts(performanceEl, {
      series: [{ name: 'Unrealized P&L', data: values }],
      chart: { type: 'bar', height: 280 },
      plotOptions: {
        bar: {
          horizontal: true,
          dataLabels: { position: 'center' }
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return (val >= 0 ? '+' : '−') + '$' + Math.abs(val).toLocaleString();
        },
        style: { colors: ['#fff'] }
      },
      xaxis: {
        categories,
        labels: {
          formatter: function (val) { return '$' + Number(val).toLocaleString(); }
        }
      },
      colors: values.map(val => val >= 0 ? '#28a745' : '#dc3545')
    });
    performanceChart.render();
  }

  renderAllocationChart();
  renderPerformanceChart();

  if (allocationButton) allocationButton.addEventListener('click', renderAllocationChart);
  if (performanceButton) performanceButton.addEventListener('click', renderPerformanceChart);

  const holdingFilters = document.querySelectorAll('.filter-holding');
  const holdingRows = document.querySelectorAll('#holdings-table tbody tr');
  holdingFilters.forEach(function (filter) {
    filter.addEventListener('click', function () {
      const type = filter.getAttribute('data-filter');
      holdingFilters.forEach(btn => btn.classList.remove('active'));
      filter.classList.add('active');

      holdingRows.forEach(function (row) {
        row.classList.remove('d-none');
        if (type === 'winners' && !row.classList.contains('holding-winner')) {
          row.classList.add('d-none');
        } else if (type === 'losers' && !row.classList.contains('holding-loser')) {
          row.classList.add('d-none');
        }
      });
    });
  });
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
