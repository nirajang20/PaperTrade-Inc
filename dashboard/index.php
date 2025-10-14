<?php
require "lib/Core.php";
require PATH_LIB . "PAGE-top.php";

$showWelcomeToast = !empty($_SESSION['welcome_back']);
if ($showWelcomeToast) {
  unset($_SESSION['welcome_back']);
}

$userId = isset($active_user['user_id']) ? (int) $active_user['user_id'] : 0;
$cashBalance = isset($active_user['user_balance']) ? (float) $active_user['user_balance'] : 0.0;

$formatCurrency = function ($value) {
  $value = (float) $value;
  $symbol = $value < 0 ? "-$" : "$";
  return $symbol . number_format(abs($value), 2, '.', ',');
};
$formatNumber = function ($value, $decimals = 2) {
  return number_format((float) $value, $decimals, '.', ',');
};

$holdings = [];
$holdingsSymbols = [];
$totalBookValue = 0.0;
$totalMarketValue = 0.0;

if ($conn && $userId > 0) {
  $stmtHoldings = $conn->prepare("
    SELECT at.symbol, h.quantity, h.avg_cost, h.updated_at
    FROM holding h
    INNER JOIN available_trade at ON at.id = h.symbol_id
    WHERE h.user_id = ?
    ORDER BY h.updated_at DESC, at.symbol ASC
  ");
  if ($stmtHoldings) {
    $stmtHoldings->bind_param("i", $userId);
    if ($stmtHoldings->execute()) {
      $stmtHoldings->bind_result($symbol, $quantity, $avgCost, $updatedAt);
      while ($stmtHoldings->fetch()) {
        $symbolUpper = strtoupper($symbol);
        $quantityValue = (float) $quantity;
        $avgCostValue = (float) $avgCost;
        $bookValue = $quantityValue * $avgCostValue;
        $holdings[] = [
          "symbol" => $symbolUpper,
          "quantity" => $quantityValue,
          "avg_cost" => $avgCostValue,
          "book_value" => $bookValue,
          "market_price" => null,
          "market_value" => null,
          "unrealized" => null,
          "change_pct" => null,
          "updated_at" => $updatedAt
        ];
        $holdingsSymbols[] = $symbolUpper;
        $totalBookValue += $bookValue;
      }
    }
    $stmtHoldings->close();
  }
}

$watchlist = [];
$watchlistSymbols = [];
if ($conn && $userId > 0) {
  $stmtWatch = $conn->prepare("
    SELECT w.watchlist_id, at.symbol, w.note, w.created_at
    FROM watchlist w
    INNER JOIN available_trade at ON at.id = w.symbol_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
    LIMIT 8
  ");
  if ($stmtWatch) {
    $stmtWatch->bind_param("i", $userId);
    if ($stmtWatch->execute()) {
      $stmtWatch->bind_result($watchId, $symbol, $note, $createdAt);
      while ($stmtWatch->fetch()) {
        $symbolUpper = strtoupper($symbol);
        $watchlist[] = [
          "id" => (int) $watchId,
          "symbol" => $symbolUpper,
          "note" => $note,
          "created_at" => $createdAt,
          "price" => null,
          "change_pct" => null
        ];
        $watchlistSymbols[] = $symbolUpper;
      }
    }
    $stmtWatch->close();
  }
}

$allSymbols = array_values(array_unique(array_merge($holdingsSymbols, $watchlistSymbols)));
if (!empty($allSymbols)) {
  $chunks = array_chunk($allSymbols, 10);
  $snapshotMap = [];
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
        $snapshotMap[strtoupper($item["symbol"])] = $item;
      }
    }
  }

  foreach ($holdings as &$holding) {
    $snapshot = $snapshotMap[$holding["symbol"]] ?? null;
    if ($snapshot) {
      if (isset($snapshot["current_price"])) {
        $holding["market_price"] = (float) $snapshot["current_price"];
        $holding["market_value"] = $holding["quantity"] * $holding["market_price"];
      }
      if (isset($snapshot["change_pct"])) {
        $holding["change_pct"] = (float) $snapshot["change_pct"];
      }
    }
    if ($holding["market_value"] === null) {
      $holding["market_value"] = $holding["book_value"];
    }
    $holding["unrealized"] = $holding["market_value"] - $holding["book_value"];
    $totalMarketValue += $holding["market_value"];
  }
  unset($holding);

  foreach ($watchlist as &$item) {
    $snapshot = $snapshotMap[$item["symbol"]] ?? null;
    if ($snapshot) {
      if (isset($snapshot["current_price"])) {
        $item["price"] = (float) $snapshot["current_price"];
      }
      if (isset($snapshot["change_pct"])) {
        $item["change_pct"] = (float) $snapshot["change_pct"];
      }
    }
  }
  unset($item);
} else {
  $totalMarketValue = 0.0;
}

