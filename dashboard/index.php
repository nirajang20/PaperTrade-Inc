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
    $apiUrl = "https://api.paper-trade.org/buy-sell-info?symbols=" . $query;
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

<style>
  /* ═══════════════════════════════════════════
     TRADING DASHBOARD — Design System
     Light mode by default, dark via body.dark-mode
  ═══════════════════════════════════════════ */
  :root {
    /* ── Light mode defaults ── */
    --td-bg-page: #f4f6f9;
    --td-bg-card: #ffffff;
    --td-bg-card-hover: #f8fafc;
    --td-border: #e2e8f0;
    --td-border-accent: #cbd5e1;
    --td-green: #059669;
    --td-green-dim: rgba(5,150,105,0.08);
    --td-green-border: rgba(5,150,105,0.3);
    --td-red: #dc2626;
    --td-red-dim: rgba(220,38,38,0.08);
    --td-red-border: rgba(220,38,38,0.3);
    --td-blue: #2563eb;
    --td-blue-dim: rgba(37,99,235,0.08);
    --td-blue-border: rgba(37,99,235,0.25);
    --td-yellow: #d97706;
    --td-yellow-dim: rgba(217,119,6,0.08);
    --td-purple: #7c3aed;
    --td-purple-dim: rgba(124,58,237,0.08);
    --td-text-primary: #0f172a;
    --td-text-secondary: #64748b;
    --td-text-muted: #94a3b8;
    --td-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
    --td-radius: 10px;
    --td-card-header-bg: rgba(0,0,0,0.02);
    --td-table-header-bg: rgba(0,0,0,0.02);
    --td-row-hover-bg: rgba(0,0,0,0.025);
    --td-row-border: rgba(0,0,0,0.07);
    --td-chart-footer-bg: rgba(0,0,0,0.02);
    --td-metric-icon-color: #1e293b;
    --td-chart-color: #2563eb;
    --td-chart-fg: #64748b;
    --td-chart-grid: rgba(0,0,0,0.07);
  }

  /* ── Dark mode overrides (synced with body.dark-mode toggle) ── */
  body.dark-mode {
    --td-bg-page: #090d13;
    --td-bg-card: #0d1117;
    --td-bg-card-hover: #161b22;
    --td-border: rgba(48,54,61,0.8);
    --td-border-accent: rgba(48,54,61,1);
    --td-green: #00d084;
    --td-green-dim: rgba(0,208,132,0.12);
    --td-green-border: rgba(0,208,132,0.3);
    --td-red: #ff4d6d;
    --td-red-dim: rgba(255,77,109,0.12);
    --td-red-border: rgba(255,77,109,0.3);
    --td-blue: #4493f8;
    --td-blue-dim: rgba(68,147,248,0.12);
    --td-blue-border: rgba(68,147,248,0.25);
    --td-yellow: #e3b341;
    --td-yellow-dim: rgba(227,179,65,0.12);
    --td-purple: #a371f7;
    --td-purple-dim: rgba(163,113,247,0.12);
    --td-text-primary: #e6edf3;
    --td-text-secondary: #7d8590;
    --td-text-muted: #484f58;
    --td-shadow: 0 1px 3px rgba(0,0,0,0.4), 0 4px 16px rgba(0,0,0,0.3);
    --td-card-header-bg: rgba(255,255,255,0.02);
    --td-table-header-bg: rgba(255,255,255,0.02);
    --td-row-hover-bg: rgba(255,255,255,0.025);
    --td-row-border: rgba(48,54,61,0.4);
    --td-chart-footer-bg: rgba(255,255,255,0.015);
    --td-metric-icon-color: #ffffff;
    --td-chart-color: #4493f8;
    --td-chart-fg: #7d8590;
    --td-chart-grid: rgba(48,54,61,0.6);
  }

  /* Base reset for dashboard section */
  .td-section {
    background: var(--td-bg-page);
    min-height: 100vh;
    padding: 1.25rem 1.5rem 2rem;
    font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
  }

  /* Page Header */
  .td-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--td-border);
  }
  .td-page-header h1 {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--td-text-primary);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin: 0;
  }
  .td-page-header .td-date {
    font-size: 0.78rem;
    color: var(--td-text-secondary);
  }
  .td-live-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--td-green-dim);
    border: 1px solid var(--td-green-border);
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--td-green);
    letter-spacing: 0.05em;
  }
  .td-live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--td-green);
    box-shadow: 0 0 8px var(--td-green);
    animation: tdPulse 1.5s ease-in-out infinite;
  }
  .td-live-dot.paused {
    background: var(--td-red);
    box-shadow: none;
    animation: none;
  }
  @keyframes tdPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
  }

  /* ── Metric Cards ── */
  .td-metrics-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
  }
  @media (max-width: 1100px) { .td-metrics-row { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 600px)  { .td-metrics-row { grid-template-columns: 1fr; } }

  .td-metric-card {
    background: var(--td-bg-card);
    border: 1px solid var(--td-border);
    border-radius: var(--td-radius);
    padding: 1.1rem 1.25rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--td-shadow);
    transition: border-color 0.2s, background 0.2s;
  }
  .td-metric-card:hover {
    background: var(--td-bg-card-hover);
    border-color: var(--td-border-accent);
  }
  .td-metric-card::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    border-radius: var(--td-radius) var(--td-radius) 0 0;
  }
  .td-metric-card.accent-blue::before  { background: var(--td-blue); }
  .td-metric-card.accent-green::before { background: var(--td-green); }
  .td-metric-card.accent-red::before   { background: var(--td-red); }
  .td-metric-card.accent-yellow::before { background: var(--td-yellow); }
  .td-metric-card.accent-purple::before { background: var(--td-purple); }

  .td-metric-label {
    font-size: 0.69rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--td-text-secondary);
    margin-bottom: 0.55rem;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .td-metric-label i {
    font-size: 0.75rem;
  }
  .td-metric-value {
    font-size: 1.65rem;
    font-weight: 700;
    color: var(--td-text-primary);
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
    transition: color 0.3s;
  }
  .td-metric-value.positive { color: var(--td-green); }
  .td-metric-value.negative { color: var(--td-red); }

  .td-metric-delta {
    font-size: 0.73rem;
    margin-top: 0.35rem;
    color: var(--td-text-secondary);
    min-height: 1.1em;
    font-variant-numeric: tabular-nums;
  }
  .td-metric-delta.positive { color: var(--td-green); }
  .td-metric-delta.negative { color: var(--td-red); }

  .td-metric-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 2.2rem;
    opacity: 0.07;
    color: var(--td-metric-icon-color);
  }

  /* metric flash animation */
  .metric-number { transition: color 0.3s ease; position: relative; }
  .metric-number.metric-flash::after {
    content: "";
    position: absolute;
    inset: -0.15em 0;
    background: rgba(68, 147, 248, 0.18);
    border-radius: 0.35rem;
    animation: metricFlash 0.9s ease forwards;
    pointer-events: none;
  }
  @keyframes metricFlash {
    0% { opacity: 1; } 100% { opacity: 0; }
  }

  /* ── Stat Row (3 info panels) ── */
  .td-stat-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
  }
  @media (max-width: 900px) { .td-stat-row { grid-template-columns: 1fr; } }

  .td-stat-card {
    background: var(--td-bg-card);
    border: 1px solid var(--td-border);
    border-radius: var(--td-radius);
    padding: 1rem 1.25rem;
    box-shadow: var(--td-shadow);
  }
  .td-stat-card-title {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--td-text-secondary);
    margin-bottom: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .td-stat-row-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.42rem 0;
    border-bottom: 1px solid var(--td-row-border);
  }
  .td-stat-row-item:last-child { border-bottom: none; }
  .td-stat-row-item .label {
    font-size: 0.78rem;
    color: var(--td-text-secondary);
  }
  .td-stat-row-item .value {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--td-text-primary);
    font-variant-numeric: tabular-nums;
  }
  .td-stat-row-item .value.positive { color: var(--td-green); }
  .td-stat-row-item .value.negative { color: var(--td-red); }

  /* ── Main content grid ── */
  .td-main-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1rem;
    margin-bottom: 1.25rem;
  }
  @media (max-width: 1000px) { .td-main-grid { grid-template-columns: 1fr; } }

  /* ── Generic Card ── */
  .td-card {
    background: var(--td-bg-card);
    border: 1px solid var(--td-border);
    border-radius: var(--td-radius);
    box-shadow: var(--td-shadow);
    overflow: hidden;
  }
  .td-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--td-border);
    background: var(--td-card-header-bg);
  }
  .td-card-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--td-text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .td-card-title i { opacity: 0.7; }
  .td-card-body { padding: 1rem 1.25rem; }
  .td-card-body-flush { padding: 0; }

  .td-card-link {
    font-size: 0.72rem;
    color: var(--td-blue);
    text-decoration: none;
    letter-spacing: 0.04em;
    font-weight: 600;
    opacity: 0.8;
    transition: opacity 0.2s;
  }
  .td-card-link:hover { opacity: 1; color: var(--td-blue); text-decoration: none; }

  /* ── Chart Area ── */
  #performanceChart { height: 320px; }

  .td-chart-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.7rem 1.25rem;
    border-top: 1px solid var(--td-border);
    background: var(--td-chart-footer-bg);
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .td-chart-footer-item {
    font-size: 0.73rem;
    color: var(--td-text-secondary);
  }
  .td-chart-footer-item strong {
    color: var(--td-text-primary);
    font-variant-numeric: tabular-nums;
  }

  /* Timeline controls */
  .td-range-group {
    display: flex;
    gap: 4px;
  }
  .td-range-btn {
    background: transparent;
    border: 1px solid var(--td-border);
    border-radius: 6px;
    color: var(--td-text-secondary);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    cursor: pointer;
    letter-spacing: 0.04em;
    transition: all 0.15s;
    font-family: inherit;
  }
  .td-range-btn:hover { border-color: var(--td-blue); color: var(--td-blue); }
  .td-range-btn.active {
    background: var(--td-blue-dim);
    border-color: var(--td-blue);
    color: var(--td-blue);
  }
  .td-live-toggle {
    background: transparent;
    border: 1px solid var(--td-green);
    border-radius: 6px;
    color: var(--td-green);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    cursor: pointer;
    letter-spacing: 0.04em;
    transition: all 0.15s;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  .td-live-toggle:hover { background: var(--td-green-dim); }
  .td-live-toggle.paused {
    border-color: var(--td-text-muted);
    color: var(--td-text-secondary);
  }

  /* ── Allocation Chart Panel ── */
  .td-allocation-legend {
    margin-top: 0.75rem;
  }
  .td-alloc-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.3rem 0;
    border-bottom: 1px solid var(--td-row-border);
  }
  .td-alloc-item:last-child { border-bottom: none; }
  .td-alloc-dot {
    width: 8px; height: 8px;
    border-radius: 2px;
    flex-shrink: 0;
    margin-right: 8px;
  }
  .td-alloc-symbol {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--td-text-primary);
    flex: 1;
  }
  .td-alloc-pct {
    font-size: 0.75rem;
    color: var(--td-text-secondary);
    font-variant-numeric: tabular-nums;
  }

  /* ── Tables ── */
  .td-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
  }
  .td-table thead th {
    padding: 0.6rem 1rem;
    font-size: 0.67rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--td-text-secondary);
    border-bottom: 1px solid var(--td-border);
    background: var(--td-table-header-bg);
    white-space: nowrap;
  }
  .td-table tbody tr {
    border-bottom: 1px solid var(--td-row-border);
    transition: background 0.12s;
  }
  .td-table tbody tr:last-child { border-bottom: none; }
  .td-table tbody tr:hover { background: var(--td-row-hover-bg); }
  .td-table td {
    padding: 0.65rem 1rem;
    color: var(--td-text-primary);
    vertical-align: middle;
    white-space: nowrap;
  }
  .td-table td.right { text-align: right; }
  .td-table td.muted { color: var(--td-text-secondary); font-size: 0.74rem; }
  .td-table td.positive { color: var(--td-green); font-weight: 600; }
  .td-table td.negative { color: var(--td-red); font-weight: 600; }

  /* Symbol badge */
  .td-symbol {
    display: inline-block;
    background: var(--td-blue-dim);
    border: 1px solid var(--td-blue-border);
    color: var(--td-blue);
    border-radius: 5px;
    padding: 1px 7px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
  }

  /* Side badge */
  .td-side-buy {
    display: inline-block;
    background: var(--td-green-dim);
    border: 1px solid var(--td-green-border);
    color: var(--td-green);
    border-radius: 5px;
    padding: 1px 8px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
  }
  .td-side-sell {
    display: inline-block;
    background: var(--td-red-dim);
    border: 1px solid var(--td-red-border);
    color: var(--td-red);
    border-radius: 5px;
    padding: 1px 8px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
  }

  /* ── Watchlist Panel ── */
  .td-watchlist-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--td-row-border);
    transition: background 0.12s;
  }
  .td-watchlist-item:last-child { border-bottom: none; }
  .td-watchlist-item:hover { background: var(--td-row-hover-bg); }
  .td-watchlist-sym {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--td-text-primary);
  }
  .td-watchlist-note {
    font-size: 0.72rem;
    color: var(--td-text-secondary);
    margin-top: 2px;
  }
  .td-watchlist-price {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--td-text-primary);
    font-variant-numeric: tabular-nums;
    text-align: right;
  }
  .td-change-badge {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 700;
    border-radius: 4px;
    padding: 1px 6px;
    font-variant-numeric: tabular-nums;
    margin-top: 3px;
    letter-spacing: 0.04em;
  }
  .td-change-badge.up {
    background: var(--td-green-dim);
    color: var(--td-green);
    border: 1px solid var(--td-green-border);
  }
  .td-change-badge.down {
    background: var(--td-red-dim);
    color: var(--td-red);
    border: 1px solid var(--td-red-border);
  }
  .td-change-badge.flat {
    background: rgba(125,133,144,0.12);
    color: var(--td-text-secondary);
    border: 1px solid rgba(125,133,144,0.2);
  }

  /* ── News Panel ── */
  .td-news-status {
    font-size: 0.7rem;
    color: var(--td-text-secondary);
  }
  #portfolioNewsList { min-height: 60px; }

  /* ── Holdings & Trades layout ── */
  .td-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1rem;
    margin-bottom: 1.25rem;
  }
  @media (max-width: 1000px) { .td-bottom-grid { grid-template-columns: 1fr; } }

  /* ── Empty states ── */
  .td-empty {
    padding: 3rem 1rem;
    text-align: center;
    color: var(--td-text-muted);
    font-size: 0.82rem;
  }
  .td-empty i {
    display: block;
    font-size: 2rem;
    margin-bottom: 0.75rem;
    opacity: 0.3;
  }

  /* ── Quick Actions ── */
  .td-quick-actions {
    display: flex;
    gap: 0.5rem;
  }
  .td-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 6px;
    font-size: 0.73rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
  }
  .td-btn-buy {
    background: var(--td-green-dim);
    border-color: var(--td-green-border);
    color: var(--td-green);
  }
  .td-btn-buy:hover {
    background: var(--td-green-dim);
    filter: brightness(1.1);
    color: var(--td-green);
    text-decoration: none;
  }
  .td-btn-sell {
    background: var(--td-red-dim);
    border-color: var(--td-red-border);
    color: var(--td-red);
  }
  .td-btn-sell:hover {
    background: var(--td-red-dim);
    filter: brightness(1.1);
    color: var(--td-red);
    text-decoration: none;
  }

  /* Responsive scroll for tables */
  .td-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

  /* Sync content-wrapper background with theme */
  .content-wrapper { background: var(--td-bg-page) !important; }

  /* Scrollbar styling */
  .td-table-wrap::-webkit-scrollbar { height: 4px; }
  .td-table-wrap::-webkit-scrollbar-track { background: transparent; }
  .td-table-wrap::-webkit-scrollbar-thumb { background: var(--td-border); border-radius: 2px; }
