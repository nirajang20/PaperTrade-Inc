<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

$tradeAlert = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["sell_order_submit"])) {
  $orderModeRaw = $_POST["order_mode"] ?? "";
  $orderMode = strtolower(trim($orderModeRaw));
  $symbol = strtoupper(trim($_POST["symbol"] ?? ""));
  $quantityInput = isset($_POST["quantity"]) ? (float) $_POST["quantity"] : 0.0;
  $amountInput = isset($_POST["amount"]) ? (float) $_POST["amount"] : 0.0;
  $limitPriceInput = isset($_POST["limit_price"]) ? (float) $_POST["limit_price"] : 0.0;
  $priceSnapshot = isset($_POST["price_snapshot"]) ? (float) $_POST["price_snapshot"] : 0.0;
  $feesInput = isset($_POST["fees"]) ? (float) $_POST["fees"] : 0.0;
  $note = trim($_POST["note"] ?? "");

  $errors = [];

  if (!$conn) {
    $errors[] = "Unable to reach the trading database.";
  }

  if (empty($active_user) || !isset($active_user["user_id"])) {
    $errors[] = "Your session has expired. Please log in again.";
  }

  if ($symbol === "") {
    $errors[] = "Please choose a symbol before submitting an order.";
  }

  if (!in_array($orderMode, ["shares", "amount"], true)) {
    $errors[] = "Invalid order mode selected.";
  }

  $finalQuantity = 0.0;
  $finalPrice = $limitPriceInput > 0 ? $limitPriceInput : $priceSnapshot;
  $finalAmount = max($amountInput, 0);

  if ($orderMode === "shares") {
    $finalQuantity = max($quantityInput, 0);
    if ($finalQuantity <= 0) {
      $errors[] = "Enter a positive number of shares to sell.";
    }
    if ($finalPrice <= 0) {
      $errors[] = "Price information is missing. Please refresh and try again.";
    }
  } elseif ($orderMode === "amount") {
    $finalAmount = max($amountInput, 0);
    if ($finalAmount <= 0) {
      $errors[] = "Enter a positive amount to sell.";
    }
    if ($priceSnapshot <= 0) {
      $errors[] = "Live price unavailable. Please refresh and try again.";
    } else {
      $finalQuantity = $finalAmount / $priceSnapshot;
      $finalPrice = $priceSnapshot;
    }
  }

  if ($finalPrice <= 0) {
    $errors[] = "Unable to determine a valid execution price.";
  }

  $fees = $feesInput >= 0 ? $feesInput : 0.0;
  $tradeProceeds = $orderMode === "amount" ? $finalAmount : ($finalQuantity * $finalPrice);
  $tradeProceeds = $tradeProceeds > 0 ? $tradeProceeds : 0.0;
  $netProceeds = $tradeProceeds - $fees;
  if ($tradeProceeds <= 0) {
    $errors[] = "Unable to calculate the proceeds of this order. Please review your inputs.";
  }
  if ($netProceeds <= 0) {
    $errors[] = "Estimated proceeds are insufficient after fees.";
  }
  if (!empty($symbol)) {
    $symbol = preg_replace('/[^A-Z0-9\.\-\^]/', '', $symbol);
  }

  $symbolId = null;
  if (empty($errors)) {
    $stmtSymbol = $conn->prepare("SELECT id FROM available_trade WHERE symbol = ?");
    if ($stmtSymbol) {
      $stmtSymbol->bind_param("s", $symbol);
      if ($stmtSymbol->execute()) {
        $stmtSymbol->bind_result($symbolId);
        $stmtSymbol->fetch();
      }
      $stmtSymbol->close();
    } else {
      $errors[] = "Unable to look up the selected symbol.";
    }
  }

  if (empty($errors) && !$symbolId) {
    $stmtInsertSymbol = $conn->prepare("INSERT INTO available_trade (symbol) VALUES (?)");
    if ($stmtInsertSymbol) {
      $stmtInsertSymbol->bind_param("s", $symbol);
      if ($stmtInsertSymbol->execute()) {
        $symbolId = $stmtInsertSymbol->insert_id;
      } else {
        $errors[] = "Unable to register the selected symbol.";
      }
      $stmtInsertSymbol->close();
    } else {
      $errors[] = "Unable to prepare the symbol registry.";
    }
  }

  if (empty($errors) && (!$symbolId || $finalQuantity <= 0 || $finalPrice <= 0)) {
    $errors[] = "Order validation failed. Please verify your order details.";
  }

  if (empty($errors)) {
    $userId = (int) $active_user["user_id"];
    $transactionStarted = false;
    try {
      if (!$conn->begin_transaction()) {
        throw new Exception("Unable to start database transaction.");
      }
      $transactionStarted = true;
      $stmtHoldingSelect = $conn->prepare("SELECT holding_id, quantity, avg_cost, note FROM holding WHERE user_id = ? AND symbol_id = ? LIMIT 1 FOR UPDATE");
      if (!$stmtHoldingSelect) {
        throw new Exception("Unable to prepare holdings lookup.");
      }
      $stmtHoldingSelect->bind_param("ii", $userId, $symbolId);
      if (!$stmtHoldingSelect->execute()) {
        $stmtHoldingSelect->close();
        throw new Exception("Unable to fetch existing holdings.");
      }
      $stmtHoldingSelect->bind_result($holdingId, $currentQty, $currentAvgCost, $currentNote);
      $hasHolding = (bool) $stmtHoldingSelect->fetch();
      $stmtHoldingSelect->close();

      if (!$hasHolding || $currentQty <= 0) {
        throw new Exception("You do not own any shares of {$symbol}.");
      }

      $normalizedQuantity = (float) $currentQty;
      $tolerance = 0.0001;
      if ($finalQuantity - $normalizedQuantity > $tolerance) {
        throw new Exception("You only have " . number_format($normalizedQuantity, 4) . " shares of {$symbol} available to sell.");
      }

      $sellQuantity = min($finalQuantity, $normalizedQuantity);
      if ($sellQuantity <= 0) {
        throw new Exception("Unable to determine sellable quantity.");
      }

      $stmtBalanceSelect = $conn->prepare("SELECT user_balance FROM users WHERE user_id = ? FOR UPDATE");
      if (!$stmtBalanceSelect) {
        throw new Exception("Unable to prepare balance check.");
      }
      $stmtBalanceSelect->bind_param("i", $userId);
      if (!$stmtBalanceSelect->execute()) {
        $stmtBalanceSelect->close();
        throw new Exception("Unable to verify account balance.");
      }
      $stmtBalanceSelect->bind_result($availableBalanceRaw);
      if (!$stmtBalanceSelect->fetch()) {
        $stmtBalanceSelect->close();
        throw new Exception("Account balance record could not be located.");
      }
      $stmtBalanceSelect->close();

      $stmtOrder = $conn->prepare("INSERT INTO transaction (user_id, symbol_id, side, quantity, price, fees) VALUES (?, ?, 'SELL', ?, ?, ?)");
      if (!$stmtOrder) {
        throw new Exception("Unable to prepare the trade submission.");
      }

      $quantityFormatted = round($sellQuantity, 4);
      $priceFormatted = round($finalPrice, 4);
      $feesFormatted = round($fees, 2);
      $stmtOrder->bind_param("iiddd", $userId, $symbolId, $quantityFormatted, $priceFormatted, $feesFormatted);
      if (!$stmtOrder->execute()) {
        $orderError = $stmtOrder->error ?: "Order execution failed.";
        $stmtOrder->close();
        throw new Exception("Unable to submit the order: " . $orderError);
      }
      $stmtOrder->close();

      $noteToStore = $note !== "" ? $note : $currentNote;
      $remainingQty = $normalizedQuantity - $sellQuantity;
      if ($remainingQty <= $tolerance) {
        $stmtHoldingDelete = $conn->prepare("DELETE FROM holding WHERE holding_id = ?");
        if (!$stmtHoldingDelete) {
          throw new Exception("Unable to prepare holdings removal.");
        }
        $stmtHoldingDelete->bind_param("i", $holdingId);
        if (!$stmtHoldingDelete->execute()) {
          $stmtHoldingDelete->close();
          throw new Exception("Unable to remove holding record.");
        }
        $stmtHoldingDelete->close();
      } else {
        $stmtHoldingUpdate = $conn->prepare("UPDATE holding SET quantity = ?, note = ?, updated_at = NOW() WHERE holding_id = ?");
        if (!$stmtHoldingUpdate) {
          throw new Exception("Unable to prepare holdings update.");
        }
        $remainingRounded = round($remainingQty, 4);
        $noteParam = $noteToStore;
        $stmtHoldingUpdate->bind_param("dsi", $remainingRounded, $noteParam, $holdingId);
        if (!$stmtHoldingUpdate->execute()) {
          $stmtHoldingUpdate->close();
          throw new Exception("Unable to update holdings record.");
        }
        $stmtHoldingUpdate->close();
      }

      $availableBalance = (float) $availableBalanceRaw;
      $netCredit = max($netProceeds, 0.0);
      $newBalance = $availableBalance + $netCredit;
      $newBalance = round($newBalance, 2);
      $stmtBalanceUpdate = $conn->prepare("UPDATE users SET user_balance = ?, last_updated = NOW() WHERE user_id = ?");
      if (!$stmtBalanceUpdate) {
        throw new Exception("Unable to prepare balance update.");
      }
      $stmtBalanceUpdate->bind_param("di", $newBalance, $userId);
      if (!$stmtBalanceUpdate->execute()) {
        $stmtBalanceUpdate->close();
        throw new Exception("Unable to update account balance.");
      }
      $stmtBalanceUpdate->close();
      $active_user["user_balance"] = $newBalance;

      if (!$conn->commit()) {
        throw new Exception("Unable to finalize the order.");
      }
      $transactionStarted = false;

      $tradeAlert = [
        "type" => "success",
        "title" => "Sell Order Submitted",
        "text" => "Your sell order for {$symbol} has been executed and holdings updated."
      ];
    } catch (Exception $exception) {
      if ($transactionStarted) {
        $conn->rollback();
      }
      $errors[] = $exception->getMessage();
    }
  }

  if (!empty($errors)) {
    $tradeAlert = [
      "type" => "error",
      "title" => "Sell Order Failed",
      "text" => implode("\n", array_unique($errors))
    ];
  }
}

