<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

if (!$conn) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-danger'>Unable to connect to the performance data source.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

if (empty($active_user) || !isset($active_user["user_id"])) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-warning'>Your session has expired. Please log in again to review performance.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

$userId = (int) $active_user["user_id"];

$holdings = [];
$holdingsCostMap = [];
$totals = [
  "buy_spend" => 0.0,
  "sell_proceeds" => 0.0,
  "fees" => 0.0,
  "book_value" => 0.0,
  "market_value" => 0.0,
  "unrealized" => 0.0,
];

$holdingStmt = $conn->prepare("
  SELECT h.holding_id, at.symbol, h.quantity, h.avg_cost, h.note, h.updated_at
  FROM holding h
  INNER JOIN available_trade at ON at.id = h.symbol_id
  WHERE h.user_id = ?
  ORDER BY h.updated_at DESC, at.symbol ASC
");

if ($holdingStmt) {
  $holdingStmt->bind_param("i", $userId);
  if ($holdingStmt->execute()) {
    $result = $holdingStmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $symbol = strtoupper($row["symbol"]);
      $row["symbol"] = $symbol;
      $row["quantity"] = (float) $row["quantity"];
      $row["avg_cost"] = (float) $row["avg_cost"];
      $row["book_value"] = $row["quantity"] * $row["avg_cost"];
      $row["market_value"] = null;
      $row["unrealized"] = null;
      $holdings[$symbol] = $row;
      $holdingsCostMap[$symbol] = $row["book_value"];
      $totals["book_value"] += $row["book_value"];
    }
  }
  $holdingStmt->close();
}