$portfolioValue = $totalMarketValue + $cashBalance;
$unrealizedPnL = $totalMarketValue - $totalBookValue;
$buyingPower = $cashBalance + max(0, $totalMarketValue * 0.3);

$totalTrades = 0;
$totalFees = 0.0;
$totalBuyValue = 0.0;
$totalSellValue = 0.0;
$lastTradeAt = null;
$recentTrades = [];

if ($conn && $userId > 0) {
  $stmtTrades = $conn->prepare("
    SELECT at.symbol, t.side, t.quantity, t.price, t.fees, t.executed_at
    FROM transaction t
    INNER JOIN available_trade at ON at.id = t.symbol_id
    WHERE t.user_id = ?
    ORDER BY t.executed_at DESC
  ");
  if ($stmtTrades) {
    $stmtTrades->bind_param("i", $userId);
    if ($stmtTrades->execute()) {
      $stmtTrades->bind_result($symbol, $side, $quantity, $price, $fees, $executedAt);
      while ($stmtTrades->fetch()) {
        $totalTrades++;
        $lineValue = (float) $quantity * (float) $price;
        $feeValue = (float) $fees;
        $totalFees += $feeValue;

        if ($side === 'BUY') {
          $spend = $lineValue + $feeValue;
          $totalBuyValue += $spend;
        } else {
          $proceeds = max($lineValue - $feeValue, 0);
          $totalSellValue += $proceeds;
        }

        if (empty($lastTradeAt)) {
          $lastTradeAt = $executedAt;
        }
        if (count($recentTrades) < 6) {
          $recentTrades[] = [
            "symbol" => strtoupper($symbol),
            "side" => $side,
            "quantity" => (float) $quantity,
            "price" => (float) $price,
            "fees" => $feeValue,
            "executed_at" => $executedAt
          ];
        }

      }
    }
    $stmtTrades->close();
  }
}

$allocationSeries = [];
if (!empty($holdings)) {
  usort($holdings, function ($a, $b) {
    return ($b["market_value"] ?? 0) <=> ($a["market_value"] ?? 0);
  });
  foreach ($holdings as $holding) {
    $allocationSeries[] = [
      "symbol" => $holding["symbol"],
      "value" => round($holding["market_value"], 2)
    ];
  }
}

$netCashFlow = $totalSellValue - $totalBuyValue;
$holdingCount = count($holdings);
$watchlistCount = count($watchlistSymbols);
$watchlistUp = 0;
$watchlistDown = 0;
foreach ($watchlist as $item) {
  if ($item["change_pct"] !== null) {
    if ($item["change_pct"] >= 0) {
      $watchlistUp++;
    } else {
      $watchlistDown++;
    }
  }
}
?>

<?php if ($showWelcomeToast && !empty($active_user['user_name'])) { ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: "9000"
      };
      toastr.success("Welcome back!", "👋 Hello <?= htmlspecialchars($active_user['user_name'], ENT_QUOTES); ?>");
    });
  </script>
<?php } ?>