$userHoldings = [];
$userHoldingsMap = [];
$defaultSellSymbol = "NVDA";
if ($conn && isset($active_user['user_id'])) {
  $holdingsSql = "
    SELECT at.symbol, at.id AS symbol_id, h.quantity, h.avg_cost, h.updated_at
    FROM holding h
    INNER JOIN available_trade at ON at.id = h.symbol_id
    WHERE h.user_id = ?
    ORDER BY h.updated_at DESC, at.symbol ASC
  ";
  $stmtHoldings = $conn->prepare($holdingsSql);
  if ($stmtHoldings) {
    $userIdForHoldings = (int) $active_user['user_id'];
    $stmtHoldings->bind_param("i", $userIdForHoldings);
    if ($stmtHoldings->execute()) {
      $stmtHoldings->bind_result($holdingSymbol, $holdingSymbolId, $holdingQuantity, $holdingAvgCost, $holdingUpdatedAt);
      while ($stmtHoldings->fetch()) {
        $symbolKey = strtoupper($holdingSymbol);
        $quantityValue = (float) $holdingQuantity;
        $avgCostValue = (float) $holdingAvgCost;
        $userHoldings[] = [
          "symbol" => $symbolKey,
          "symbol_id" => (int) $holdingSymbolId,
          "quantity" => $quantityValue,
          "avg_cost" => $avgCostValue,
          "updated_at" => $holdingUpdatedAt
        ];
        if (!isset($userHoldingsMap[$symbolKey])) {
          $userHoldingsMap[$symbolKey] = [
            "quantity" => $quantityValue,
            "avg_cost" => $avgCostValue
          ];
        }
      }
    }
    $stmtHoldings->close();
  }
}

if (!empty($userHoldingsMap)) {
  $defaultSellSymbol = array_key_first($userHoldingsMap);
}

$recentOrders = [];
if ($conn && isset($active_user['user_id'])) {
  $recentSql = "
    SELECT at.symbol, t.quantity, t.price, t.fees, t.executed_at
    FROM transaction t
    INNER JOIN available_trade at ON at.id = t.symbol_id
    WHERE t.user_id = ? AND t.side = 'SELL'
      AND t.executed_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY t.executed_at DESC
    LIMIT 20
  ";
  $stmtRecent = $conn->prepare($recentSql);
  if ($stmtRecent) {
    $userIdForRecent = (int) $active_user['user_id'];
    $stmtRecent->bind_param("i", $userIdForRecent);
    if ($stmtRecent->execute()) {
      $stmtRecent->bind_result($recentSymbol, $recentQty, $recentPrice, $recentFees, $recentExecutedAt);
      while ($stmtRecent->fetch()) {
        $recentOrders[] = [
          "symbol" => $recentSymbol,
          "quantity" => (float) $recentQty,
          "price" => (float) $recentPrice,
          "fees" => (float) $recentFees,
          "executed_at" => $recentExecutedAt
        ];
      }
    }
    $stmtRecent->close();
  }
}
$availableCashValue = isset($active_user["user_balance"]) ? (float) $active_user["user_balance"] : 0.0;
?>

<section class="content">
  <div class="container-fluid">

    <!-- HEADER -->
    <div class="row align-items-center mb-3">
      <div class="col-lg-8">
        <h1 class="h3 mb-1"><i class="fas fa-hand-holding-usd text-danger mr-2"></i>Trade Tickets · <span id="page-intent-label" class="text-danger">Sell</span></h1>
        <p class="text-muted mb-0">Track the market, review performance, and stage a sell order without leaving this workspace.</p>
      </div>
    </div>
    <hr>
    <div class="row">
      <!-- LEFT COLUMN: Selected Stock -->
      <div class="col-xl-4">
<div class="card card-outline card-danger mb-4">
  <div class="card-header border-0 d-flex justify-content-between align-items-center">
    <h3 class="card-title mb-0">Selected Stock</h3>
    <span class="badge badge-danger">Live</span>
    </div>
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h2 class="mb-0" id="selected-symbol">Loading...</h2>
        <p class="text-muted mb-3" id="selected-company">Loading company name</p>
        <!-- <div class="badge badge-light text-muted">Loading stock exchange...</div> -->
      </div>
      <div class="text-right">
        <small class="text-muted d-block">Last Price</small>
        <h2 class="mb-0" id="selected-price">--.--</h2>
        <span class="text-muted" id="selected-change">Loading change...</span>
      </div>
    </div>

    <div class="row pt-3">
      <div class="col-6"><small class="text-muted">Open</small><p id="selected-open" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Previous Close</small><p id="selected-prev-close" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Day Range</small><p id="selected-day-range" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">52W Range</small><p id="selected-52w-range" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Volume</small><p id="selected-volume" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Avg Volume</small><p id="selected-avg-volume" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Market Cap</small><p id="selected-market-cap" class="font-weight-bold mb-0">Loading...</p></div>
      <div class="col-6"><small class="text-muted">P/E Ratio</small><p id="selected-pe" class="font-weight-bold mb-0">Loading...</p></div>
    </div>
  </div>
  <div class="card-footer bg-white">
    <small class="text-muted">Updated at <?=date('Y-m-d H:i:s')?> CST</small>
  </div>