</style>

<section class="td-section">

  <!-- Page Header -->
  <div class="td-page-header">
    <div>
      <h1><i class="fas fa-chart-line" style="color:var(--td-blue);margin-right:8px;font-size:0.9em;"></i>Trading Dashboard</h1>
      <div class="td-date"><?= date("l, F j, Y · h:i A T"); ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <div class="td-live-pill">
        <span class="td-live-dot" id="performanceLiveDot"></span>
        <span id="performanceLiveStatus">LIVE</span>
      </div>
      <div class="td-quick-actions">
        <a href="<?=URL_HOST?>trading/buy.php" class="td-btn td-btn-buy"><i class="fas fa-arrow-up"></i> Buy</a>
        <a href="<?=URL_HOST?>trading/sell.php" class="td-btn td-btn-sell"><i class="fas fa-arrow-down"></i> Sell</a>
      </div>
    </div>
  </div>

  <!-- ── Metric Cards ── -->
  <div class="td-metrics-row">

    <!-- Portfolio Value -->
    <div class="td-metric-card accent-blue">
      <div class="td-metric-label"><i class="fas fa-layer-group" style="color:var(--td-blue)"></i> Portfolio Value</div>
      <div class="td-metric-value metric-number"
        id="metricPortfolioValue"
        data-animated-number
        data-format="currency"
        data-value="<?=htmlspecialchars($portfolioValue, ENT_QUOTES); ?>"
        data-metric="portfolioValue"
        data-delta-target="portfolioValueDelta">
        <?=$formatCurrency($portfolioValue); ?>
      </div>
      <div class="td-metric-delta" id="portfolioValueDelta"></div>
      <i class="fas fa-layer-group td-metric-icon"></i>
    </div>

    <!-- Cash Balance -->
    <div class="td-metric-card accent-green">
      <div class="td-metric-label"><i class="fas fa-wallet" style="color:var(--td-green)"></i> Cash Available</div>
      <div class="td-metric-value"><?=$formatCurrency($cashBalance); ?></div>
      <div class="td-metric-delta" style="color:var(--td-text-muted)">Ready to deploy</div>
      <i class="fas fa-wallet td-metric-icon"></i>
    </div>

    <!-- Unrealized P&L -->
    <?php $pnlPositive = $unrealizedPnL >= 0; ?>
    <div class="td-metric-card <?=$pnlPositive ? 'accent-green' : 'accent-red'; ?>">
      <div class="td-metric-label">
        <i class="fas fa-chart-line" style="color:<?=$pnlPositive ? 'var(--td-green)' : 'var(--td-red)'; ?>"></i>
        Unrealized P&amp;L
      </div>
      <div class="td-metric-value <?=$pnlPositive ? 'positive' : 'negative'; ?> metric-number"
        id="metricUnrealized"
        data-animated-number
        data-format="currency"
        data-value="<?=htmlspecialchars($unrealizedPnL, ENT_QUOTES); ?>"
        data-metric="unrealizedPnl"
        data-positive-class="positive"
        data-negative-class="negative">
        <?=$formatCurrency($unrealizedPnL); ?>
      </div>
      <div class="td-metric-delta" style="color:var(--td-text-muted)">
        <?php if ($totalBookValue > 0) {
          $pnlPct = ($unrealizedPnL / $totalBookValue) * 100;
          echo ($pnlPct >= 0 ? "+" : "") . number_format($pnlPct, 2) . "% return";
        } else {
          echo "No cost basis";
        } ?>
      </div>
      <i class="fas fa-chart-line td-metric-icon"></i>
    </div>

    <!-- Buying Power -->
    <div class="td-metric-card accent-yellow">
      <div class="td-metric-label"><i class="fas fa-bolt" style="color:var(--td-yellow)"></i> Buying Power</div>
      <div class="td-metric-value"><?=$formatCurrency($buyingPower); ?></div>
      <div class="td-metric-delta" style="color:var(--td-text-muted)">Cash + margin estimate</div>
      <i class="fas fa-bolt td-metric-icon"></i>
    </div>

  </div>

  <!-- ── Stat Panels ── -->
  <div class="td-stat-row">

    <!-- Trading Activity -->
    <div class="td-stat-card">
      <div class="td-stat-card-title"><i class="fas fa-exchange-alt" style="color:var(--td-blue)"></i> Trading Activity</div>
      <div class="td-stat-row-item">
        <span class="label">Total trades executed</span>
        <span class="value"><?=$formatNumber($totalTrades, 0); ?></span>
      </div>
      <div class="td-stat-row-item">
        <span class="label">Last trade</span>
        <span class="value" style="font-size:0.74rem;"><?= $lastTradeAt ? date("M d, Y · h:i A", strtotime($lastTradeAt)) : "—"; ?></span>
      </div>
      <div class="td-stat-row-item">
        <span class="label">Total fees paid</span>
        <span class="value negative"><?=$formatCurrency($totalFees); ?></span>
      </div>
    </div>

    <!-- Cash Flow -->
    <div class="td-stat-card">
      <div class="td-stat-card-title"><i class="fas fa-money-bill-wave" style="color:var(--td-green)"></i> Cash Flow</div>
      <div class="td-stat-row-item">
        <span class="label">Capital deployed</span>
        <span class="value negative"><?=$formatCurrency($totalBuyValue); ?></span>
      </div>
      <div class="td-stat-row-item">
        <span class="label">Proceeds realized</span>
        <span class="value positive"><?=$formatCurrency($totalSellValue); ?></span>
      </div>
      <div class="td-stat-row-item">
        <span class="label">Net cash flow</span>
        <?php $netClass = $netCashFlow >= 0 ? "positive" : "negative"; ?>
        <span class="value <?=$netClass; ?>"><?=$formatCurrency($netCashFlow); ?></span>
      </div>
    </div>

    <!-- Portfolio Snapshot -->
    <div class="td-stat-card">
      <div class="td-stat-card-title"><i class="fas fa-briefcase" style="color:var(--td-purple)"></i> Portfolio Snapshot</div>
      <div class="td-stat-row-item">
        <span class="label">Active holdings</span>
        <span class="value"><?=$formatNumber($holdingCount, 0); ?></span>
      </div>
      <div class="td-stat-row-item">
        <span class="label">Watchlist symbols</span>
        <span class="value"><?=$formatNumber($watchlistCount, 0); ?></span>
      </div>
      <div class="td-stat-row-item">
        <span class="label">Watchlist trend</span>
        <span class="value">
          <span style="color:var(--td-green);margin-right:8px;">&#9650; <?=$formatNumber($watchlistUp, 0); ?></span>
          <span style="color:var(--td-red);">&#9660; <?=$formatNumber($watchlistDown, 0); ?></span>
        </span>
      </div>
    </div>

  </div>

  <!-- ── Performance Chart + Allocation ── -->
  <div class="td-main-grid">

    <!-- Performance Timeline -->
    <div class="td-card">
      <div class="td-card-header">
        <div class="td-card-title" style="flex-direction:column;align-items:flex-start;gap:4px;">
          <span><i class="fas fa-chart-area"></i> Performance Timeline</span>
          <span style="font-size:0.67rem;font-weight:400;letter-spacing:0.04em;color:var(--td-text-secondary);text-transform:none;">
            Updates every 5 seconds &nbsp;&middot;&nbsp;
            <span id="performanceRangeLabel" style="color:var(--td-blue);">Last 15m</span>
          </span>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
          <div class="td-range-group" role="group" aria-label="Timeline range">
            <button class="td-range-btn active" data-range-filter="15">15m</button>
            <button class="td-range-btn" data-range-filter="60">1h</button>
            <button class="td-range-btn" data-range-filter="240">4h</button>
            <button class="td-range-btn" data-range-filter="all">All</button>
          </div>
          <button class="td-live-toggle" id="performanceLiveToggle" data-live="on">
            <i class="fas fa-pause"></i><span class="live-toggle-label">Pause</span>
          </button>
        </div>
      </div>
      <div class="td-card-body-flush" style="padding:1rem 1rem 0;">
        <div id="performanceChart"></div>
      </div>
      <div class="td-chart-footer">
        <div class="td-chart-footer-item">
          Current &nbsp;<strong
            class="metric-number"
            id="performanceCurrentValue"
            data-animated-number
            data-format="currency"
            data-value="<?=htmlspecialchars($portfolioValue, ENT_QUOTES); ?>">
            <?=$formatCurrency($portfolioValue); ?>
          </strong>
        </div>
        <div class="td-chart-footer-item">Last update &nbsp;<strong id="performanceLastUpdate">—</strong></div>
      </div>
    </div>

    <!-- Allocation Breakdown -->
    <div class="td-card">
      <div class="td-card-header">
        <div class="td-card-title"><i class="fas fa-chart-pie"></i> Allocation</div>
      </div>
      <div class="td-card-body">
        <div style="position:relative;height:220px;">
          <canvas id="allocationChart"></canvas>
          <?php if (empty($holdings)) { ?>
            <div class="td-empty" style="padding:1rem 0;position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
              <i class="fas fa-chart-pie"></i>No holdings
            </div>
          <?php } ?>
        </div>
        <?php if (!empty($holdings)) { ?>
          <div class="td-allocation-legend">
            <?php
            $allocColors = ['#4493f8','#ff4d6d','#00d084','#e3b341','#a371f7','#fb8500','#06d6a0','#ef476f','#ffd166','#118ab2'];
            foreach (array_slice($holdings, 0, 5) as $i => $holding) {
              $weight = $totalMarketValue > 0 ? ($holding["market_value"] / $totalMarketValue) * 100 : 0;
              ?>
              <div class="td-alloc-item">
                <span class="td-alloc-dot" style="background:<?=$allocColors[$i % count($allocColors)]; ?>;"></span>
                <span class="td-alloc-symbol"><?=htmlspecialchars($holding["symbol"]); ?></span>
                <span class="td-alloc-pct"><?=$formatNumber($weight, 1); ?>%</span>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>

  </div>

  <!-- ── Market News ── -->
  <div class="td-card" style="margin-bottom:1.25rem;">
    <div class="td-card-header">
      <div class="td-card-title"><i class="fas fa-newspaper"></i> Market News</div>
      <span class="td-news-status" id="portfolioNewsStatus">Pending</span>
    </div>
    <div class="td-card-body-flush" id="portfolioNewsList">
      <div class="td-empty"><i class="fas fa-spinner fa-spin"></i>Loading portfolio news…</div>
    </div>
  </div>

  <!-- ── Holdings + Watchlist ── -->
  <div class="td-bottom-grid">

    <!-- Holdings Table -->
    <div class="td-card">
      <div class="td-card-header">
        <div class="td-card-title"><i class="fas fa-wallet"></i> Holdings</div>
        <a href="<?=URL_HOST?>portfolio/my-holdings.php" class="td-card-link">View all &rsaquo;</a>
      </div>
      <?php if (empty($holdings)) { ?>
        <div class="td-empty">
          <i class="fas fa-wallet"></i>
          No holdings yet. Execute your first trade to start building a portfolio.
        </div>
      <?php } else { ?>
        <div class="td-table-wrap">
          <table class="td-table">
            <thead>
              <tr>
                <th>Symbol</th>
                <th class="right">Qty</th>
                <th class="right">Avg Cost</th>
                <th class="right">Last Price</th>
                <th class="right">Mkt Value</th>
                <th class="right">Unrealized P&amp;L</th>
                <th class="right">Chg%</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($holdings as $holding) {
                $isPos = ($holding["unrealized"] ?? 0) >= 0;
                $pnlClass = $isPos ? "positive" : "negative";
                $chgPct = $holding["change_pct"];
                $chgClass = $chgPct === null ? "" : ($chgPct >= 0 ? "positive" : "negative");
                ?>
                <tr>
                  <td><span class="td-symbol"><?=htmlspecialchars($holding["symbol"]); ?></span></td>
                  <td class="right"><?=$formatNumber($holding["quantity"], 4); ?></td>
                  <td class="right muted"><?=$formatCurrency($holding["avg_cost"]); ?></td>
                  <td class="right"><?=$holding["market_price"] !== null ? $formatCurrency($holding["market_price"]) : "—"; ?></td>
                  <td class="right"><?=$formatCurrency($holding["market_value"]); ?></td>
                  <td class="right <?=$pnlClass; ?>"><?=$formatCurrency($holding["unrealized"]); ?></td>
                  <td class="right <?=$chgClass; ?>">
                    <?php if ($chgPct !== null) {
                      echo ($chgPct >= 0 ? "+" : "") . $formatNumber($chgPct, 2) . "%";
                    } else { echo "—"; } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </div>

    <!-- Watchlist -->
    <div class="td-card">
      <div class="td-card-header">
        <div class="td-card-title"><i class="fas fa-eye"></i> Watchlist</div>
        <a href="<?=URL_HOST?>portfolio/watchlist.php" class="td-card-link">Manage &rsaquo;</a>
      </div>
      <?php if (empty($watchlist)) { ?>
        <div class="td-empty">
          <i class="fas fa-eye"></i>
          Add symbols to your watchlist to monitor them here.
        </div>
      <?php } else { ?>
        <div id="watchlist">
          <?php foreach ($watchlist as $item) {
            $change = $item["change_pct"];
            if ($change === null) {
              $badgeClass = "flat";
              $changeText = "—";
            } elseif ($change >= 0) {
              $badgeClass = "up";
              $changeText = "+" . $formatNumber($change, 2) . "%";
            } else {
              $badgeClass = "down";
              $changeText = $formatNumber($change, 2) . "%";
            }
            ?>
            <div class="td-watchlist-item">
              <div>
                <div class="td-watchlist-sym"><?=htmlspecialchars($item["symbol"]); ?></div>
                <?php if (!empty($item["note"])) { ?>
                  <div class="td-watchlist-note"><?=htmlspecialchars($item["note"]); ?></div>
                <?php } ?>
              </div>
              <div style="text-align:right;">
                <div class="td-watchlist-price"><?=$item["price"] !== null ? $formatCurrency($item["price"]) : "—"; ?></div>
                <span class="td-change-badge <?=$badgeClass; ?>"><?=$changeText; ?></span>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

  </div>

  <!-- ── Recent Trades ── -->
  <div class="td-card">
    <div class="td-card-header">
      <div class="td-card-title"><i class="fas fa-history"></i> Recent Trades</div>
      <a href="<?=URL_HOST?>trading/transactions.php" class="td-card-link">Full history &rsaquo;</a>
    </div>
    <?php if (empty($recentTrades)) { ?>
      <div class="td-empty">
        <i class="fas fa-history"></i>
        You haven't executed any trades yet.
      </div>
    <?php } else { ?>
      <div class="td-table-wrap">
        <table class="td-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Symbol</th>
              <th>Side</th>
              <th class="right">Quantity</th>
              <th class="right">Price</th>
              <th class="right">Fees</th>
              <th class="right">Net Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentTrades as $trade) {
              $lineValue = $trade["quantity"] * $trade["price"];
              $netAmount = $trade["side"] === "BUY"
                ? -($lineValue + $trade["fees"])
                : ($lineValue - $trade["fees"]);
              $netClass = $netAmount >= 0 ? "positive" : "negative";
              ?>
              <tr>
                <td class="muted"><?=date("M d, Y · h:i A", strtotime($trade["executed_at"])); ?></td>
                <td><span class="td-symbol"><?=htmlspecialchars($trade["symbol"]); ?></span></td>
                <td>
                  <?php if ($trade["side"] === "BUY") { ?>
                    <span class="td-side-buy">BUY</span>
                  <?php } else { ?>
                    <span class="td-side-sell">SELL</span>
                  <?php } ?>
                </td>
                <td class="right"><?=$formatNumber($trade["quantity"], 4); ?></td>
                <td class="right"><?=$formatCurrency($trade["price"]); ?></td>
                <td class="right muted"><?=$formatCurrency($trade["fees"]); ?></td>
                <td class="right <?=$netClass; ?>"><?=$formatCurrency($netAmount); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= URL_PUBLIC ?>news-engine.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const allocationSeries = <?=json_encode($allocationSeries); ?>;
  const portfolioNewsHoldings = <?=json_encode(array_values(array_unique($holdingsSymbols)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const portfolioNewsWatchlist = <?=json_encode(array_values(array_unique($watchlistSymbols)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

  const portfolioNewsWidget = window.PaperTradeNews && window.PaperTradeNews.createWidget({
    endpoint: new URL("news-proxy.php", window.location.href).toString(),
    containerId: "portfolioNewsList",
    statusId: "portfolioNewsStatus",
    scope: "portfolio",
    portfolioSymbols: portfolioNewsHoldings,
    watchlistSymbols: portfolioNewsWatchlist,
    marketContextSymbol: "^GSPC",
    autoRefreshMs: 180000,
    emptyMessage: "No recent news for your current holdings.",
    loadingMessage: "Loading portfolio news...",
    errorMessage: "Unable to load portfolio news right now."
  });

  /* ─────────────────────────────────────────
     Chart & Live Polling
  ───────────────────────────────────────── */
  const performanceChartEl = document.getElementById("performanceChart");
  const allocationCtx = document.getElementById("allocationChart");
  const liveValueEl = document.getElementById("performanceCurrentValue");
  const lastUpdateEl = document.getElementById("performanceLastUpdate");
  const rangeLabelEl = document.getElementById("performanceRangeLabel");
  const rangeButtons = document.querySelectorAll("[data-range-filter]");
  const liveToggleBtn = document.getElementById("performanceLiveToggle");
  const liveStatusEl = document.getElementById("performanceLiveStatus");
  const liveDotEl = document.getElementById("performanceLiveDot");
  const portfolioCardValueEl = document.querySelector("[data-metric='portfolioValue']");
  const unrealizedCardEl = document.querySelector("[data-metric='unrealizedPnl']");
  const animatedNumberEls = document.querySelectorAll("[data-animated-number]");

  const portfolioEndpoint = new URL("ajax-portfolio-worth.php", window.location.href).toString();
  const POLL_INTERVAL = 5000;
  const MAX_POINTS = Math.ceil((4 * 60 * 60 * 1000) / POLL_INTERVAL) + 60;

  let pollTimer = null;
  let portfolioChart = null;

  const activeRangeButton = document.querySelector("[data-range-filter].active");
  const initialRange = activeRangeButton ? activeRangeButton.getAttribute("data-range-filter") : "15";
  const initialCardValue = portfolioCardValueEl ? Number(portfolioCardValueEl.dataset.value) : null;

  const chartState = {
    points: [],
    liveEnabled: true,
    activeRange: initialRange,
    lastPortfolioValue: Number.isFinite(initialCardValue) ? initialCardValue : null,
    previousPortfolioValue: null
  };

  const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

  const formatCurrency = (value) => {
    if (!Number.isFinite(value)) return "$0.00";
    return "$" + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  const formatValueForElement = (element, value) => {
    if (!element) return value;
    const formatType = element.dataset && element.dataset.format ? element.dataset.format : "number";
    const decimals = element.dataset && element.dataset.decimals ? Number(element.dataset.decimals) : 2;
    if (formatType === "currency") return formatCurrency(value);
    return Number(value).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
  };

  const triggerMetricFlash = (element) => {
    if (!element) return;
    element.classList.add("metric-flash");
    element.addEventListener("animationend", () => element.classList.remove("metric-flash"), { once: true });
  };

  const applySignClass = (element, value) => {
    if (!element || !element.dataset) return;
    const positiveClass = element.dataset.positiveClass;
    const negativeClass = element.dataset.negativeClass;
    if (!positiveClass || !negativeClass) return;
    element.classList.remove(positiveClass, negativeClass);
    element.classList.add(value >= 0 ? positiveClass : negativeClass);
  };

  const updateDeltaIndicator = (target, delta, options = {}) => {
    if (!target) return;
    target.classList.remove("positive", "negative");
    const suffix = options.suffix || " since last data";
    const pendingText = options.pendingText || "Waiting for previous data…";
    if (!Number.isFinite(delta)) { target.textContent = pendingText; return; }
    if (delta > 0) target.classList.add("positive");
    else if (delta < 0) target.classList.add("negative");
    if (Math.abs(delta) < 0.005) { target.textContent = "Δ " + formatCurrency(0) + suffix; return; }
    const prefix = delta > 0 ? "▲ +" : "▼ -";
    target.textContent = prefix + formatCurrency(Math.abs(delta)) + suffix;
  };

  const animateValue = (element, startValue, endValue, options = {}) => {
    if (!element) return;
    const duration = options.duration > 0 ? options.duration : 900;
    if (!Number.isFinite(startValue) || !Number.isFinite(endValue) || duration <= 0) {
      element.textContent = options.formatter ? options.formatter(endValue) : endValue;
      if (options.onComplete) options.onComplete();
      return;
    }
    const formatter = options.formatter || ((val) => val);
    const startTime = performance.now();
    const step = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = easeOutCubic(progress);
      element.textContent = formatter(startValue + (endValue - startValue) * eased);
      if (progress < 1) requestAnimationFrame(step);
      else if (options.onComplete) options.onComplete();
    };
    requestAnimationFrame(step);
  };

  const setAnimatedNumber = (element, value, options = {}) => {
    if (!element) return;
    const sanitized = Number.isFinite(value) ? value : 0;
    const storedCandidate = element.dataset && typeof element.dataset.currentValue !== "undefined"
      ? Number(element.dataset.currentValue)
      : (element.dataset && element.dataset.value ? Number(element.dataset.value) : sanitized);
    const stored = Number.isFinite(storedCandidate) ? storedCandidate : sanitized;
    const intrinsicDelta = sanitized - stored;
    const delta = typeof options.deltaOverride === "number" ? options.deltaOverride : (options.initial ? 0 : intrinsicDelta);
    const shouldAnimate = options.initial || Math.abs(delta) >= 0.005;
    const startValue = options.initial && options.animateFromZero ? 0 : stored;
    const formatter = (val) => formatValueForElement(element, val);
    if (shouldAnimate) {
      animateValue(element, startValue, sanitized, { duration: options.duration, formatter, onComplete: () => { element.dataset.currentValue = sanitized; } });
    } else {
      element.textContent = formatter(sanitized);
      element.dataset.currentValue = sanitized;
    }
    if (!options.initial && Math.abs(delta) >= 0.005) triggerMetricFlash(element);
    applySignClass(element, sanitized);
    const deltaTargetId = element.dataset ? element.dataset.deltaTarget : null;
    if (deltaTargetId) {
      const deltaTarget = document.getElementById(deltaTargetId);
      if (options.initial) {
        if (deltaTarget) { deltaTarget.textContent = "Synced"; deltaTarget.classList.remove("positive", "negative"); }
      } else {
        updateDeltaIndicator(deltaTarget, delta, { suffix: options.deltaSuffix || " since last data", pendingText: options.deltaPendingText });
      }
    }
  };

  const bootstrapAnimatedNumbers = () => {
    animatedNumberEls.forEach((element) => {
      const initialValue = element.dataset && element.dataset.value ? Number(element.dataset.value) : 0;
      setAnimatedNumber(element, Number.isFinite(initialValue) ? initialValue : 0, { initial: true, animateFromZero: true });
    });
  };

  const getRangeLabel = (value) => {
    if (value === "15") return "15m";
    if (value === "60") return "1h";
    if (value === "240") return "4h";
    return "custom";
  };

  const updateRangeLabel = () => {
    if (!rangeLabelEl) return;
    rangeLabelEl.textContent = chartState.activeRange === "all" ? "Full history" : "Last " + getRangeLabel(chartState.activeRange);
  };

  const getVisiblePoints = () => {
    if (!chartState.points.length) return [];
    if (chartState.activeRange === "all") return chartState.points.slice();
    const minutes = Number(chartState.activeRange);
    if (!Number.isFinite(minutes) || minutes <= 0) return chartState.points.slice();
    const cutoff = Date.now() - minutes * 60 * 1000;
    return chartState.points.filter((p) => p.x >= cutoff);
  };

  const getChartTheme = () => document.body.classList.contains("dark-mode") ? "dark" : "light";
  const getChartFg   = () => document.body.classList.contains("dark-mode") ? "#7d8590" : "#64748b";
  const getChartGrid = () => document.body.classList.contains("dark-mode") ? "rgba(48,54,61,0.6)" : "rgba(0,0,0,0.07)";
  const getChartColor = () => document.body.classList.contains("dark-mode") ? "#4493f8" : "#2563eb";

  const ensureChart = () => {
    if (portfolioChart || !performanceChartEl) return;
    const isDark = document.body.classList.contains("dark-mode");
    portfolioChart = new ApexCharts(performanceChartEl, {
      chart: {
        type: "area",
        height: 320,
        animations: { enabled: false },
        toolbar: { show: false },
        background: "transparent",
        foreColor: getChartFg()
      },
      theme: { mode: getChartTheme() },
      dataLabels: { enabled: false },
      stroke: { curve: "smooth", width: 2 },
      series: [{ name: "Portfolio Value", data: getVisiblePoints() }],
      colors: [getChartColor()],
      fill: {
        type: "gradient",
        gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.01, stops: [0, 90, 100] }
      },
      grid: {
        borderColor: getChartGrid(),
        strokeDashArray: 3
      },
      xaxis: {
        type: "datetime",
        labels: { style: { colors: getChartFg(), fontSize: "11px" } },
        axisBorder: { color: getChartGrid() },
        axisTicks: { color: getChartGrid() }
      },
      yaxis: {
        labels: {
          style: { colors: getChartFg(), fontSize: "11px" },
          formatter: (value) => "$" + Number(value).toLocaleString()
        }
      },
      tooltip: {
        theme: getChartTheme(),
        x: { format: "MMM dd HH:mm:ss" },
        y: { formatter: (value) => formatCurrency(Number(value)) }
      },
      noData: { text: "Awaiting live data…", align: "center", style: { color: isDark ? "#484f58" : "#94a3b8", fontSize: "13px" } }
    });
    portfolioChart.render();
    updateRangeLabel();
  };

  const updateChartSeries = () => {
    ensureChart();
    if (!portfolioChart) return;
    portfolioChart.updateSeries([{ name: "Portfolio Value", data: getVisiblePoints() }], true);
    updateRangeLabel();
  };

  const appendPoint = (timestamp, value) => {
    const ts = timestamp ? new Date(timestamp) : new Date();
    chartState.points.push({ x: ts.getTime(), y: value });
    while (chartState.points.length > MAX_POINTS) chartState.points.shift();
    updateChartSeries();
  };

  const clearPollTimer = () => { if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; } };
  const scheduleNextPoll = () => {
    clearPollTimer();
    if (!chartState.liveEnabled) return;
    pollTimer = setTimeout(refreshPortfolioWorth, POLL_INTERVAL);
  };

  const fetchPortfolioWorth = async () => {
    const response = await fetch(portfolioEndpoint, { method: "GET", credentials: "same-origin", cache: "no-store" });
    if (!response.ok) { const e = new Error("Request failed " + response.status); e.status = response.status; throw e; }
    const payload = await response.json();
    if (!payload || payload.status !== "success") { const e = new Error(payload?.message || "Unexpected response"); e.status = response.status; throw e; }
    return payload;
  };

  const updateLiveStatusUi = () => {
    const on = chartState.liveEnabled;
    if (liveStatusEl) liveStatusEl.textContent = on ? "LIVE" : "PAUSED";
    if (liveDotEl) liveDotEl.classList.toggle("paused", !on);
    if (liveToggleBtn) {
      const icon = liveToggleBtn.querySelector("i");
      const label = liveToggleBtn.querySelector(".live-toggle-label");
      liveToggleBtn.classList.toggle("paused", !on);
      if (icon) { icon.className = on ? "fas fa-pause" : "fas fa-play"; }
      if (label) label.textContent = on ? "Pause" : "Resume";
    }
    // Also update the header live pill
    const headerDot = document.querySelector(".td-live-dot");
    const headerStatus = document.querySelector(".td-live-pill span:last-child");
    if (headerDot) headerDot.classList.toggle("paused", !on);
    if (headerStatus) headerStatus.textContent = on ? "LIVE" : "PAUSED";
  };

  const refreshPortfolioWorth = async () => {
    if (!chartState.liveEnabled) return;
    try {
      const data = await fetchPortfolioWorth();
      const portfolioValue = Number(data.portfolioValue ?? data.totalValue ?? data.value ?? 0);
      const sanitizedValue = Number.isFinite(portfolioValue) ? portfolioValue : 0;
      const timestamp = data.timestamp || new Date().toISOString();
      appendPoint(timestamp, sanitizedValue);
      if (liveValueEl) setAnimatedNumber(liveValueEl, sanitizedValue, { duration: 600 });
      const previousValue = chartState.lastPortfolioValue;
      chartState.previousPortfolioValue = previousValue;
      chartState.lastPortfolioValue = sanitizedValue;
      if (portfolioCardValueEl) {
        const delta = Number.isFinite(previousValue) ? sanitizedValue - previousValue : null;
        setAnimatedNumber(portfolioCardValueEl, sanitizedValue, { duration: 900, deltaOverride: delta, deltaSuffix: " since last data" });
      }
      if (unrealizedCardEl) {
        const pnlValue = data.unrealizedPnL ?? data.unrealized ?? data.unrealizedPnl ?? data.unrealizedProfit ?? data.netUnrealized;
        const parsedPnL = Number(pnlValue);
        if (Number.isFinite(parsedPnL)) setAnimatedNumber(unrealizedCardEl, parsedPnL, { duration: 900 });
      }
      if (lastUpdateEl) {
        lastUpdateEl.textContent = new Date(timestamp).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
      }
    } catch (error) {
      console.error("Unable to refresh portfolio worth:", error);
      const isLoggingOut = window.__loggingOut === true || (() => { try { return sessionStorage.getItem("papertrade_logging_out") === "1"; } catch(e){ return false; } })();
      if (error.status === 401) { clearPollTimer(); if (lastUpdateEl) lastUpdateEl.textContent = "Session expired"; return; }
      if (lastUpdateEl) lastUpdateEl.textContent = "Error";
      if (window.toastr) window.toastr.error("Live update failed" + (error.message ? ": " + error.message : ""));
    } finally {
      scheduleNextPoll();
    }
  };

  /* ── Allocation Donut ── */
  if (allocationCtx && allocationSeries.length) {
    const COLORS = ['#4493f8','#ff4d6d','#00d084','#e3b341','#a371f7','#fb8500','#06d6a0','#ef476f','#ffd166','#118ab2'];
    new Chart(allocationCtx, {
      type: 'doughnut',
      data: {
        labels: allocationSeries.map(i => i.symbol),
        datasets: [{
          data: allocationSeries.map(i => Number(i.value || 0)),
          backgroundColor: COLORS,
          borderWidth: 0,
          hoverBorderWidth: 2,
          hoverBorderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => ctx.label + ': $' + Number(ctx.parsed).toLocaleString(undefined, { maximumFractionDigits: 2 })
            }
          }
        }
      }
    });
  }

  /* ── Init ── */
  bootstrapAnimatedNumbers();
  updateLiveStatusUi();
  updateRangeLabel();

  if (liveToggleBtn) {
    liveToggleBtn.addEventListener("click", () => {
      chartState.liveEnabled = !chartState.liveEnabled;
      updateLiveStatusUi();
      if (chartState.liveEnabled) refreshPortfolioWorth();
      else clearPollTimer();
    });
  }

  rangeButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      if (btn.classList.contains("active")) return;
      rangeButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      chartState.activeRange = btn.getAttribute("data-range-filter") || "all";
      updateChartSeries();
    });
  });

  if (performanceChartEl) { ensureChart(); refreshPortfolioWorth(); }
  if (portfolioNewsWidget) portfolioNewsWidget.load();

  /* Re-render chart when dark/light mode toggles */
  const darkModeBtn = document.getElementById("dark-mode-toggle");
  if (darkModeBtn && portfolioChart) {
    darkModeBtn.addEventListener("click", () => {
      // Wait one tick for body.dark-mode class to be toggled by the existing handler
      setTimeout(() => {
        if (portfolioChart) {
          portfolioChart.destroy();
          portfolioChart = null;
        }
        ensureChart();
        updateChartSeries();
      }, 50);
    });
  }

  window.addEventListener("beforeunload", clearPollTimer);
});
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