<section class="content pt-3">
  <div class="container-fluid">
    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-primary">
          <div class="inner">
            <h3><?=$formatCurrency($portfolioValue); ?></h3>
            <p>Total Portfolio Value</p>
          </div>
          <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-success">
          <div class="inner">
            <h3><?=$formatCurrency($cashBalance); ?></h3>
            <p>Available Cash</p>
          </div>
          <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <?php $pnlClass = $unrealizedPnL >= 0 ? "text-success" : "text-danger"; ?>
        <div class="small-box bg-light">
          <div class="inner">
            <h3 class="<?=$pnlClass; ?>"><?=$formatCurrency($unrealizedPnL); ?></h3>
            <p>Unrealized P&amp;L</p>
          </div>
          <div class="icon"><i class="fas fa-chart-line text-muted"></i></div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?=$formatCurrency($buyingPower); ?></h3>
            <p>Estimated Buying Power</p>
          </div>
          <div class="icon"><i class="fas fa-bolt"></i></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-4 col-md-6 mb-3">
        <div class="card card-outline card-secondary h-100">
          <div class="card-body">
            <h5 class="card-title mb-3">Trading Activity</h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Total trades</span>
              <span class="font-weight-bold"><?=$formatNumber($totalTrades, 0); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Last trade</span>
              <span class="font-weight-bold"><?= $lastTradeAt ? date("M d, Y · h:i A", strtotime($lastTradeAt)) : "—"; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Fees paid</span>
              <span class="font-weight-bold"><?=$formatCurrency($totalFees); ?></span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6 mb-3">
        <div class="card card-outline card-success h-100">
          <div class="card-body">
            <h5 class="card-title mb-3">Cash Flow</h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Capital deployed</span>
              <span class="font-weight-bold"><?=$formatCurrency($totalBuyValue); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Proceeds realized</span>
              <span class="font-weight-bold"><?=$formatCurrency($totalSellValue); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Net cash flow</span>
              <?php $netClass = $netCashFlow >= 0 ? "text-success" : "text-danger"; ?>
              <span class="font-weight-bold <?=$netClass; ?>"><?=$formatCurrency($netCashFlow); ?></span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-12 mb-3">
        <div class="card card-outline card-info h-100">
          <div class="card-body">
            <h5 class="card-title mb-3">Portfolio Snapshot</h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Active holdings</span>
              <span class="font-weight-bold"><?=$formatNumber($holdingCount, 0); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Watchlist symbols</span>
              <span class="font-weight-bold"><?=$formatNumber($watchlistCount, 0); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Watchlist trend</span>
              <span class="font-weight-bold">
                <span class="text-success mr-2">↑ <?=$formatNumber($watchlistUp, 0); ?></span>
                <span class="text-danger">↓ <?=$formatNumber($watchlistDown, 0); ?></span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 mb-3">
        <div class="card card-outline card-dark h-100">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Performance Timeline</h3>
            <small class="text-muted">Live portfolio value · updates every 5 seconds</small>
          </div>
          <div class="card-body">
            <div id="performanceChart" style="height: 340px;"></div>
            <div class="d-flex justify-content-between mt-3 small text-muted">
              <span>Current value: <span class="font-weight-semibold text-dark" id="performanceCurrentValue">—</span></span>
              <span>Last update: <span class="font-weight-semibold text-dark" id="performanceLastUpdate">—</span></span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 mb-3">
        <div class="card card-outline card-primary h-100">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Allocation Breakdown</h3>
          </div>
          <div class="card-body">
            <div class="position-relative mb-3" style="height: 260px;">
              <canvas id="allocationChart"></canvas>
            </div>
            <?php if (!empty($holdings)) { ?>
              <ul class="list-unstyled small mb-0">
                <?php foreach (array_slice($holdings, 0, 5) as $holding) {
                  $weight = $totalMarketValue > 0 ? ($holding["market_value"] / $totalMarketValue) * 100 : 0;
                  ?>
                  <li class="mb-2 d-flex justify-content-between align-items-center">
                    <span><?=htmlspecialchars($holding["symbol"]); ?></span>
                    <span><?=$formatNumber($weight, 1); ?>%</span>
                  </li>
                <?php } ?>
              </ul>
            <?php } else { ?>
              <div class="text-muted text-center small">No holdings to display.</div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 mb-3">
        <div class="card card-outline card-info h-100">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Holdings</h3>
          </div>
          <div class="card-body p-0">
            <?php if (empty($holdings)) { ?>
              <div class="text-center text-muted py-5">No holdings yet. Execute your first trade to start building a portfolio.</div>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Symbol</th>
                      <th class="text-right">Quantity</th>
                      <th class="text-right">Avg Cost</th>
                      <th class="text-right">Last Price</th>
                      <th class="text-right">Market Value</th>
                      <th class="text-right">Unrealized</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($holdings as $holding) {
                      $unrealClass = ($holding["unrealized"] ?? 0) >= 0 ? "text-success" : "text-danger";
                      ?>
                      <tr>
                        <td><span class="badge badge-secondary badge-pill"><?=htmlspecialchars($holding["symbol"]); ?></span></td>
                        <td class="text-right"><?=$formatNumber($holding["quantity"], 4); ?></td>
                        <td class="text-right"><?=$formatCurrency($holding["avg_cost"]); ?></td>
                        <td class="text-right"><?=$holding["market_price"] !== null ? $formatCurrency($holding["market_price"]) : "—"; ?></td>
                        <td class="text-right"><?=$formatCurrency($holding["market_value"]); ?></td>
                        <td class="text-right <?=$unrealClass; ?>"><?=$formatCurrency($holding["unrealized"]); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="col-lg-4 mb-3">
        <div class="card card-outline card-success h-100">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Watchlist</h3>
            <a href="<?=URL_HOST?>portfolio/watchlist.php" class="text-muted small">Manage</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($watchlist)) { ?>
              <div class="text-center text-muted py-5">Add symbols to your watchlist to monitor them here.</div>
            <?php } else { ?>
              <ul class="list-group list-group-flush" id="watchlist">
                <?php foreach ($watchlist as $item) {
                  $change = $item["change_pct"];
                  $changeClass = $change === null ? "badge-secondary" : ($change >= 0 ? "badge-success" : "badge-danger");
                  $changeText = $change === null ? "—" : (($change >= 0 ? "+" : "") . $formatNumber($change, 2) . "%");
                  ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <strong><?=htmlspecialchars($item["symbol"]); ?></strong>
                      <?php if (!empty($item["note"])) { ?>
                        <div class="small text-muted"><?=htmlspecialchars($item["note"]); ?></div>
                      <?php } ?>
                      <div class="small text-muted"><?=!empty($item["created_at"]) ? date("M d, Y", strtotime($item["created_at"])) : ""; ?></div>
                    </div>
                    <div class="text-right">
                      <div class="font-weight-bold"><?=$item["price"] !== null ? $formatCurrency($item["price"]) : "—"; ?></div>
                      <span class="badge <?=$changeClass; ?>"><?=$changeText; ?></span>
                    </div>
                  </li>
                <?php } ?>
              </ul>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12 mb-3">
        <div class="card card-outline card-dark">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Recent Trades</h3>
            <a href="<?=URL_HOST?>trading/transactions.php" class="text-muted small">View full history</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($recentTrades)) { ?>
              <div class="text-center text-muted py-5">You haven't executed any trades yet.</div>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-hover mb-0">
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
                    <?php foreach ($recentTrades as $trade) {
                      $sideBadge = $trade["side"] === "BUY" ? "badge-success" : "badge-danger";
                      $lineValue = $trade["quantity"] * $trade["price"];
                      $netAmount = $trade["side"] === "BUY"
                        ? -($lineValue + $trade["fees"])
                        : ($lineValue - $trade["fees"]);
                      $netClass = $netAmount >= 0 ? "text-success" : "text-danger";
                      ?>
                      <tr>
                        <td><?=date("M d, Y · h:i A", strtotime($trade["executed_at"])); ?></td>
                        <td><?=htmlspecialchars($trade["symbol"]); ?></td>
                        <td><span class="badge <?=$sideBadge; ?>"><?=$trade["side"]; ?></span></td>
                        <td class="text-right"><?=$formatNumber($trade["quantity"], 4); ?></td>
                        <td class="text-right"><?=$formatCurrency($trade["price"]); ?></td>
                        <td class="text-right"><?=$formatCurrency($trade["fees"]); ?></td>
                        <td class="text-right <?=$netClass; ?>"><?=$formatCurrency($netAmount); ?></td>
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
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const allocationSeries = <?=json_encode($allocationSeries); ?>;

  const performanceChartEl = document.getElementById("performanceChart");
  const allocationCtx = document.getElementById("allocationChart");
  const liveValueEl = document.getElementById("performanceCurrentValue");
  const lastUpdateEl = document.getElementById("performanceLastUpdate");

  const portfolioEndpoint = new URL("ajax-portfolio-worth.php", window.location.href).toString();
  const POLL_INTERVAL = 5000;
  const MAX_POINTS = 120;

  let pollTimer = null;
  let portfolioChart = null;

  const chartState = {
    points: []
  };

  const formatCurrency = (value) => {
    if (!Number.isFinite(value)) {
      return "$0.00";
    }
    return "$" + value.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  const ensureChart = () => {
    if (portfolioChart || !performanceChartEl) {
      return;
    }

    const options = {
      chart: {
        type: "area",
        height: 320,
        animations: { enabled: false },
        toolbar: { show: false },
        sparkline: { enabled: false }
      },
      dataLabels: { enabled: false },
      stroke: { curve: "smooth", width: 3 },
      series: [{ name: "Portfolio Value", data: chartState.points }],
      colors: ["#17a2b8"],
      fill: {
        type: "gradient",
        gradient: {
          shadeIntensity: 0.6,
          opacityFrom: 0.35,
          opacityTo: 0.05,
          stops: [0, 90, 100]
        }
      },
      grid: {
        borderColor: "rgba(108,117,125,0.12)",
        strokeDashArray: 3
      },
      xaxis: {
        type: "datetime",
        labels: { style: { colors: "#6c757d" } },
        axisBorder: { color: "rgba(108,117,125,0.2)" },
        axisTicks: { color: "rgba(108,117,125,0.2)" }
      },
      yaxis: {
        labels: {
          style: { colors: "#6c757d" },
          formatter: (value) => "$" + Number(value).toLocaleString()
        }
      },
      tooltip: {
        x: { format: "MMM dd HH:mm:ss" },
        y: {
          formatter: (value) => formatCurrency(Number(value))
        }
      },
      noData: {
        text: "Awaiting live data…",
        align: "center"
      }
    };

    performanceChartEl.innerHTML = "";
    portfolioChart = new ApexCharts(performanceChartEl, options);
    portfolioChart.render();
  };

  const appendPoint = (timestamp, value) => {
    const ts = timestamp ? new Date(timestamp) : new Date();
    const point = { x: ts.getTime(), y: value };
    chartState.points.push(point);

    while (chartState.points.length > MAX_POINTS) {
      chartState.points.shift();
    }

    ensureChart();
    if (portfolioChart) {
      portfolioChart.updateSeries([{ name: "Portfolio Value", data: chartState.points.slice() }], false);
    }
  };

  const clearPollTimer = () => {
    if (pollTimer) {
      clearTimeout(pollTimer);
      pollTimer = null;
    }
  };

  const scheduleNextPoll = () => {
    clearPollTimer();
    pollTimer = setTimeout(refreshPortfolioWorth, POLL_INTERVAL);
  };

  const fetchPortfolioWorth = async () => {
    const response = await fetch(portfolioEndpoint, {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });
    if (!response.ok) {
      const error = new Error("Request failed with status " + response.status);
      error.status = response.status;
      throw error;
    }
    const payload = await response.json();
    if (!payload || payload.status !== "success") {
      const error = new Error(payload && payload.message ? payload.message : "Unexpected response");
      error.status = response.status;
      throw error;
    }
    return payload;
  };

  const refreshPortfolioWorth = async () => {
    try {
      const data = await fetchPortfolioWorth();
      const portfolioValue = Number(data.portfolioValue ?? data.totalValue ?? data.value ?? 0);
      const sanitizedValue = Number.isFinite(portfolioValue) ? portfolioValue : 0;
      const timestamp = data.timestamp || new Date().toISOString();

      appendPoint(timestamp, sanitizedValue);

      if (liveValueEl) {
        liveValueEl.textContent = formatCurrency(sanitizedValue);
      }
      if (lastUpdateEl) {
        const dt = new Date(timestamp);
        lastUpdateEl.textContent = dt.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
      }
    } catch (error) {
      console.error("Unable to refresh portfolio worth:", error);
      if (lastUpdateEl) {
        lastUpdateEl.textContent = error.status === 401 ? "Session expired" : "Error";
      }
      if (window.toastr) {
        window.toastr.error("Live portfolio update failed" + (error.message ? `: ${error.message}` : ""));
      }
      if (error.status === 401) {
        clearPollTimer();
        return;
      }
    } finally {
      scheduleNextPoll();
    }
  };

  if (performanceChartEl) {
    ensureChart();
    refreshPortfolioWorth();
  }

  if (allocationCtx && allocationSeries.length) {
    const labels = allocationSeries.map(item => item.symbol);
    const data = allocationSeries.map(item => Number(item.value || 0));

    new Chart(allocationCtx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: ['#007bff', '#dc3545', '#17a2b8', '#ffc107', '#28a745', '#6610f2', '#6c757d', '#20c997', '#fd7e14', '#e83e8c'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.label + ': $' + Number(context.parsed).toLocaleString(undefined, { maximumFractionDigits: 2 });
              }
            }
          }
        }
      }
    });
  }

  window.addEventListener("beforeunload", clearPollTimer);
});
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