</div>
        <!-- AVAILABLE STOCKS -->
        <div class="card card-outline card-primary mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Available Stocks</h3>
            <button class="btn btn-tool d-none" type="button" id="refresh-stocks" aria-label="Refresh stock list"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div id="available-stocks" class="list-group list-group-flush">
            <div class="p-3 text-center text-muted">Loading data...</div>
          </div>
          <div class="card-footer bg-white">
            <small class="text-muted">Screeners refreshed at <?=date('Y-m-d H:i:s')?> CST</small>
          </div>
        </div>

        <!-- WATCHLIST -->
        <div class="card card-outline card-info mb-4">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Pinned Watchlist</h3>
            <span class="badge badge-info">Alerts · 3</span>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>BTC/USD</span><span class="badge badge-success">+2.6%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>SPY</span><span class="badge badge-warning text-dark">+0.4%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>VIX</span><span class="badge badge-danger">-1.9%</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="col-xl-8">
        <!-- CHART -->
        <div class="card card-outline card-danger mb-4">
          <h3 class="card-title mb-0 text-center mt-2" id="selected-symbol-chart">Loading....</h3>
          <div class="card-body">
            <div id="chart"></div>
            <div class="card-header d-flex justify-content-center align-items-center flex-wrap gap-2">
              <div class="btn-group btn-group-sm" role="group" aria-label="Range selector" id="chart-range-buttons">
                <button type="button" class="btn btn-outline-primary active" data-range="1D">1D</button>
                <button type="button" class="btn btn-outline-primary" data-range="1W">1W</button>
                <button type="button" class="btn btn-outline-primary" data-range="1M">1M</button>
                <button type="button" class="btn btn-outline-primary" data-range="YTD">YTD</button>
                <button type="button" class="btn btn-outline-primary" data-range="1Yr">1Yr</button>
                <button type="button" class="btn btn-outline-primary" data-range="3Yr">3Yr</button>
                <button type="button" class="btn btn-outline-primary" data-range="5Yr">5Yr</button>
              </div>
            </div>
  </div>