$marketSnapshots = [];
if (!empty($holdings)) {
  $symbols = array_keys($holdings);
  $chunks = array_chunk($symbols, 10);
  foreach ($chunks as $chunk) {
    $querySymbols = implode(",", array_map("urlencode", $chunk));
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
  $snapshot = $marketSnapshots[$symbol] ?? null;
  $lastPrice = $snapshot && isset($snapshot["current_price"]) ? (float) $snapshot["current_price"] : null;
  $changePct = $snapshot && isset($snapshot["change_pct"]) ? (float) $snapshot["change_pct"] : null;
  $company = $snapshot["company_name"] ?? null;

  $holding["company"] = $company;
  $holding["last_price"] = $lastPrice;
  $holding["change_pct"] = $changePct;

  if ($lastPrice !== null) {
    $holding["market_value"] = $holding["quantity"] * $lastPrice;
    $holding["unrealized"] = $holding["market_value"] - $holding["book_value"];
    $totals["market_value"] += $holding["market_value"];
    $totals["unrealized"] += $holding["unrealized"];
  } else {
    $totals["market_value"] += $holding["book_value"];
  }
}
unset($holding);

$transactions = [];
$symbolPerformanceIndex = [];
$monthlyCashflow = [];

$txnStmt = $conn->prepare("
  SELECT t.side, t.quantity, t.price, t.fees, t.executed_at, at.symbol
  FROM transaction t
  INNER JOIN available_trade at ON at.id = t.symbol_id
  WHERE t.user_id = ?
  ORDER BY t.executed_at ASC
");

if ($txnStmt) {
  $txnStmt->bind_param("i", $userId);
  if ($txnStmt->execute()) {
    $result = $txnStmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $symbol = strtoupper($row["symbol"]);
      $side = $row["side"] === "SELL" ? "SELL" : "BUY";

      $quantity = (float) $row["quantity"];
      $price = (float) $row["price"];
      $fees = (float) $row["fees"];
      $lineValue = $quantity * $price;

      $transactions[] = $row;
      if (!isset($symbolPerformanceIndex[$symbol])) {
        $symbolPerformanceIndex[$symbol] = [
          "symbol" => $symbol,
          "spent" => 0.0,
          "sold" => 0.0,
          "fees" => 0.0,
          "trades" => 0,
          "buy_shares" => 0.0,
          "sell_shares" => 0.0
        ];
      }
      $symbolPerformanceIndex[$symbol]["fees"] += $fees;
      $symbolPerformanceIndex[$symbol]["trades"] += 1;

      $totals["fees"] += $fees;

      if ($side === "BUY") {
        $spend = $lineValue + $fees;
        $totals["buy_spend"] += $spend;
        $symbolPerformanceIndex[$symbol]["spent"] += $spend;
        $symbolPerformanceIndex[$symbol]["buy_shares"] += $quantity;
        $cashFlow = -$spend;
      } else {
        $proceeds = max($lineValue - $fees, 0);
        $totals["sell_proceeds"] += $proceeds;
        $symbolPerformanceIndex[$symbol]["sold"] += $proceeds;
        $symbolPerformanceIndex[$symbol]["sell_shares"] += $quantity;
        $cashFlow = $proceeds;
      }

      if (!empty($row["executed_at"])) {
        $monthKey = date("Y-m", strtotime($row["executed_at"]));
        if (!isset($monthlyCashflow[$monthKey])) {
          $monthlyCashflow[$monthKey] = [
            "net" => 0.0,
            "contributions" => 0.0,
            "withdrawals" => 0.0
          ];
        }
        $monthlyCashflow[$monthKey]["net"] += $cashFlow;
        if ($cashFlow >= 0) {
          $monthlyCashflow[$monthKey]["withdrawals"] += $cashFlow;
        } else {
          $monthlyCashflow[$monthKey]["contributions"] += abs($cashFlow);
        }
      }
    }
  }
  $txnStmt->close();
}

$totals["fees"] = round($totals["fees"], 2);
$totals["net_cash_flow"] = $totals["sell_proceeds"] - $totals["buy_spend"];

$bookValueRemaining = $totals["book_value"];
$costAllocatedToSold = max($totals["buy_spend"] - $bookValueRemaining, 0);
$realizedPnL = $totals["sell_proceeds"] - $costAllocatedToSold;
$totalReturn = $realizedPnL + $totals["unrealized"];
$roiPct = $totals["buy_spend"] > 0 ? ($totalReturn / $totals["buy_spend"]) * 100 : null;
$availableCash = isset($active_user["user_balance"]) ? (float) $active_user["user_balance"] : 0.0;
$totalEquity = $totals["market_value"] + $availableCash;

$symbolPerformance = [];
foreach ($symbolPerformanceIndex as $symbol => $data) {
  $book = $holdings[$symbol]["book_value"] ?? 0.0;
  $unrealized = $holdings[$symbol]["unrealized"] ?? 0.0;
  $marketValue = $holdings[$symbol]["market_value"] ?? null;
  $realized = $data["sold"] - max($data["spent"] - $book, 0);
  $total = $realized + $unrealized;
  $roi = $data["spent"] > 0 ? ($total / $data["spent"]) * 100 : null;

  $symbolPerformance[] = [
    "symbol" => $symbol,
    "spent" => $data["spent"],
    "sold" => $data["sold"],
    "book_value" => $book,
    "market_value" => $marketValue,
    "unrealized" => $unrealized,
    "realized" => $realized,
    "total" => $total,
    "roi" => $roi,
    "trades" => $data["trades"],
    "buy_shares" => $data["buy_shares"],
    "sell_shares" => $data["sell_shares"]
  ];
}

usort($symbolPerformance, function ($a, $b) {
  return $b["total"] <=> $a["total"];
});

$topGainers = array_slice(array_filter($symbolPerformance, function ($item) {
  return $item["total"] > 0;
}), 0, 5);

$topLosers = array_slice(array_filter($symbolPerformance, function ($item) {
  return $item["total"] < 0;
}), 0, 5);

ksort($monthlyCashflow);
$timelineSeries = [];
$cumulative = 0.0;
foreach ($monthlyCashflow as $month => $data) {
  $net = $data["net"];
  $cumulative += $net;
  $timelineSeries[] = [
    "month" => date("M Y", strtotime($month . "-01")),
    "net" => round($net, 2),
    "contributions" => round($data["contributions"], 2),
    "withdrawals" => round($data["withdrawals"], 2),
    "cumulative" => round($cumulative, 2)
  ];
}

$allocationChartSeries = array_slice(array_map(function ($item) {
  return [
    "symbol" => $item["symbol"],
    "value" => round($item["market_value"] ?? $item["book_value"], 2)
  ];
}, array_filter($symbolPerformance, function ($item) {
  return ($item["market_value"] ?? 0) > 0;
})), 0, 10);

$performanceBarSeries = array_slice(array_map(function ($item) {
  return [
    "symbol" => $item["symbol"],
    "value" => round($item["total"], 2)
  ];
}, $symbolPerformance), 0, 10);

?>

<section class="content">
  <div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-chart-line text-success mr-2"></i>Performance Center</h1>
        <p class="text-muted mb-0">Track realized gains, monitor unrealized moves, and compare capital flows over time.</p>
      </div>
      <div class="mt-3 mt-md-0">
        <span class="badge badge-success badge-pill px-3 py-2">Updated <?=date("M d, Y h:i A"); ?></span>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-success">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Total Return</div>
            <div class="h4 font-weight-bold mb-0 text-success"><?=$totalReturn >= 0 ? "+" : "−"; ?>$<?=number_format(abs($totalReturn), 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3"><div class="progress-bar bg-success" style="width:100%"></div></div>
            <small class="text-muted d-block mt-2">ROI <?=($roiPct !== null ? number_format($roiPct, 2) . "%" : "—"); ?></small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-primary">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Realized P&amp;L</div>
            <div class="h4 font-weight-bold mb-0 <?=$realizedPnL >= 0 ? "text-primary" : "text-danger"; ?>">
              <?=$realizedPnL >= 0 ? "+" : "−"; ?>$<?=number_format(abs($realizedPnL), 2, ".", ","); ?>
            </div>
            <div class="progress progress-xs mt-3"><div class="progress-bar bg-primary" style="width:100%"></div></div>
            <small class="text-muted d-block mt-2">Sell proceeds $<?=number_format($totals["sell_proceeds"], 2, ".", ","); ?></small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-warning">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Unrealized P&amp;L</div>
            <div class="h4 font-weight-bold mb-0 <?=$totals["unrealized"] >= 0 ? "text-warning" : "text-danger"; ?>">
              <?=$totals["unrealized"] >= 0 ? "+" : "−"; ?>$<?=number_format(abs($totals["unrealized"]), 2, ".", ","); ?>
            </div>
            <div class="progress progress-xs mt-3">
              <?php
              $percentUnreal = $totals["book_value"] > 0 ? min((abs($totals["unrealized"]) / $totals["book_value"]) * 100, 100) : 0;
              ?>
              <div class="progress-bar <?=$totals["unrealized"] >= 0 ? "bg-warning" : "bg-danger"; ?>" style="width: <?=$percentUnreal;?>%"></div>
            </div>
            <small class="text-muted d-block mt-2">Market value $<?=number_format($totals["market_value"], 2, ".", ","); ?></small>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm border-left-secondary">
          <div class="card-body">
            <div class="text-muted text-uppercase small mb-1">Total Equity</div>
            <div class="h4 font-weight-bold mb-0">$<?=number_format($totalEquity, 2, ".", ","); ?></div>
            <div class="progress progress-xs mt-3"><div class="progress-bar bg-secondary" style="width:100%"></div></div>
            <small class="text-muted d-block mt-2">Cash available $<?=number_format($availableCash, 2, ".", ","); ?></small>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-7">
        <div class="card card-outline card-success h-100">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Capital Flow</h3>
              <small class="text-muted">Monthly contributions vs withdrawals</small>
            </div>
            <button class="btn btn-tool" type="button" id="refresh-timeline"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div class="card-body">
            <?php if (!empty($timelineSeries)) { ?>
              <div id="capital-flow-chart" data-series='<?=json_encode($timelineSeries); ?>' style="height:320px;"></div>
            <?php } else { ?>
              <div class="text-center text-muted py-5">No transactions yet. Execute trades to populate capital flow.</div>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="col-xl-5">
        <div class="card card-outline card-primary h-100">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Symbol Contribution</h3>
              <small class="text-muted">Net impact (realized + unrealized)</small>
            </div>
            <button class="btn btn-tool" type="button" id="refresh-symbol"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div class="card-body">
            <?php if (!empty($performanceBarSeries)) { ?>
              <div id="symbol-performance-chart" data-series='<?=json_encode($performanceBarSeries); ?>' style="height:320px;"></div>
            <?php } else { ?>
              <div class="text-center text-muted py-5">No measurable performance yet.</div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-xl-6">
        <div class="card card-outline card-success">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Top Gainers</h3>
          </div>
          <div class="card-body">
            <?php if (!empty($topGainers)) { ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($topGainers as $item) { ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <strong class="mr-2"><?=htmlspecialchars($item["symbol"]); ?></strong>
                      <span class="badge badge-success">+<?=number_format($item["total"], 2, ".", ","); ?></span>
                    </div>
                    <small class="text-muted">ROI <?=($item["roi"] !== null ? number_format($item["roi"], 2) . "%" : "—"); ?></small>
                  </li>
                <?php } ?>
              </ul>
            <?php } else { ?>
              <div class="text-center text-muted py-4">Gains will appear once positions appreciate.</div>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="col-xl-6">
        <div class="card card-outline card-danger">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Top Drawdowns</h3>
          </div>
          <div class="card-body">
            <?php if (!empty($topLosers)) { ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($topLosers as $item) { ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <strong class="mr-2"><?=htmlspecialchars($item["symbol"]); ?></strong>
                      <span class="badge badge-danger"><?=number_format($item["total"], 2, ".", ","); ?></span>
                    </div>
                    <small class="text-muted">ROI <?=($item["roi"] !== null ? number_format($item["roi"], 2) . "%" : "—"); ?></small>
                  </li>
                <?php } ?>
              </ul>
            <?php } else { ?>
              <div class="text-center text-muted py-4">No drawdowns recorded yet.</div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-dark mt-4">
      <div class="card-header border-0">
        <h3 class="card-title mb-0">Symbol Performance Breakdown</h3>
      </div>
      <div class="card-body p-0">
        <?php if (empty($symbolPerformance)) { ?>
          <div class="text-center text-muted py-5">Trading history is required to populate performance metrics.</div>
        <?php } else { ?>
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Symbol</th>
                  <th class="text-right">Capital In</th>
                  <th class="text-right">Capital Out</th>
                  <th class="text-right">Book Value</th>
                  <th class="text-right">Market Value</th>
                  <th class="text-right">Realized</th>
                  <th class="text-right">Unrealized</th>
                  <th class="text-right">Total P&amp;L</th>
                  <th class="text-right">ROI</th>
                  <th class="text-right">Trades</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($symbolPerformance as $item) { ?>
                  <tr>
                    <td><span class="badge badge-secondary badge-pill"><?=htmlspecialchars($item["symbol"]); ?></span></td>
                    <td class="text-right">$<?=number_format($item["spent"], 2, ".", ","); ?></td>
                    <td class="text-right">$<?=number_format($item["sold"], 2, ".", ","); ?></td>
                    <td class="text-right">$<?=number_format($item["book_value"], 2, ".", ","); ?></td>
                    <td class="text-right"><?=$item["market_value"] !== null ? "$" . number_format($item["market_value"], 2, ".", ",") : "—"; ?></td>
                    <td class="text-right <?=$item["realized"] >= 0 ? "text-success" : "text-danger"; ?>">
                      <?=$item["realized"] >= 0 ? "+" : "−"; ?>$<?=number_format(abs($item["realized"]), 2, ".", ","); ?>
                    </td>
                    <td class="text-right <?=$item["unrealized"] >= 0 ? "text-success" : "text-danger"; ?>">
                      <?=$item["unrealized"] >= 0 ? "+" : "−"; ?>$<?=number_format(abs($item["unrealized"]), 2, ".", ","); ?>
                    </td>
                    <td class="text-right <?=$item["total"] >= 0 ? "text-success" : "text-danger"; ?>">
                      <?=$item["total"] >= 0 ? "+" : "−"; ?>$<?=number_format(abs($item["total"]), 2, ".", ","); ?>
                    </td>
                    <td class="text-right"><?=$item["roi"] !== null ? number_format($item["roi"], 2) . "%" : "—"; ?></td>
                    <td class="text-right"><?=number_format($item["trades"]); ?></td>
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
  .border-left-success { border-left: 4px solid #28a745 !important; }
  .border-left-primary { border-left: 4px solid #007bff !important; }
  .border-left-warning { border-left: 4px solid #ffc107 !important; }
  .border-left-secondary { border-left: 4px solid #6c757d !important; }
  .progress.progress-xs { height: 4px; border-radius: 999px; background-color: rgba(0,0,0,0.05); }
  .list-group-item strong { font-size: 1rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const timelineEl = document.getElementById('capital-flow-chart');
  const symbolChartEl = document.getElementById('symbol-performance-chart');
  let timelineChart = null;
  let symbolChart = null;

  function renderTimelineChart() {
    if (!timelineEl || !timelineEl.dataset.series) return;
    const data = JSON.parse(timelineEl.dataset.series || '[]');
    if (!data.length) return;

    const categories = data.map(item => item.month);
    const contributions = data.map(item => item.contributions);
    const withdrawals = data.map(item => item.withdrawals);
    const cumulative = data.map(item => item.cumulative);

    if (timelineChart) timelineChart.destroy();

    timelineChart = new ApexCharts(timelineEl, {
      series: [
        { name: 'Contributions', type: 'column', data: contributions.map(val => -Math.abs(val)) },
        { name: 'Withdrawals', type: 'column', data: withdrawals },
        { name: 'Net Cash Flow', type: 'line', data: cumulative }
      ],
      chart: { height: 320, stacked: true, type: 'line', toolbar: { show: false } },
      stroke: { width: [0, 0, 3], curve: 'smooth' },
      plotOptions: { bar: { columnWidth: '45%' } },
      dataLabels: { enabled: false },
      xaxis: { categories },
      yaxis: [
        { labels: { formatter: val => '$' + Number(val).toLocaleString() } },
        { opposite: true, labels: { formatter: val => '$' + Number(val).toLocaleString() } }
      ],
      tooltip: {
        shared: true,
        y: {
          formatter: function (val, opts) {
            const seriesName = opts.seriesIndex === 2 ? ' net' : '';
            return '$' + Number(val).toLocaleString() + seriesName;
          }
        }
      },
      colors: ['#dc3545', '#28a745', '#17a2b8']
    });
    timelineChart.render();
  }

  function renderSymbolChart() {
    if (!symbolChartEl || !symbolChartEl.dataset.series) return;
    const data = JSON.parse(symbolChartEl.dataset.series || '[]');
    if (!data.length) return;

    const categories = data.map(item => item.symbol);
    const values = data.map(item => item.value);

    if (symbolChart) symbolChart.destroy();

    symbolChart = new ApexCharts(symbolChartEl, {
      series: [{
        name: 'Net Contribution',
        data: values
      }],
      chart: { type: 'bar', height: 320 },
      plotOptions: {
        bar: {
          horizontal: true,
          dataLabels: { position: 'center' }
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          const prefix = val >= 0 ? '+' : '−';
          return prefix + '$' + Math.abs(val).toLocaleString();
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
    symbolChart.render();
  }

  renderTimelineChart();
  renderSymbolChart();

  const refreshTimelineBtn = document.getElementById('refresh-timeline');
  if (refreshTimelineBtn) {
    refreshTimelineBtn.addEventListener('click', renderTimelineChart);
  }

  const refreshSymbolBtn = document.getElementById('refresh-symbol');
  if (refreshSymbolBtn) {
    refreshSymbolBtn.addEventListener('click', renderSymbolChart);
  }
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