</div>

       

        <!-- ORDER TICKET -->
        <div class="card card-outline card-danger mb-4 mt-4">
          <div class="card-header border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
              <div>
                <h3 class="card-title mb-0">Order Ticket</h3>
                <small class="text-muted">Long Equity · USD · Intent <span id="order-intent-label" class="text-danger">Sell</span></small>
              </div>
            </div>
          </div>
          <div class="card-body">
            <ul class="nav nav-pills mb-3" id="ticket-mode-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="by-shares-tab" data-toggle="tab" href="#ticket-by-shares" role="tab" data-mode="shares">Sell by Shares</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="by-dollars-tab" data-toggle="tab" href="#ticket-by-dollars" role="tab" data-mode="amount">Sell by Amount</a>
              </li>
            </ul>
            <div class="tab-content" id="ticket-mode-content">
              <div class="tab-pane fade show active" id="ticket-by-shares" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <div class="trade-input-wrapper" data-mode="shares">
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="shares-quantity">Shares</label>
                          <input type="number" min="0" class="form-control" id="shares-quantity" placeholder="e.g. 10">
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="shares-limit">Limit Price</label>
                          <input type="number" step="0.01" class="form-control" id="shares-limit" placeholder="$191.50" readonly/>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="shares-order-type">Order Type</label>
                          <select id="shares-order-type" class="form-control" aria-readonly="true">
                            <option selected>Market</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="shares-symbol-name">Symbol</label>
                          <input id="shares-symbol-name" name="shares-symbol-name" class="form-control" value="Loading..." readonly/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="shares-notes">Trader Notes</label>
                        <textarea id="shares-notes" class="form-control" rows="2" placeholder="Remind yourself why you're exiting"></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-6">Mode</dt>
                        <dd class="col-6 text-right" id="shares-summary-mode">Shares</dd>
                        <dt class="col-6">Quantity</dt>
                        <dd class="col-6 text-right" id="shares-summary-quantity">—</dd>
                        <dt class="col-6">Est. Proceeds</dt>
                        <dd class="col-6 text-right" id="shares-summary-cost">—</dd>
                        <dt class="col-6">Fees</dt>
                        <dd class="col-6 text-right" id="shares-summary-fees">$1.00</dd>
                        <dt class="col-6">Shares Remaining</dt>
                        <dd class="col-6 text-right" id="shares-summary-buying-power">—</dd>
                      </dl>
                    </div>
                    <div class="mt-3">
                      <h6 class="text-uppercase text-muted mb-2 small">Execution Checklist</h6>
                      <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><i class="fas fa-check-circle text-danger mr-2"></i>Shares available</li>
                        <li class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>Compliance checks passed</li>
                        <li class="mb-1"><i class="fas fa-clock text-warning mr-2"></i>Extended hours disabled</li>
                        <li><i class="fas fa-info-circle text-info mr-2"></i>Submit before 4:00 PM ET</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="ticket-by-dollars" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <div class="trade-input-wrapper" data-mode="amount">
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="dollar-amount">Amount (USD)</label>
                          <input type="number" step="0.01" class="form-control" id="dollar-amount" name="dollar-amount" placeholder="e.g. 2,500">
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="dollar-estimated">Estimated Shares</label>
                          <input type="text" class="form-control" id="dollar-estimated" placeholder="13.05" name="dollar-estimated" readonly="">
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="dollar-order-type">Order Type</label>
                          <select id="dollar-order-type" class="form-control" aria-readonly="true">
                            <option selected>Market</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="dollar-symbol-name">Symbol</label>
                          <input id="dollar-symbol-name" name="dollar-symbol-name" class="form-control" value="Loading..." readonly/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="dollar-note">Instruction</label>
                        <input type="text" class="form-control" id="dollar-note" placeholder="Optional trade note">
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-6">Mode</dt>
                        <dd class="col-6 text-right" id="amount-summary-mode">Amount</dd>
                        <dt class="col-6">Target Proceeds</dt>
                        <dd class="col-6 text-right" id="amount-summary-investing">—</dd>
                        <dt class="col-6">Est. Shares Sold</dt>
                        <dd class="col-6 text-right" id="amount-summary-est-shares">—</dd>
                        <dt class="col-6">Est. Execution Price</dt>
                        <dd class="col-6 text-right" id="amount-summary-avg-price">—</dd>
                      </dl>
                    </div>
                    <div class="mt-3">
                      <h6 class="text-uppercase text-muted mb-2 small">Checklist</h6>
                      <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><i class="fas fa-dot-circle text-primary mr-2"></i>Fractional trade enabled</li>
                        <li class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>Availability confirmed</li>
                        <li><i class="fas fa-lock text-secondary mr-2"></i>Trade window closes in 1h 12m</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-column flex-md-row">
            <div class="text-muted small mb-3 mb-md-0" id="holding-info-wrapper" data-cash-balance="<?=htmlspecialchars($availableCashValue, ENT_QUOTES); ?>">
              <span class="mr-3" id="holding-quantity-display"><i class="fas fa-layer-group mr-1"></i>Shares owned: —</span>
              <span class="mr-3" id="holding-value-display"><i class="fas fa-chart-line mr-1"></i>Est. value: —</span>
              <span id="cash-balance-display"><i class="fas fa-wallet mr-1"></i>Cash balance: $<?=number_format($availableCashValue, 2, '.', ','); ?></span>
            </div>
            <div>
              <button type="button" class="btn btn-outline-secondary mr-2" id="clear-trade-btn"><i class="fas fa-undo"></i> Clear Ticket</button>
              <button type="button" class="btn btn-danger" id="submit-trade-btn">Submit Sell Order</button>
            </div>
          </div>
          <form method="post" action="<?=htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="sell-order-form" class="d-none">
            <input type="hidden" name="sell_order_submit" value="1">
            <input type="hidden" name="order_mode" id="form-order-mode">
            <input type="hidden" name="symbol" id="form-symbol">
            <input type="hidden" name="quantity" id="form-quantity">
            <input type="hidden" name="limit_price" id="form-limit-price">
            <input type="hidden" name="amount" id="form-amount">
            <input type="hidden" name="estimated_shares" id="form-estimated-shares">
            <input type="hidden" name="price_snapshot" id="form-price-snapshot">
            <input type="hidden" name="fees" id="form-fees">
            <input type="hidden" name="note" id="form-note">
          </form>
        </div>

        <!-- RECENT ORDERS -->
        <div class="card card-outline card-danger">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Recent Sell Orders</h3>
            <span class="text-muted small">Past 24 hours</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Symbol</th><th>Mode</th><th>Qty</th><th>Price</th><th>Status</th><th>Time</th></tr></thead>
              <tbody>
                <?php if (!empty($recentOrders)) { ?>
                  <?php foreach ($recentOrders as $order) {
                    $quantityValue = $order['quantity'];
                    $modeLabel = abs($quantityValue - round($quantityValue)) > 0.0001 ? "Amount" : "Shares";
                    $formattedQty = $quantityValue > 0 ? number_format($quantityValue, 4, '.', ',') : '—';
                    if ($modeLabel === "Shares" && abs($quantityValue - round($quantityValue)) <= 0.0001) {
                      $formattedQty = number_format(round($quantityValue), 0, '', ',');
                    }
                    $formattedPrice = $order['price'] > 0 ? '$' . number_format($order['price'], 2, '.', ',') : '—';
                    $timeDisplay = '—';
                    if (!empty($order['executed_at'])) {
                      $timestamp = strtotime($order['executed_at']);
                      if ($timestamp) {
                        $timeDisplay = date("M d, Y H:i", $timestamp);
                      }
                    }
                  ?>
                    <tr>
                      <td><?=htmlspecialchars($order['symbol']); ?></td>
                      <td><?=$modeLabel; ?></td>
                      <td><?=$formattedQty; ?></td>
                      <td><?=$formattedPrice; ?></td>
                      <td><span class="badge badge-danger">Filled</span></td>
                      <td><?=$timeDisplay; ?></td>
                    </tr>
                  <?php } ?>
                <?php } else { ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No sell orders in the last 24 hours.</td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
<style>
  #available-stocks a.active {
    background-color: rgba(220,53,69,0.08);
    border-left: 4px solid #dc3545;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
  }
  #available-stocks a:hover {
    background-color: rgba(220,53,69,0.12);
  }

  /* Ensure the h5 title (and its span/small) in an active item is black */
  #available-stocks a.active h5,
  #available-stocks a.active h5 span,
  #available-stocks a.active h5 small {
    color: #000 !important;
  }

  /* Slightly bolder title when active */
  #available-stocks a.active h5 {
    font-weight: 700;
  }

  /* Ensure the right-side price (e.g. <span class="d-block font-weight-bold">) is black when item is active */
  #available-stocks a.active .d-block.font-weight-bold {
    color: #000 !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const apiBase = "http://74.208.158.229/stock-api";
  const holdingsMapRaw = <?=json_encode($userHoldingsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const holdingsMap = holdingsMapRaw && typeof holdingsMapRaw === 'object' ? holdingsMapRaw : {};
  const baseSymbols = ['NVDA','MSFT','AAPL','GOOGL','AMZN','META','TSLA','JPM','^GSPC','BTC-USD','ETH-USD'];
  const symbols = Array.from(new Set(baseSymbols.concat(Object.keys(holdingsMap))));
  const DEFAULT_SYMBOL = <?=json_encode($defaultSellSymbol); ?>;

  const availableList = document.getElementById("available-stocks");
  const refreshBtn = document.getElementById("refresh-stocks");
  const refreshIcon = refreshBtn ? refreshBtn.querySelector('i') : null;
  const rangeButtonsWrapper = document.getElementById("chart-range-buttons");
  const chartTitle = document.getElementById("selected-symbol-chart");
  const chartEl = document.getElementById("chart");
  const holdingInfoWrapper = document.getElementById("holding-info-wrapper");
  const holdingQuantityDisplay = document.getElementById("holding-quantity-display");
  const holdingValueDisplay = document.getElementById("holding-value-display");
  const cashBalanceDisplay = document.getElementById("cash-balance-display");
  const cashBalance = holdingInfoWrapper ? Number(holdingInfoWrapper.getAttribute('data-cash-balance') || 0) : 0;

  const TRADE_FEE = 1;
  const CACHE_DURATION_MS = 60000;
  const CHART_CACHE_DURATION_MS = 120000;
  const HOLDING_TOLERANCE = 0.0001;

  const stockDataCache = new Map();
  const chartDataCache = new Map();

  const state = {
    currentSymbol: null,
    currentRange: '1D',
    livePrice: 0,
    currentHoldingQty: 0,
    currentAvgCost: 0
  };

  let chartInstance = null;
  let stockAbortController = null;
  let chartAbortController = null;
  let loadingSwalCount = 0;
  let loadingSwalTimerInterval = null;

  function openLoadingSwal(config) {
    if (!window.Swal) return false;

    loadingSwalCount += 1;
    if (loadingSwalCount > 1) {
      return false;
    }

    const userConfig = config ? Object.assign({}, config) : {};
    const userDidOpen = typeof userConfig.didOpen === 'function' ? userConfig.didOpen : null;
    const userWillClose = typeof userConfig.willClose === 'function' ? userConfig.willClose : null;

    const allowOutsideClickValue = userConfig.allowOutsideClick;
    const showConfirmButtonValue = userConfig.showConfirmButton;
    const swalConfig = Object.assign({}, userConfig, {
      title: userConfig.title || "Loading…",
      allowOutsideClick: (typeof allowOutsideClickValue === 'boolean' || typeof allowOutsideClickValue === 'function') ? allowOutsideClickValue : false,
      showConfirmButton: typeof showConfirmButtonValue === 'boolean' ? showConfirmButtonValue : false,
      didOpen: (popup) => {
        if (typeof Swal.showLoading === 'function') {
          Swal.showLoading();
        }
        if (typeof userDidOpen === 'function') {
          userDidOpen(popup);
        }
      },
      willClose: (popup) => {
        if (loadingSwalTimerInterval) {
          clearInterval(loadingSwalTimerInterval);
          loadingSwalTimerInterval = null;
        }
        if (typeof userWillClose === 'function') {
          userWillClose(popup);
        }
      }
    });

    Swal.fire(swalConfig);
    return true;
  }

  function closeLoadingSwal() {
    if (!window.Swal || loadingSwalCount === 0) return;
    loadingSwalCount -= 1;
    if (loadingSwalCount === 0) {
      if (loadingSwalTimerInterval) {
        clearInterval(loadingSwalTimerInterval);
        loadingSwalTimerInterval = null;
      }
      if (typeof Swal.close === 'function') {
        Swal.close();
      }
    }
  }

  const els = {
    symbol: document.getElementById("selected-symbol"),
    company: document.getElementById("selected-company"),
    price: document.getElementById("selected-price"),
    change: document.getElementById("selected-change"),
    open: document.getElementById("selected-open"),
    prevClose: document.getElementById("selected-prev-close"),
    dayRange: document.getElementById("selected-day-range"),
    range52: document.getElementById("selected-52w-range"),
    volume: document.getElementById("selected-volume"),
    avgVolume: document.getElementById("selected-avg-volume"),
    marketCap: document.getElementById("selected-market-cap"),
    pe: document.getElementById("selected-pe")
  };

  const summaryEls = {
    sharesQuantity: document.getElementById("shares-summary-quantity"),
    sharesCost: document.getElementById("shares-summary-cost"),
    sharesFees: document.getElementById("shares-summary-fees"),
    sharesBuyingPower: document.getElementById("shares-summary-buying-power"),
    amountInvesting: document.getElementById("amount-summary-investing"),
    amountEstShares: document.getElementById("amount-summary-est-shares"),
    amountAvgPrice: document.getElementById("amount-summary-avg-price")
  };

  const priceInputs = {
    sharesQty: document.getElementById('shares-quantity'),
    sharesLimit: document.getElementById('shares-limit'),
    dollarAmount: document.getElementById('dollar-amount'),
    dollarEstimated: document.getElementById('dollar-estimated'),
    sharesSymbol: document.getElementById('shares-symbol-name'),
    dollarSymbol: document.getElementById('dollar-symbol-name'),
    sharesNote: document.getElementById('shares-notes'),
    dollarNote: document.getElementById('dollar-note')
  };
  const hiddenForm = document.getElementById('sell-order-form');
  const hiddenFields = {
    orderMode: document.getElementById('form-order-mode'),
    symbol: document.getElementById('form-symbol'),
    quantity: document.getElementById('form-quantity'),
    limitPrice: document.getElementById('form-limit-price'),
    amount: document.getElementById('form-amount'),
    estimatedShares: document.getElementById('form-estimated-shares'),
    priceSnapshot: document.getElementById('form-price-snapshot'),
    fees: document.getElementById('form-fees'),
    note: document.getElementById('form-note')
  };

  const chartRangeConfig = {
    '1D': { period: '1d', interval: '15m' },
    '1W': { period: '5d', interval: '30m' },
    '1M': { period: '1mo', interval: '1d' },
    'YTD': { period: '6mo', interval: '1d' },
    '1Yr': { period: '1y', interval: '1d' },
    '3Yr': { period: '3y', interval: '1wk' },
    '5Yr': { period: '5y', interval: '1mo' }
  };

  const rangeButtons = rangeButtonsWrapper ? Array.prototype.slice.call(rangeButtonsWrapper.querySelectorAll('button[data-range]')) : [];

  function formatCurrency(value) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    return num.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
  }

  function formatDecimal(value, digits) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    return num.toFixed(typeof digits === 'number' ? digits : 2);
  }

  function formatShares(value) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    if (Math.abs(num - Math.round(num)) < 0.0001) {
      return Math.round(num).toLocaleString('en-US');
    }
    return num.toFixed(4);
  }

  function fmtNum(value) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    const abs = Math.abs(num);
    const sign = num < 0 ? '-' : '';
    if (abs >= 1e12) return sign + (abs / 1e12).toFixed(2) + 'T';
    if (abs >= 1e9) return sign + (abs / 1e9).toFixed(2) + 'B';
    if (abs >= 1e6) return sign + (abs / 1e6).toFixed(1) + 'M';
    return sign + abs.toLocaleString();
  }

  function clampNumber(value) {
    return isFinite(value) ? value : 0;
  }

  function debounce(func, delay) {
    let timeout;
    return function () {
      const args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        func.apply(null, args);
      }, typeof delay === 'number' ? delay : 250);
    };
  }

  function escapeSelector(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }
    return String(value).replace(/([ !"#$%&'()*+,./:;<=>?@\[\\\]^`{|}~])/g, '\\$1');
  }

  function setActiveRangeButton(range) {
    for (let i = 0; i < rangeButtons.length; i++) {
      const btn = rangeButtons[i];
      btn.classList.toggle('active', btn.getAttribute('data-range') === range);
    }
  }

  function setActiveSymbol(symbol) {
    if (!availableList) return;
    const items = availableList.querySelectorAll('a[data-symbol]');
    for (let i = 0; i < items.length; i++) {
      items[i].classList.remove('active');
    }
    if (!symbol) return;
    const selector = 'a[data-symbol="' + escapeSelector(symbol) + '"]';
    const target = availableList.querySelector(selector);
    if (target) target.classList.add('active');
  }

  function getHoldingForSymbol(symbol) {
    if (!symbol) return null;
    const key = symbol.toUpperCase();
    if (Object.prototype.hasOwnProperty.call(holdingsMap, key)) {
      return holdingsMap[key];
    }
    return null;
  }

  function updateHoldingIndicators() {
    const holding = getHoldingForSymbol(state.currentSymbol);
    const ownedQty = holding ? Number(holding.quantity) : 0;
    const avgCost = holding ? Number(holding.avg_cost) : 0;

    state.currentHoldingQty = isFinite(ownedQty) ? ownedQty : 0;
    state.currentAvgCost = isFinite(avgCost) ? avgCost : 0;

    if (holdingQuantityDisplay) {
      const ownedText = state.currentHoldingQty > 0 ? formatShares(state.currentHoldingQty) + " sh" : '0 sh';
      holdingQuantityDisplay.innerHTML = "<i class=\"fas fa-layer-group mr-1\"></i>Shares owned: " + ownedText;
    }
    if (holdingValueDisplay) {
      const estValue = state.currentHoldingQty > 0 && state.livePrice > 0 ? state.currentHoldingQty * state.livePrice : 0;
      const valueText = estValue > 0 ? formatCurrency(estValue) : '—';
      holdingValueDisplay.innerHTML = "<i class=\"fas fa-chart-line mr-1\"></i>Est. value: " + valueText;
    }
    if (cashBalanceDisplay && isFinite(cashBalance)) {
      cashBalanceDisplay.innerHTML = "<i class=\"fas fa-wallet mr-1\"></i>Cash balance: " + formatCurrency(cashBalance);
    }
  }

  function showChartLoadingTimer() {
    const opened = openLoadingSwal({
      title: "Updating chart…",
      html: "Closing in <b></b> milliseconds.",
      timer: 2000,
      timerProgressBar: true,
      didOpen: (popup) => {
        const counter = popup ? popup.querySelector("b") : null;
        loadingSwalTimerInterval = setInterval(() => {
          if (!counter) return;
          const remaining = typeof Swal.getTimerLeft === 'function' ? Swal.getTimerLeft() : null;
          counter.textContent = typeof remaining === "number" ? `${remaining}` : "0";
        }, 100);
      },
      willClose: () => {
        if (loadingSwalTimerInterval) {
          clearInterval(loadingSwalTimerInterval);
          loadingSwalTimerInterval = null;
        }
      }
    });

    if (!opened && loadingSwalTimerInterval) {
      clearInterval(loadingSwalTimerInterval);
      loadingSwalTimerInterval = null;
    }
  }

  function closeChartLoadingTimer() {
    closeLoadingSwal();
  }

  function getActiveOrderMode() {
    const activeLink = document.querySelector('#ticket-mode-tabs .nav-link.active');
    const modeAttr = activeLink ? activeLink.getAttribute('data-mode') : null;
    if (modeAttr === 'amount') return 'amount';
    return 'shares';
  }

  function showOrderError(message) {
    if (window.Swal && typeof Swal.fire === 'function') {
      Swal.fire({
        icon: 'error',
        title: 'Order Not Ready',
        text: message
      });
    } else {
      alert(message);
    }
  }

  function buildOrderPayload() {
    const mode = getActiveOrderMode();
    const symbolInput = mode === 'shares' ? priceInputs.sharesSymbol : priceInputs.dollarSymbol;
    const symbolFromInput = symbolInput && symbolInput.value ? symbolInput.value.trim().toUpperCase() : '';
    const symbol = (state.currentSymbol || '').toUpperCase() || symbolFromInput;
    const livePrice = clampNumber(state.livePrice);
    const limitPriceRaw = priceInputs.sharesLimit ? Number(priceInputs.sharesLimit.value) : 0;
    const limitPrice = limitPriceRaw > 0 ? limitPriceRaw : livePrice;
    const sharesNote = priceInputs.sharesNote ? priceInputs.sharesNote.value.trim() : '';
    const amountNote = priceInputs.dollarNote ? priceInputs.dollarNote.value.trim() : '';

    if (!symbol) {
      showOrderError('Please select a stock before submitting an order.');
      return null;
    }

    let quantity = 0;
    let amount = 0;
    let note = '';

    if (mode === 'shares') {
      const qty = priceInputs.sharesQty ? Number(priceInputs.sharesQty.value) : 0;
      if (!(qty > 0)) {
        showOrderError('Enter the number of shares you would like to sell.');
        return null;
      }
      if (!(limitPrice > 0)) {
        showOrderError('Price information is missing. Please refresh the quote and try again.');
        return null;
      }
      quantity = qty;
      amount = quantity * limitPrice;
      note = sharesNote;
    } else {
      const investAmount = priceInputs.dollarAmount ? Number(priceInputs.dollarAmount.value) : 0;
      if (!(investAmount > 0)) {
        showOrderError('Enter the dollar amount you would like to receive.');
        return null;
      }
      if (!(livePrice > 0)) {
        showOrderError('Live price is unavailable. Please refresh the quote and try again.');
        return null;
      }
      amount = investAmount;
      quantity = investAmount / livePrice;
      note = amountNote;
    }

    if (!(quantity > 0)) {
      showOrderError('Calculated share quantity must be greater than zero.');
      return null;
    }

    const ownedQty = state.currentHoldingQty > 0 ? state.currentHoldingQty : 0;
    if (!(ownedQty > 0)) {
      showOrderError('You do not own any shares of ' + symbol + '.');
      return null;
    }
    if (quantity - ownedQty > HOLDING_TOLERANCE) {
      showOrderError('You only have ' + formatShares(ownedQty) + ' shares of ' + symbol + ' available to sell.');
      return null;
    }

    return {
      mode,
      symbol,
      quantity,
      limitPrice: limitPrice > 0 ? limitPrice : livePrice,
      amount,
      estimatedShares: quantity,
      priceSnapshot: livePrice,
      fees: TRADE_FEE,
      note
    };
  }

  function populateHiddenForm(payload) {
    if (!hiddenForm) {
      showOrderError('Unable to locate the order form.');
      return;
    }
    if (hiddenFields.orderMode) hiddenFields.orderMode.value = payload.mode;
    if (hiddenFields.symbol) hiddenFields.symbol.value = payload.symbol;
    if (hiddenFields.quantity) hiddenFields.quantity.value = payload.quantity.toFixed(4);
    if (hiddenFields.limitPrice) hiddenFields.limitPrice.value = payload.limitPrice.toFixed(4);
    if (hiddenFields.amount) hiddenFields.amount.value = payload.amount.toFixed(2);
    if (hiddenFields.estimatedShares) hiddenFields.estimatedShares.value = payload.estimatedShares.toFixed(4);
    if (hiddenFields.priceSnapshot) hiddenFields.priceSnapshot.value = payload.priceSnapshot.toFixed(4);
    if (hiddenFields.fees) hiddenFields.fees.value = payload.fees.toFixed(2);
    if (hiddenFields.note) hiddenFields.note.value = payload.note || '';

    if (typeof hiddenForm.requestSubmit === 'function') {
      hiddenForm.requestSubmit();
    } else {
      hiddenForm.submit();
    }
  }

  const symbolsQuery = symbols.map(function (sym) { return encodeURIComponent(sym); }).join(',');

  function setRefreshState(loading) {
    if (!refreshBtn) return;
    if (loading) {
      refreshBtn.disabled = true;
      refreshBtn.classList.add('opacity-50');
      if (refreshIcon) refreshIcon.classList.add('fa-spin');
    } else {
      refreshBtn.disabled = false;
      refreshBtn.classList.remove('opacity-50');
      if (refreshIcon) refreshIcon.classList.remove('fa-spin');
      refreshBtn.classList.remove('d-none');
    }
  }

  function renderChart(ohlcData) {
    if (!chartEl) return;
    if (!Array.isArray(ohlcData) || ohlcData.length === 0) {
      if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
      }
      chartEl.innerHTML = "<div class='text-center text-muted py-5'>No chart data available.</div>";
      return;
    }

    const options = {
      series: [{ data: ohlcData }],
      chart: { type: 'candlestick', height: 350, animations: { enabled: false }, toolbar: { show: false } },
      xaxis: { type: 'datetime' },
      yaxis: { tooltip: { enabled: true } }
    };

    if (!chartInstance) {
      chartEl.innerHTML = '';
      chartInstance = new ApexCharts(chartEl, options);
      chartInstance.render();
    } else {
      chartInstance.updateOptions(options, false, true);
    }
  }

  async function loadChart(symbol, range, options) {
    if (!symbol || !chartEl) return;
    const cfg = chartRangeConfig[range] || chartRangeConfig['1D'];
    const cacheKey = symbol + "|" + cfg.period + "|" + cfg.interval;
    const now = Date.now();
    const forceRefresh = options && options.forceRefresh;

    if (!forceRefresh && chartDataCache.has(cacheKey)) {
      const cached = chartDataCache.get(cacheKey);
      if (now - cached.timestamp < CHART_CACHE_DURATION_MS) {
        renderChart(cached.data);
        if (chartTitle) chartTitle.textContent = symbol + " Price Chart · " + range;
        return;
      }
    }

    if (chartAbortController) chartAbortController.abort();
    chartAbortController = new AbortController();

    try {
      showChartLoadingTimer();
      const url = apiBase + "/chart-history?symbol=" + encodeURIComponent(symbol) + "&period=" + cfg.period + "&interval=" + cfg.interval;
      const response = await fetch(url, { signal: chartAbortController.signal });
      if (!response.ok) throw new Error("Chart API responded with " + response.status);
      const json = await response.json();
      const raw = json && Array.isArray(json.data) ? json.data : [];
      const data = raw.map(function (item) {
        return { x: new Date(item.x).getTime(), y: item.y };
      });
      chartDataCache.set(cacheKey, { data: data, timestamp: now });
      renderChart(data);
      if (chartTitle) chartTitle.textContent = symbol + " Price Chart · " + range;
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Chart load failed:', error);
      chartEl.innerHTML = "<div class='text-center text-danger py-5'>Unable to load chart data.</div>";
    } finally {
      chartAbortController = null;
      closeChartLoadingTimer();
    }
  }

  function updateOrderEstimates() {
    const quantity = priceInputs.sharesQty ? Number(priceInputs.sharesQty.value) : 0;
    const limitPriceInput = priceInputs.sharesLimit ? Number(priceInputs.sharesLimit.value) : NaN;
    const limitPrice = limitPriceInput > 0 ? limitPriceInput : clampNumber(state.livePrice);
    const amount = priceInputs.dollarAmount ? Number(priceInputs.dollarAmount.value) : 0;

    const estProceeds = quantity > 0 && limitPrice > 0 ? quantity * limitPrice : 0;
    const estFees = TRADE_FEE;
    const ownedQty = state.currentHoldingQty > 0 ? state.currentHoldingQty : 0;
    const sharesRemaining = ownedQty > 0 ? Math.max(ownedQty - quantity, 0) : 0;
    const estShares = state.livePrice > 0 && amount > 0 ? amount / state.livePrice : 0;
    const execPrice = state.livePrice > 0 ? state.livePrice : limitPrice;

    if (summaryEls.sharesQuantity) summaryEls.sharesQuantity.textContent = quantity > 0 ? formatShares(quantity) + ' sh' : '—';
    if (summaryEls.sharesCost) summaryEls.sharesCost.textContent = estProceeds > 0 ? formatCurrency(estProceeds) : '—';
    if (summaryEls.sharesFees) summaryEls.sharesFees.textContent = formatCurrency(estFees);
    if (summaryEls.sharesBuyingPower) summaryEls.sharesBuyingPower.textContent = ownedQty > 0 ? formatShares(sharesRemaining) + ' sh' : '—';

    if (summaryEls.amountInvesting) summaryEls.amountInvesting.textContent = amount > 0 ? formatCurrency(amount) : '—';
    if (summaryEls.amountEstShares) summaryEls.amountEstShares.textContent = estShares > 0 ? formatShares(estShares) + ' sh' : '—';
    if (summaryEls.amountAvgPrice) summaryEls.amountAvgPrice.textContent = execPrice > 0 ? formatCurrency(execPrice) : '—';

    if (priceInputs.dollarEstimated) priceInputs.dollarEstimated.value = estShares > 0 ? estShares.toFixed(2) : '';
  }

  function updateUIWithStockData(data) {
    const stock = data && data.selected_stock ? data.selected_stock : null;
    if (!stock) return;

    const metrics = stock.metrics || {};
    const dayRange = metrics.day_range || {};
    const range52 = metrics["52w_range"] || {};
    const priceChange = stock.price_change || {};
    const priceChangeAmount = Number(priceChange.amount);
    const priceChangePct = priceChange.percentage;

    state.currentSymbol = stock.ticker;
    state.livePrice = Number(stock.last_price) || 0;

    if (els.symbol) els.symbol.textContent = stock.ticker || '—';
    if (els.company) els.company.textContent = (stock.company_name || 'Unknown') + " · " + (stock.exchange || '—');
    if (els.price) els.price.textContent = state.livePrice ? formatCurrency(state.livePrice) : '—';

    if (els.change) {
      els.change.classList.remove('text-success', 'text-danger');
      if (isFinite(priceChangeAmount)) {
        els.change.classList.add(priceChangeAmount >= 0 ? 'text-success' : 'text-danger');
        let pctText;
        if (typeof priceChangePct === 'string') {
          pctText = priceChangePct;
        } else if (isFinite(Number(priceChangePct))) {
          pctText = Number(priceChangePct).toFixed(2) + "%";
        } else {
          pctText = '—';
        }
        const formattedAmount = (priceChangeAmount >= 0 ? '+' : '') + formatDecimal(priceChangeAmount);
        els.change.textContent = formattedAmount + " (" + pctText + ")";
      } else {
        els.change.textContent = '—';
      }
    }

    if (els.open) els.open.textContent = metrics.open ? formatCurrency(metrics.open) : '—';
    if (els.prevClose) els.prevClose.textContent = metrics.previous_close ? formatCurrency(metrics.previous_close) : '—';
    if (els.dayRange) {
      if (isFinite(dayRange.low) && isFinite(dayRange.high)) {
        els.dayRange.textContent = formatCurrency(dayRange.low) + " - " + formatCurrency(dayRange.high);
      } else {
        els.dayRange.textContent = '—';
      }
    }
    if (els.range52) {
      if (isFinite(range52.low) && isFinite(range52.high)) {
        els.range52.textContent = formatCurrency(range52.low) + " - " + formatCurrency(range52.high);
      } else {
        els.range52.textContent = '—';
      }
    }
    if (els.volume) els.volume.textContent = fmtNum(metrics.volume);
    if (els.avgVolume) els.avgVolume.textContent = fmtNum(metrics.avg_volume);
    if (els.marketCap) els.marketCap.textContent = metrics.market_cap || '—';
    if (els.pe) els.pe.textContent = metrics.pe_ratio || '—';

    if (priceInputs.sharesSymbol) priceInputs.sharesSymbol.value = stock.ticker || '';
    if (priceInputs.dollarSymbol) priceInputs.dollarSymbol.value = stock.ticker || '';
    if (priceInputs.sharesLimit) priceInputs.sharesLimit.value = state.livePrice ? state.livePrice.toFixed(2) : '';

    updateHoldingIndicators();
    updateOrderEstimates();
    setActiveSymbol(stock.ticker);
    return loadChart(stock.ticker, state.currentRange);
  }

  async function fetchStock(symbol, options) {
    const forceRefresh = options && options.forceRefresh;
    const now = Date.now();
    let loaderActivated = false;

    if (!forceRefresh && stockDataCache.has(symbol)) {
      const cached = stockDataCache.get(symbol);
      if (now - cached.timestamp < CACHE_DURATION_MS) {
        return updateUIWithStockData(cached.data);
      }
    }

    if (stockAbortController) stockAbortController.abort();
    stockAbortController = new AbortController();
    loaderActivated = true;
    openLoadingSwal({
      title: "Loading " + (symbol ? symbol.toUpperCase() + " data…" : "stock data…")
    });

    try {
      const response = await fetch(apiBase + "/buy-sell?symbol=" + encodeURIComponent(symbol), { signal: stockAbortController.signal });
      if (!response.ok) throw new Error("Stock API responded with " + response.status);
      const data = await response.json();
      if (!data || !data.selected_stock) throw new Error("Invalid stock response");
      stockDataCache.set(symbol, { data: data, timestamp: now });
      return updateUIWithStockData(data);
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Stock load failed:', error);
      if (els.symbol) els.symbol.textContent = symbol;
      if (els.company) els.company.textContent = 'Unable to load data.';
    } finally {
      stockAbortController = null;
      if (loaderActivated) {
        closeLoadingSwal();
      }
    }
  }

  async function loadAvailableStocks(options) {
    if (!availableList) return;
    const forceRefresh = options && options.forceRefresh;
    let loaderActivated = false;

    if (forceRefresh || !availableList.getAttribute('data-loaded')) {
      availableList.innerHTML = "<div class='p-3 text-center text-muted'>Loading data...</div>";
    }

    setRefreshState(true);
    loaderActivated = true;
    openLoadingSwal({ title: "Loading available stocks…" });

    try {
      const response = await fetch(apiBase + "/buy-sell-info?symbols=" + symbolsQuery);
      if (!response.ok) throw new Error("List API responded with " + response.status);
      const data = await response.json();
      availableList.innerHTML = "";
      const fragment = document.createDocumentFragment();

      data.forEach(function (stock) {
        if (!stock || stock.error) return;
        const changePctValue = Number(stock.change_pct);
        const isUp = isFinite(changePctValue) ? changePctValue >= 0 : false;
        const changeClass = isUp ? 'text-success' : 'text-danger';
        const changePct = isFinite(changePctValue) ? (isUp ? '+' : '') + changePctValue.toFixed(2) + '%' : '—';
        const currentPrice = Number(stock.current_price);
        let tagsText;
        if (Array.isArray(stock.tags)) {
          tagsText = stock.tags.filter(Boolean).join(', ');
        } else {
          tagsText = stock.tags || '';
        }

        const holdingInfo = getHoldingForSymbol(stock.symbol);
        const ownedQty = holdingInfo ? Number(holdingInfo.quantity) : 0;
        const hasHolding = ownedQty > HOLDING_TOLERANCE;
        const ownedBadge = hasHolding ? `<span class="badge badge-danger ml-2">Owned ${formatShares(ownedQty)} sh</span>` : '';

        const item = document.createElement('a');
        item.href = '#';
        item.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
        item.setAttribute('data-symbol', stock.symbol);
        if (hasHolding) {
          item.classList.add('border', 'border-danger');
          item.setAttribute('data-owned', '1');
        }

        item.innerHTML = `
          <div>
            <h5 class="mb-0">
              <span class="mr-3">${stock.symbol}</span>
              <small class="text-muted">${stock.company_name || ''}</small>
              ${ownedBadge}
            </h5>
            <small class="text-muted">${stock.exchange || ''}${tagsText ? ' · ' + tagsText : ''}</small>
          </div>
          <div class="text-right">
            <span class="d-block font-weight-bold">${isFinite(currentPrice) ? formatCurrency(currentPrice) : '—'}</span>
            <span class="${changeClass}">${changePct}</span>
          </div>
        `;
        fragment.appendChild(item);
      });

      if (!fragment.childNodes.length) {
        availableList.innerHTML = "<div class='p-3 text-center text-muted'>No stocks available.</div>";
      } else {
        availableList.appendChild(fragment);
        availableList.setAttribute('data-loaded', '1');
        setActiveSymbol(state.currentSymbol);
      }
    } catch (error) {
      console.error('Failed to load stock list:', error);
      availableList.innerHTML = "<div class='p-3 text-center text-danger'>Unable to load stock list.</div>";
    } finally {
      setRefreshState(false);
      if (loaderActivated) {
        closeLoadingSwal();
      }
    }
  }

  function loadStock(symbol, options) {
    if (!symbol) return;
    state.currentSymbol = symbol;
    return fetchStock(symbol, options);
  }

  const debouncedLoadStock = debounce(function (symbol) {
    loadStock(symbol);
  }, 250);

  if (availableList) {
    availableList.addEventListener('click', function (event) {
      const link = event.target.closest('a[data-symbol]');
      if (!link) return;
      event.preventDefault();
      const symbol = link.getAttribute('data-symbol');
      debouncedLoadStock(symbol);
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      stockDataCache.clear();
      chartDataCache.clear();
      if (availableList) availableList.removeAttribute('data-loaded');
      loadAvailableStocks({ forceRefresh: true });
      if (state.currentSymbol) {
        loadStock(state.currentSymbol, { forceRefresh: true });
        loadChart(state.currentSymbol, state.currentRange, { forceRefresh: true });
      }
    });
  }

  if (rangeButtonsWrapper) {
    rangeButtonsWrapper.addEventListener('click', function (event) {
      const button = event.target.closest('button[data-range]');
      if (!button) return;
      const range = button.getAttribute('data-range');
      if (!range) return;
      state.currentRange = range;
      setActiveRangeButton(range);
      if (state.currentSymbol) {
        loadChart(state.currentSymbol, range);
      }
    });
  }

  ['input', 'change'].forEach(function (evt) {
    if (priceInputs.sharesQty) priceInputs.sharesQty.addEventListener(evt, updateOrderEstimates);
    if (priceInputs.sharesLimit) priceInputs.sharesLimit.addEventListener(evt, updateOrderEstimates);
    if (priceInputs.dollarAmount) priceInputs.dollarAmount.addEventListener(evt, updateOrderEstimates);
  });

  const clearButton = document.getElementById('clear-trade-btn');
  if (clearButton) {
    clearButton.addEventListener('click', function () {
      if (priceInputs.sharesQty) priceInputs.sharesQty.value = '';
      if (priceInputs.sharesLimit) priceInputs.sharesLimit.value = state.livePrice ? state.livePrice.toFixed(2) : '';
      if (priceInputs.dollarAmount) priceInputs.dollarAmount.value = '';
      if (priceInputs.dollarEstimated) priceInputs.dollarEstimated.value = '';
      updateOrderEstimates();
    });
  }

  const submitButton = document.getElementById('submit-trade-btn');
  if (submitButton) {
    submitButton.addEventListener('click', function () {
      const payload = buildOrderPayload();
      if (!payload) {
        return;
      }

      const previewLines = [
        "Mode: " + (payload.mode === 'shares' ? 'Sell by Shares' : 'Sell by Amount'),
        "Symbol: " + payload.symbol,
        "Quantity: " + payload.quantity.toFixed(4),
        "Price: " + formatCurrency(payload.limitPrice),
        "Estimated Proceeds: " + formatCurrency(payload.quantity * payload.limitPrice),
        "Fees: " + formatCurrency(payload.fees)
      ];
      if (payload.mode === 'amount') {
        previewLines.splice(3, 0, "Target Proceeds: " + formatCurrency(payload.amount));
      }
      previewLines.push("Note: " + (payload.note ? payload.note : '—'));

      const previewHtml = "<pre class='text-left mb-0'>" + previewLines.join("\n") + "</pre>";

      if (window.Swal && typeof window.Swal.fire === 'function') {
        Swal.fire({
          icon: 'info',
          title: 'Preview Sell Order',
          html: previewHtml,
          showCancelButton: true,
          confirmButtonText: 'Confirm Sell Order',
          cancelButtonText: 'Cancel'
        }).then(function (result) {
          if (result.isConfirmed) {
            populateHiddenForm(payload);
          }
        });
      } else {
        const confirmed = window.confirm(previewLines.join("\n"));
        if (confirmed) {
          populateHiddenForm(payload);
        }
      }
    });
  }

  setActiveRangeButton(state.currentRange);
  updateOrderEstimates();

  openLoadingSwal({ title: "Loading sell data…" });
  Promise.all([
    loadAvailableStocks(),
    loadStock(DEFAULT_SYMBOL)
  ]).catch(function () {
    // Initial data load failures are surfaced via inline UI messaging.
  }).finally(function () {
    closeLoadingSwal();
  });
});
</script>

<?php if ($tradeAlert) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var alertConfig = {
    icon: <?=json_encode($tradeAlert["type"] ?? "info")?>,
    title: <?=json_encode($tradeAlert["title"] ?? "Notice")?>,
    text: <?=json_encode($tradeAlert["text"] ?? "")?>
  };
  if (window.Swal && typeof Swal.fire === 'function') {
    Swal.fire(alertConfig).then(function () {
      if (alertConfig.icon === 'success') {
        window.location.href = window.location.href;
      }
    });
  } else {
    alert(alertConfig.title + "\n\n" + alertConfig.text);
    if (alertConfig.icon === 'success') {
      window.location.href = window.location.href;
    }
  }
});
</script>
<?php } ?>

<?php require "../lib/PAGE-bottom.php"; ?>
