<?php
require "../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$_ISADMIN = isset($_SESSION["user"]);
require "../lib/fetch.php";

if (!function_exists('recordActivity')) {
  function recordActivity($conn, $userId, $actionTitle, $pageName, $notes = null, $status = 'Success')
  {
    if (!$conn || !$userId) return;
    $allowedStatuses = ['Success', 'Pending', 'Failed', 'Warning'];
    if (!in_array($status, $allowedStatuses, true)) $status = 'Success';
    $device = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ipv4 = $ipv6 = null;
    if ($ip) {
      if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) $ipv4 = $ip;
      elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) $ipv6 = $ip;
    }
    $s = $conn->prepare("INSERT INTO activities (action_title,page_name,notes,status,device,ipv4,ipv6,user_id) VALUES (?,?,?,?,?,?,?,?)");
    if ($s) {
      $n = $notes !== '' ? $notes : null;
      $s->bind_param("sssssssi", $actionTitle, $pageName, $n, $status, $device, $ipv4, $ipv6, $userId);
      $s->execute(); $s->close();
    }
  }
}

// ─── helpers ──────────────────────────────────────────────────────────────────
function resolveSymbolId(mysqli $conn, string $symbol): ?int
{
  $symbol = strtoupper(preg_replace('/[^A-Z0-9\.\-\^]/', '', $symbol));
  $s = $conn->prepare("SELECT id FROM available_trade WHERE symbol = ?");
  if (!$s) return null;
  $s->bind_param("s", $symbol); $s->execute();
  $s->bind_result($id); $s->fetch(); $s->close();
  if ($id) return (int)$id;
  $s2 = $conn->prepare("INSERT INTO available_trade (symbol) VALUES (?)");
  if (!$s2) return null;
  $s2->bind_param("s", $symbol);
  $s2->execute(); $newId = $s2->insert_id; $s2->close();
  return $newId ?: null;
}

// ─── AJAX: create limit order ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_create_order'])) {
  header('Content-Type: application/json');
  $errors = [];

  $side      = strtoupper(trim($_POST['side']      ?? ''));
  $symbol    = strtoupper(preg_replace('/[^A-Z0-9\.\-\^]/', '', trim($_POST['symbol'] ?? '')));
  $trigger   = isset($_POST['trigger_price']) ? (float)$_POST['trigger_price'] : 0;
  $condition = in_array($_POST['condition'] ?? '', ['lte','gte']) ? $_POST['condition'] : 'lte';
  $mode      = trim($_POST['order_mode']  ?? 'shares');
  $qty       = isset($_POST['quantity'])  ? (float)$_POST['quantity']  : 0;
  $amount    = isset($_POST['amount'])    ? (float)$_POST['amount']    : 0;
  $pct       = isset($_POST['sell_pct'])  ? (float)$_POST['sell_pct'] : 0;
  $fees      = isset($_POST['fees'])      ? (float)$_POST['fees']     : 1.0;
  $note      = trim($_POST['note'] ?? '');

  if (!$conn)                                     $errors[] = "Database unavailable.";
  if (empty($active_user['user_id']))             $errors[] = "Session expired.";
  if (!in_array($side, ['BUY','SELL']))           $errors[] = "Invalid side.";
  if ($symbol === '')                             $errors[] = "Symbol required.";
  if ($trigger <= 0)                              $errors[] = "Trigger price must be positive.";
  if (!in_array($mode, ['shares','amount','all','pct'])) $errors[] = "Invalid order mode.";
  if ($mode === 'shares' && $qty <= 0)            $errors[] = "Share quantity must be positive.";
  if ($mode === 'amount' && $amount <= 0)         $errors[] = "Amount must be positive.";
  if ($mode === 'pct' && ($pct <= 0 || $pct > 100)) $errors[] = "Percent must be between 0 and 100.";
  if ($side === 'BUY' && in_array($mode, ['all','pct'], true)) $errors[] = "Invalid buy order mode.";
  if ($side === 'SELL' && !in_array($mode, ['shares','amount','all','pct'], true)) $errors[] = "Invalid sell order mode.";

  if (empty($errors)) {
    $userId   = (int) $active_user['user_id'];
    $symbolId = resolveSymbolId($conn, $symbol);
    if (!$symbolId) $errors[] = "Could not resolve symbol.";
  }

  if (empty($errors)) {
    $qtyVal  = ($mode === 'shares') ? $qty  : null;
    $amtVal  = ($mode === 'amount') ? $amount : null;
    $pctVal  = ($mode === 'pct')    ? $pct  : null;
    $feesVal = max($fees, 0);

    $stmt = $conn->prepare("INSERT INTO limit_orders
      (user_id,symbol_id,side,trigger_price,`condition`,order_mode,quantity,amount,sell_pct,fees,note)
      VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) { $errors[] = "Prepare failed."; }
    else {
      $stmt->bind_param("iisdssdddds",
        $userId, $symbolId, $side, $trigger, $condition, $mode, $qtyVal, $amtVal, $pctVal, $feesVal, $note);
      if (!$stmt->execute()) { $errors[] = "Insert failed: " . $stmt->error; }
      $newId = $stmt->insert_id;
      $stmt->close();
      recordActivity($conn, $userId, "Limit Order Created", "Limit Orders",
        "{$side} {$symbol} @ " . ($condition === 'lte' ? '≤' : '≥') . " \${$trigger}", 'Success');
    }
  }

  if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
  } else {
    echo json_encode(['success' => true, 'id' => $newId, 'message' => "Limit order placed for {$symbol}."]);
  }
  exit;
}

// ─── AJAX: cancel limit order ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_cancel_order'])) {
  header('Content-Type: application/json');
  $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
  $userId  = isset($active_user['user_id']) ? (int)$active_user['user_id'] : 0;
  if (!$orderId || !$userId || !$conn) {
    echo json_encode(['success' => false, 'message' => "Invalid request."]);
    exit;
  }
  $s = $conn->prepare("UPDATE limit_orders SET status='cancelled' WHERE id=? AND user_id=? AND status='active'");
  $s->bind_param("ii", $orderId, $userId); $s->execute();
  $affected = $s->affected_rows; $s->close();
  echo json_encode(['success' => $affected > 0, 'message' => $affected > 0 ? "Order cancelled." : "Order not found."]);
  exit;
}

// ─── AJAX: execute limit order ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_execute_order'])) {
  header('Content-Type: application/json');
  $orderId    = isset($_POST['order_id'])    ? (int)$_POST['order_id']    : 0;
  $livePrice  = isset($_POST['live_price'])  ? (float)$_POST['live_price'] : 0;
  $userId     = isset($active_user['user_id']) ? (int)$active_user['user_id'] : 0;

  if (!$orderId || !$userId || !$conn || $livePrice <= 0) {
    echo json_encode(['success' => false, 'message' => "Invalid request."]);
    exit;
  }

  // lock + fetch the order
  $stmtO = $conn->prepare("
    SELECT lo.id, lo.side, lo.order_mode, lo.quantity, lo.amount, lo.sell_pct,
           lo.fees, lo.note, at.symbol, lo.symbol_id
    FROM limit_orders lo
    INNER JOIN available_trade at ON at.id = lo.symbol_id
    WHERE lo.id = ? AND lo.user_id = ? AND lo.status = 'active'
    LIMIT 1
  ");
  $stmtO->bind_param("ii", $orderId, $userId);
  $stmtO->execute();
  $stmtO->bind_result($oId, $oSide, $oMode, $oQty, $oAmt, $oPct, $oFees, $oNote, $oSymbol, $oSymbolId);
  $found = (bool)$stmtO->fetch();
  $stmtO->close();

  if (!$found) {
    echo json_encode(['success' => false, 'message' => "Order not found or already executed."]);
    exit;
  }

  $transactionStarted = false;
  try {
    if (!$conn->begin_transaction()) throw new Exception("Cannot start transaction.");
    $transactionStarted = true;

    // ── BUY execution ──────────────────────────────────────────────────────
    if ($oSide === 'BUY') {
      // determine qty & cost
      if ($oMode === 'amount') {
        $finalQty   = (float)$oAmt / $livePrice;
        $tradeCost  = (float)$oAmt;
      } else {
        $finalQty  = (float)$oQty;
        $tradeCost = $finalQty * $livePrice;
      }
      $totalCost = $tradeCost + (float)$oFees;
      if ($finalQty <= 0) throw new Exception("Invalid quantity.");

      // balance check
      $sB = $conn->prepare("SELECT user_balance FROM users WHERE user_id=? FOR UPDATE");
      $sB->bind_param("i", $userId); $sB->execute();
      $sB->bind_result($balRaw); $sB->fetch(); $sB->close();
      $bal = (float)$balRaw;
      if ($bal < $totalCost) throw new Exception("Insufficient balance ($" . number_format($bal,2) . " < $" . number_format($totalCost,2) . ").");

      // insert transaction
      $qR = round($finalQty,4); $pR = round($livePrice,4); $fR = round((float)$oFees,2);
      $sT = $conn->prepare("INSERT INTO transaction (user_id,symbol_id,side,quantity,price,fees) VALUES (?,?,'BUY',?,?,?)");
      $sT->bind_param("iiddd", $userId, $oSymbolId, $qR, $pR, $fR);
      if (!$sT->execute()) throw new Exception("Transaction insert failed."); $sT->close();

      // update holding
      $sH = $conn->prepare("SELECT holding_id,quantity,avg_cost FROM holding WHERE user_id=? AND symbol_id=? LIMIT 1 FOR UPDATE");
      $sH->bind_param("ii", $userId, $oSymbolId); $sH->execute();
      $sH->bind_result($hId, $hQty, $hAvg); $hasH = (bool)$sH->fetch(); $sH->close();
      if ($hasH) {
        $newQty = (float)$hQty + $finalQty;
        $newAvg = round(((float)$hQty*(float)$hAvg + $tradeCost + (float)$oFees) / $newQty, 4);
        $newQtyR = round($newQty, 4);
        $sUH = $conn->prepare("UPDATE holding SET quantity=?,avg_cost=?,updated_at=NOW() WHERE holding_id=?");
        $sUH->bind_param("ddi", $newQtyR, $newAvg, $hId);
        if (!$sUH->execute()) throw new Exception("Holding update failed."); $sUH->close();
      } else {
        $avgCost = round(($tradeCost + (float)$oFees) / $finalQty, 4);
        $sIH = $conn->prepare("INSERT INTO holding (user_id,symbol_id,quantity,avg_cost,note) VALUES (?,?,?,?,?)");
        $noteV = $oNote ?: null;
        $sIH->bind_param("iidds", $userId, $oSymbolId, $qR, $avgCost, $noteV);
        if (!$sIH->execute()) throw new Exception("Holding insert failed."); $sIH->close();
      }

      // deduct balance
      $newBal = round($bal - $totalCost, 2);
      $sBU = $conn->prepare("UPDATE users SET user_balance=?,last_updated=NOW() WHERE user_id=?");
      $sBU->bind_param("di", $newBal, $userId);
      if (!$sBU->execute()) throw new Exception("Balance update failed."); $sBU->close();

      $execResult = ['side' => 'BUY', 'symbol' => $oSymbol, 'qty' => $qR, 'price' => $pR, 'newBalance' => $newBal];
    }

    // ── SELL execution ─────────────────────────────────────────────────────
    else {
      $sH = $conn->prepare("SELECT holding_id,quantity FROM holding WHERE user_id=? AND symbol_id=? LIMIT 1 FOR UPDATE");
      $sH->bind_param("ii", $userId, $oSymbolId); $sH->execute();
      $sH->bind_result($hId, $hQty); $hasH = (bool)$sH->fetch(); $sH->close();
      if (!$hasH || (float)$hQty <= 0) throw new Exception("No holdings of {$oSymbol} to sell.");

      $hQtyF = (float)$hQty;
      if ($oMode === 'all')   { $finalQty = $hQtyF; }
      elseif ($oMode === 'pct') { $finalQty = $hQtyF * min((float)$oPct / 100, 1); }
      elseif ($oMode === 'amount') { $finalQty = (float)$oAmt / $livePrice; }
      else { $finalQty = min((float)$oQty, $hQtyF); }
      $finalQty = min($finalQty, $hQtyF);
      if ($finalQty <= 0) throw new Exception("Invalid sell quantity.");

      $qR = round($finalQty,4); $pR = round($livePrice,4); $fR = round((float)$oFees,2);

      $sT = $conn->prepare("INSERT INTO transaction (user_id,symbol_id,side,quantity,price,fees) VALUES (?,?,'SELL',?,?,?)");
      $sT->bind_param("iiddd", $userId, $oSymbolId, $qR, $pR, $fR);
      if (!$sT->execute()) throw new Exception("Transaction insert failed."); $sT->close();

      $remaining = $hQtyF - $finalQty;
      $tol = 0.0001;
      if ($remaining <= $tol) {
        $sD = $conn->prepare("DELETE FROM holding WHERE holding_id=?");
        $sD->bind_param("i", $hId); $sD->execute(); $sD->close();
      } else {
        $remR = round($remaining, 4);
        $sUH = $conn->prepare("UPDATE holding SET quantity=?,updated_at=NOW() WHERE holding_id=?");
        $sUH->bind_param("di", $remR, $hId);
        if (!$sUH->execute()) throw new Exception("Holding update failed."); $sUH->close();
      }

      $sB = $conn->prepare("SELECT user_balance FROM users WHERE user_id=? FOR UPDATE");
      $sB->bind_param("i", $userId); $sB->execute();
      $sB->bind_result($balRaw); $sB->fetch(); $sB->close();
      $credit  = max(($finalQty * $livePrice) - (float)$oFees, 0);
      $newBal  = round((float)$balRaw + $credit, 2);
      $sBU = $conn->prepare("UPDATE users SET user_balance=?,last_updated=NOW() WHERE user_id=?");
      $sBU->bind_param("di", $newBal, $userId);
      if (!$sBU->execute()) throw new Exception("Balance update failed."); $sBU->close();

      $execResult = ['side' => 'SELL', 'symbol' => $oSymbol, 'qty' => $qR, 'price' => $pR, 'newBalance' => $newBal, 'remaining' => round(max($remaining,0),4)];
    }

    // mark order executed
    $sUp = $conn->prepare("UPDATE limit_orders SET status='executed',executed_price=?,executed_qty=?,executed_at=NOW() WHERE id=?");
    $sUp->bind_param("ddi", $pR, $qR, $orderId);
    if (!$sUp->execute()) throw new Exception("Order status update failed."); $sUp->close();

    if (!$conn->commit()) throw new Exception("Commit failed.");
    $transactionStarted = false;

    recordActivity($conn, $userId, "Limit Order Executed", "Limit Orders",
      "{$oSide} {$oSymbol} | Qty {$qR} | Px {$pR}", 'Success');

    echo json_encode(array_merge(['success' => true, 'message' => "Limit order executed: {$oSide} {$qR} {$oSymbol} @ \${$pR}."], $execResult));
  } catch (Exception $ex) {
    if ($transactionStarted) $conn->rollback();
    recordActivity($conn, $userId, "Limit Order Failed", "Limit Orders", $ex->getMessage(), 'Failed');
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
  }
  exit;
}

// ─── Page data ─────────────────────────────────────────────────────────────
$userId          = isset($active_user['user_id']) ? (int)$active_user['user_id'] : 0;
$availableCash   = isset($active_user['user_balance']) ? (float)$active_user['user_balance'] : 0.0;

// Holdings
$userHoldings = [];
if ($conn && $userId) {
  $s = $conn->prepare("
    SELECT at.symbol, h.quantity, h.avg_cost
    FROM holding h
    INNER JOIN available_trade at ON at.id = h.symbol_id
    WHERE h.user_id = ?
    ORDER BY h.updated_at DESC, at.symbol ASC
  ");
  $s->bind_param("i", $userId);
  if ($s->execute()) {
    $s->bind_result($hSym, $hQty, $hAvg);
    while ($s->fetch()) {
      $userHoldings[] = ['symbol' => strtoupper($hSym), 'quantity' => (float)$hQty, 'avg_cost' => (float)$hAvg];
    }
  }
  $s->close();
}

// Available symbols from DB
$availableSymbols = [];
if ($conn) {
  $r = $conn->query("SELECT symbol FROM available_trade ORDER BY symbol ASC");
  if ($r) { while ($row = $r->fetch_assoc()) { $sym = strtoupper(trim($row['symbol'])); if ($sym !== '') $availableSymbols[] = $sym; } $r->free(); }
}

// Active limit orders
$activeOrders = [];
if ($conn && $userId) {
  $s = $conn->prepare("
    SELECT lo.id, lo.side, at.symbol, lo.trigger_price, lo.`condition`,
           lo.order_mode, lo.quantity, lo.amount, lo.sell_pct, lo.fees, lo.note, lo.created_at
    FROM limit_orders lo
    INNER JOIN available_trade at ON at.id = lo.symbol_id
    WHERE lo.user_id = ? AND lo.status = 'active'
    ORDER BY lo.created_at DESC
  ");
  $s->bind_param("i", $userId);
  if ($s->execute()) {
    $s->bind_result($oId,$oSide,$oSym,$oTrig,$oCond,$oMode,$oQty,$oAmt,$oPct,$oFees,$oNote,$oCreated);
    while ($s->fetch()) {
      $activeOrders[] = [
        'id' => $oId, 'side' => $oSide, 'symbol' => strtoupper($oSym),
        'trigger_price' => (float)$oTrig, 'condition' => $oCond,
        'order_mode' => $oMode, 'quantity' => (float)$oQty, 'amount' => (float)$oAmt,
        'sell_pct' => (float)$oPct, 'fees' => (float)$oFees,
        'note' => $oNote, 'created_at' => $oCreated
      ];
    }
  }
  $s->close();
}

// Recent executed limit orders (24 h)
$recentExecuted = [];
if ($conn && $userId) {
  $s = $conn->prepare("
    SELECT lo.side, at.symbol, lo.executed_qty, lo.executed_price, lo.fees, lo.executed_at
    FROM limit_orders lo
    INNER JOIN available_trade at ON at.id = lo.symbol_id
    WHERE lo.user_id = ? AND lo.status = 'executed'
      AND lo.executed_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY lo.executed_at DESC
    LIMIT 20
  ");
  $s->bind_param("i", $userId);
  if ($s->execute()) {
    $s->bind_result($eSide,$eSym,$eQty,$ePx,$eFees,$eAt);
    while ($s->fetch()) {
      $recentExecuted[] = ['side'=>$eSide,'symbol'=>strtoupper($eSym),'quantity'=>(float)$eQty,'price'=>(float)$ePx,'fees'=>(float)$eFees,'executed_at'=>$eAt];
    }
  }
  $s->close();
}

// Watchlist
$watchlistItems = [];
$watchlistTotal = 0;
if ($conn && $userId) {
  $s = $conn->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
  $s->bind_param("i", $userId); $s->execute(); $s->bind_result($wc); $s->fetch(); $watchlistTotal = (int)$wc; $s->close();
  $s = $conn->prepare("
    SELECT w.watchlist_id, at.symbol, w.note, w.created_at
    FROM watchlist w INNER JOIN available_trade at ON at.id = w.symbol_id
    WHERE w.user_id = ? ORDER BY w.created_at DESC LIMIT 6
  ");
  $s->bind_param("i", $userId);
  if ($s->execute()) {
    $s->bind_result($wId,$wSym,$wNote,$wCreated);
    while ($s->fetch()) $watchlistItems[] = ['id'=>(int)$wId,'symbol'=>strtoupper($wSym),'note'=>$wNote,'created_at'=>$wCreated,'price'=>null,'change_pct'=>null];
  }
  $s->close();
}
if (!empty($watchlistItems)) {
  $wSyms = array_unique(array_column($watchlistItems,'symbol'));
  $snap = [];
  foreach (array_chunk($wSyms,10) as $chunk) {
    $resp = @file_get_contents("https://api.paper-trade.org/buy-sell-info?symbols=" . implode(',',array_map('urlencode',$chunk)));
    if ($resp) { $d = json_decode($resp,true); if (is_array($d)) foreach ($d as $it) if (!empty($it['symbol'])) $snap[strtoupper($it['symbol'])] = $it; }
  }
  foreach ($watchlistItems as &$e) {
    $sn = $snap[$e['symbol']] ?? null;
    if ($sn) { if (isset($sn['current_price'])) $e['price']=(float)$sn['current_price']; if (isset($sn['change_pct'])) $e['change_pct']=(float)$sn['change_pct']; }
  } unset($e);
}

$defaultSymbol = !empty($userHoldings) ? $userHoldings[0]['symbol'] : (!empty($availableSymbols) ? $availableSymbols[0] : 'NVDA');
if (!empty($_SESSION['last_trade_symbol'])) {
  $c = strtoupper(preg_replace('/[^A-Z0-9\.\-\^]/','',$_SESSION['last_trade_symbol']));
  if ($c) $defaultSymbol = $c;
}

require "../lib/PAGE-top.php";
?>

<section class="content">
  <div class="container-fluid">

    <div class="row align-items-center mb-3">
      <div class="col-lg-8">
        <h1 class="h3 mb-1"><i class="fas fa-layer-group text-warning mr-2"></i>Limit Orders</h1>
        <p class="text-muted mb-0">Place buy and sell limit orders that execute automatically while this page is open — no cron required.</p>
      </div>
    </div>
    <hr>

    <div class="row">

      <!-- ══ LEFT COLUMN ══════════════════════════════════════════════════════ -->
      <div class="col-xl-4">

        <!-- Selected Stock -->
        <div class="card card-outline card-warning mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Selected Stock</h3>
            <span class="badge badge-warning text-dark">Live</span>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h2 class="mb-0" id="selected-symbol">Loading...</h2>
                <p class="text-muted mb-3" id="selected-company">Loading company name</p>
              </div>
              <div class="text-right">
                <small class="text-muted d-block">Last Price</small>
                <h2 class="mb-0" id="selected-price">--.--</h2>
                <span class="text-muted" id="selected-change">Loading change...</span>
              </div>
            </div>
            <div class="row pt-3">
              <div class="col-6"><small class="text-muted">Open</small><p id="selected-open" class="font-weight-bold mb-3">Loading...</p></div>
              <div class="col-6"><small class="text-muted">Prev Close</small><p id="selected-prev-close" class="font-weight-bold mb-3">Loading...</p></div>
              <div class="col-6"><small class="text-muted">Day Range</small><p id="selected-day-range" class="font-weight-bold mb-3">Loading...</p></div>
              <div class="col-6"><small class="text-muted">52W Range</small><p id="selected-52w-range" class="font-weight-bold mb-3">Loading...</p></div>
              <div class="col-6"><small class="text-muted">Volume</small><p id="selected-volume" class="font-weight-bold mb-3">Loading...</p></div>
              <div class="col-6"><small class="text-muted">Avg Volume</small><p id="selected-avg-volume" class="font-weight-bold mb-3">Loading...</p></div>
              <div class="col-6"><small class="text-muted">Market Cap</small><p id="selected-market-cap" class="font-weight-bold mb-0">Loading...</p></div>
              <div class="col-6"><small class="text-muted">P/E Ratio</small><p id="selected-pe" class="font-weight-bold mb-0">Loading...</p></div>
            </div>
          </div>
          <div class="card-footer bg-white">
            <small class="text-muted">Updated at <?= date('Y-m-d H:i:s') ?> CST</small>
          </div>
        </div>

        <!-- Holdings -->
        <div class="card card-outline card-danger mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Your Holdings</h3>
            <span class="badge badge-danger"><?= count($userHoldings) ?> Position<?= count($userHoldings) !== 1 ? 's' : '' ?></span>
          </div>
          <div class="card-body p-0" id="holdings-list">
            <?php if (!empty($userHoldings)) { ?>
              <div class="list-group list-group-flush">
                <?php foreach ($userHoldings as $h) { ?>
                  <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                     data-symbol="<?= htmlspecialchars($h['symbol']) ?>"
                     data-qty="<?= $h['quantity'] ?>"
                     data-avg="<?= $h['avg_cost'] ?>">
                    <div>
                      <strong><?= htmlspecialchars($h['symbol']) ?></strong>
                      <div class="small text-muted"><?= number_format($h['quantity'],4) ?> sh · avg $<?= number_format($h['avg_cost'],2) ?></div>
                    </div>
                    <div class="text-right">
                      <span class="badge badge-secondary holding-live-price" id="hlp-<?= htmlspecialchars($h['symbol']) ?>">—</span>
                    </div>
                  </a>
                <?php } ?>
              </div>
            <?php } else { ?>
              <div class="text-center text-muted py-4 small">No open positions.</div>
            <?php } ?>
          </div>
        </div>

        <!-- Available Stocks -->
        <div class="card card-outline card-primary mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Available Stocks</h3>
            <button class="btn btn-tool d-none" type="button" id="refresh-stocks"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div id="available-stocks" class="list-group list-group-flush">
            <div class="p-3 text-center text-muted">Loading data...</div>
          </div>
          <div class="card-footer bg-white">
            <small class="text-muted">Screeners refreshed at <?= date('Y-m-d H:i:s') ?> CST</small>
          </div>
        </div>

        <!-- Watchlist -->
        <div class="card card-outline card-info mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Pinned Watchlist</h3>
            <span class="badge badge-info">Tracked · <?= number_format($watchlistTotal) ?></span>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php if (!empty($watchlistItems)) { ?>
                <?php foreach ($watchlistItems as $w) {
                  $chg = $w['change_pct'];
                  $cls = $chg === null ? 'badge-secondary' : ($chg >= 0 ? 'badge-success' : 'badge-danger');
                  $txt = $chg !== null ? (($chg >= 0 ? '+' : '') . number_format($chg,2) . '%') : '—';
                ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <strong><?= htmlspecialchars($w['symbol']) ?></strong>
                      <?php if (!empty($w['note'])) { ?><div class="small text-muted"><?= htmlspecialchars($w['note']) ?></div><?php } ?>
                      <div class="small text-muted"><?= !empty($w['created_at']) ? date('M d, Y',strtotime($w['created_at'])) : '' ?></div>
                    </div>
                    <div class="text-right">
                      <div class="font-weight-bold"><?= $w['price'] !== null ? '$'.number_format((float)$w['price'],2,'.','') : '—' ?></div>
                      <span class="badge <?= $cls ?>"><?= $txt ?></span>
                    </div>
                  </li>
                <?php } ?>
              <?php } else { ?>
                <li class="list-group-item text-center text-muted py-4">No watchlist items.</li>
              <?php } ?>
            </ul>
          </div>
        </div>

      </div><!-- /left -->

      <!-- ══ RIGHT COLUMN ═════════════════════════════════════════════════════ -->
      <div class="col-xl-8">

        <!-- Chart -->
        <div class="card card-outline card-warning mb-4">
          <h3 class="card-title mb-0 text-center mt-2" id="selected-symbol-chart">Loading....</h3>
          <div class="card-body">
            <div id="chart"></div>
            <div class="card-header d-flex justify-content-center align-items-center flex-wrap gap-2">
              <div class="btn-group btn-group-sm" role="group" id="chart-range-buttons">
                <button type="button" class="btn btn-outline-primary active" data-range="1D">1D</button>
                <button type="button" class="btn btn-outline-primary" data-range="1W">1W</button>
                <button type="button" class="btn btn-outline-primary" data-range="1M">1M</button>
                <button type="button" class="btn btn-outline-primary" data-range="YTD">YTD</button>
                <button type="button" class="btn btn-outline-primary" data-range="1Yr">1Yr</button>
                <button type="button" class="btn btn-outline-primary" data-range="3Yr">3Yr</button>
                <button type="button" class="btn btn-outline-primary" data-range="5Yr">5Yr</button>
              </div>
              <div class="btn-group btn-group-sm ml-2" role="group" id="chart-type-toggle">
                <button type="button" class="btn btn-outline-warning active" data-chart-type="candlestick">Candles</button>
                <button type="button" class="btn btn-outline-warning" data-chart-type="line">Line</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Ticket -->
        <div class="card card-outline card-warning mb-4">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Limit Order Ticket</h3>
            <small class="text-muted">Order triggers and executes automatically while this page stays open</small>
          </div>
          <div class="card-body">

            <!-- BUY / SELL tabs -->
            <ul class="nav nav-pills mb-4" id="order-side-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active bg-success text-white" id="tab-buy" data-toggle="tab" href="#pane-buy" role="tab" data-side="BUY">
                  <i class="fas fa-arrow-up mr-1"></i>Buy Limit
                </a>
              </li>
              <li class="nav-item ml-2">
                <a class="nav-link bg-danger text-white" id="tab-sell" data-toggle="tab" href="#pane-sell" role="tab" data-side="SELL">
                  <i class="fas fa-arrow-down mr-1"></i>Sell Limit
                </a>
              </li>
            </ul>

            <div class="tab-content" id="order-tab-content">

              <!-- ── BUY PANE ── -->
              <div class="tab-pane fade show active" id="pane-buy" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <div class="form-row">
                      <div class="form-group col-sm-6">
                        <label>Symbol</label>
                        <input type="text" class="form-control" id="buy-symbol" placeholder="e.g. AAPL" readonly>
                      </div>
                      <div class="form-group col-sm-6">
                        <label>Trigger Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="buy-trigger" placeholder="e.g. 150.00">
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-sm-6">
                        <label>Condition</label>
                        <select class="form-control" id="buy-condition">
                          <option value="lte">Price ≤ trigger (buy dip)</option>
                          <option value="gte">Price ≥ trigger (breakout)</option>
                        </select>
                      </div>
                      <div class="form-group col-sm-6">
                        <label>Order Mode</label>
                        <select class="form-control" id="buy-mode">
                          <option value="shares">Buy by Shares</option>
                          <option value="amount">Buy by Amount ($)</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-sm-6" id="buy-qty-group">
                        <label id="buy-qty-label">Shares</label>
                        <div class="input-group">
                          <input type="number" step="0.0001" class="form-control" id="buy-qty" placeholder="e.g. 10">
                          <div class="input-group-append">
                            <button type="button" class="btn btn-outline-warning" id="buy-max-btn">Max</button>
                          </div>
                        </div>
                      </div>
                      <div class="form-group col-sm-6">
                        <label>Note</label>
                        <input type="text" class="form-control" id="buy-note" placeholder="Optional">
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light mb-3">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-7">Symbol</dt><dd class="col-5 text-right" id="buy-prev-symbol">—</dd>
                        <dt class="col-7">Trigger</dt><dd class="col-5 text-right" id="buy-prev-trigger">—</dd>
                        <dt class="col-7">Condition</dt><dd class="col-5 text-right" id="buy-prev-cond">—</dd>
                        <dt class="col-7">Est. Qty</dt><dd class="col-5 text-right" id="buy-prev-qty">—</dd>
                        <dt class="col-7">Est. Cost</dt><dd class="col-5 text-right" id="buy-prev-cost">—</dd>
                        <dt class="col-7">Fees</dt><dd class="col-5 text-right" id="buy-prev-fees">$1.00</dd>
                        <dt class="col-7">Cash After</dt><dd class="col-5 text-right" id="buy-prev-cash">—</dd>
                      </dl>
                    </div>
                    <ul class="list-unstyled small mb-0">
                      <li class="mb-1"><i class="fas fa-sync-alt text-info mr-2"></i>Price polled every 5 s</li>
                      <li class="mb-1"><i class="fas fa-bell text-warning mr-2"></i>Confirmation before buying</li>
                      <li><i class="fas fa-database text-success mr-2"></i>Order saved to database</li>
                    </ul>
                  </div>
                </div>
              </div><!-- /buy pane -->

              <!-- ── SELL PANE ── -->
              <div class="tab-pane fade" id="pane-sell" role="tabpanel">

                <!-- not-held overlay — shown when selected stock is not in holdings -->
                <div id="sell-no-holding" class="alert alert-warning d-flex align-items-center mb-3" style="display:none!important">
                  <i class="fas fa-lock fa-lg mr-3"></i>
                  <div>
                    <strong id="sell-no-holding-sym">This stock</strong> is not in your holdings.
                    <span class="d-block small text-muted mt-1">Click a position from <strong>Your Holdings</strong> on the left to set a sell limit order.</span>
                  </div>
                </div>

                <!-- held banner — shown when selected stock IS in holdings -->
                <div id="sell-holding-info" class="alert alert-success d-flex align-items-center mb-3" style="display:none!important">
                  <i class="fas fa-check-circle fa-lg mr-3"></i>
                  <div class="small">
                    Sell limit for <strong id="sell-info-sym">—</strong> &nbsp;·&nbsp;
                    You hold <strong id="sell-info-qty">—</strong> shares &nbsp;·&nbsp;
                    Avg cost <strong id="sell-info-avg">—</strong>
                  </div>
                </div>

                <div id="sell-form-fields">
                  <div class="row">
                    <div class="col-lg-7">
                      <!-- hidden symbol carrier (no dropdown) -->
                      <input type="hidden" id="sell-symbol">

                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label>Trigger Price ($)</label>
                          <input type="number" step="0.01" class="form-control" id="sell-trigger" placeholder="e.g. 100.00">
                        </div>
                        <div class="form-group col-sm-6">
                          <label>Condition</label>
                          <select class="form-control" id="sell-condition">
                            <option value="lte">Price ≤ trigger (stop-loss)</option>
                            <option value="gte">Price ≥ trigger (take-profit)</option>
                          </select>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label>Sell Mode</label>
                          <select class="form-control" id="sell-mode">
                            <option value="all">Sell All Shares</option>
                            <option value="shares">Fixed Qty</option>
                            <option value="pct">% of Position</option>
                            <option value="amount">By Amount ($)</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6" id="sell-qty-group" style="display:none">
                          <label id="sell-qty-label">Qty / % / $</label>
                          <div class="input-group">
                            <input type="number" step="0.0001" class="form-control" id="sell-qty" placeholder="e.g. 5">
                            <div class="input-group-append">
                              <button type="button" class="btn btn-outline-danger" id="sell-max-btn">Max</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-12">
                          <label>Note</label>
                          <input type="text" class="form-control" id="sell-note" placeholder="Optional">
                        </div>
                      </div>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light mb-3">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-7">Symbol</dt><dd class="col-5 text-right" id="sell-prev-symbol">—</dd>
                        <dt class="col-7">Trigger</dt><dd class="col-5 text-right" id="sell-prev-trigger">—</dd>
                        <dt class="col-7">Condition</dt><dd class="col-5 text-right" id="sell-prev-cond">—</dd>
                        <dt class="col-7">Est. Qty</dt><dd class="col-5 text-right" id="sell-prev-qty">—</dd>
                        <dt class="col-7">Est. Proceeds</dt><dd class="col-5 text-right" id="sell-prev-proceeds">—</dd>
                        <dt class="col-7">Fees</dt><dd class="col-5 text-right" id="sell-prev-fees">$1.00</dd>
                      </dl>
                    </div>
                    <ul class="list-unstyled small mb-0">
                      <li class="mb-1"><i class="fas fa-sync-alt text-info mr-2"></i>Price polled every 5 s</li>
                      <li class="mb-1"><i class="fas fa-bell text-warning mr-2"></i>Confirmation before selling</li>
                      <li><i class="fas fa-database text-danger mr-2"></i>Order saved to database</li>
                    </ul>
                  </div>
                </div>
                </div><!-- /sell-form-fields -->
              </div><!-- /sell pane -->

            </div><!-- /tab-content -->
          </div>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-column flex-md-row">
            <div class="text-muted small mb-3 mb-md-0">
              <i class="fas fa-wallet mr-1"></i>Available cash:
              <strong id="cash-display">$<?= number_format($availableCash,2,'.','') ?></strong>
            </div>
            <div>
              <button type="button" class="btn btn-outline-secondary mr-2" id="clear-ticket-btn"><i class="fas fa-undo"></i> Clear</button>
              <button type="button" class="btn btn-warning" id="place-order-btn"><i class="fas fa-layer-group mr-1"></i>Place Limit Order</button>
            </div>
          </div>
        </div>

        <!-- Active Limit Orders -->
        <div class="card card-outline card-warning mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Active Limit Orders</h3>
            <span class="badge badge-warning text-dark" id="active-orders-badge"><?= count($activeOrders) ?> active</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr><th>Side</th><th>Symbol</th><th>Condition</th><th>Trigger</th><th>Current</th><th>Mode</th><th>Status</th><th></th></tr>
              </thead>
              <tbody id="active-orders-body">
                <?php if (!empty($activeOrders)) { ?>
                  <?php foreach ($activeOrders as $o) {
                    $condLabel = ($o['condition'] === 'lte' ? 'Price ≤' : 'Price ≥') . ' $' . number_format($o['trigger_price'],2);
                    $modeLabel = match($o['order_mode']) { 'all'=>'All shares', 'pct'=>$o['sell_pct'].'% of pos.', 'amount'=>'$'.number_format($o['amount'],2), default=>number_format($o['quantity'],4).' sh' };
                    $sideCls   = $o['side'] === 'BUY' ? 'success' : 'danger';
                  ?>
                    <tr data-order-id="<?= $o['id'] ?>" data-symbol="<?= htmlspecialchars($o['symbol']) ?>"
                        data-trigger="<?= $o['trigger_price'] ?>" data-condition="<?= $o['condition'] ?>">
                      <td><span class="badge badge-<?= $sideCls ?>"><?= $o['side'] ?></span></td>
                      <td><strong><?= htmlspecialchars($o['symbol']) ?></strong></td>
                      <td class="small"><?= $condLabel ?></td>
                      <td>$<?= number_format($o['trigger_price'],2) ?></td>
                      <td class="live-price-cell" id="ord-price-<?= $o['id'] ?>">—</td>
                      <td class="small"><?= $modeLabel ?></td>
                      <td><span class="badge badge-success"><i class="fas fa-clock mr-1"></i>Watching</span></td>
                      <td>
                        <button class="btn btn-sm btn-outline-danger cancel-order-btn" data-id="<?= $o['id'] ?>" title="Cancel"><i class="fas fa-times"></i></button>
                      </td>
                    </tr>
                  <?php } ?>
                <?php } else { ?>
                  <tr id="no-orders-row"><td colspan="8" class="text-center text-muted py-4">No active limit orders. Place one above.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recent Executions -->
        <div class="card card-outline card-primary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Recent Executions</h3>
            <span class="text-muted small">Past 24 hours</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Side</th><th>Symbol</th><th>Qty</th><th>Price</th><th>Fees</th><th>Time</th></tr></thead>
              <tbody id="recent-exec-body">
                <?php if (!empty($recentExecuted)) { ?>
                  <?php foreach ($recentExecuted as $e) {
                    $ts = !empty($e['executed_at']) ? strtotime($e['executed_at']) : 0;
                    $tc = $e['side'] === 'BUY' ? 'success' : 'danger';
                  ?>
                    <tr>
                      <td><span class="badge badge-<?= $tc ?>"><?= $e['side'] ?></span></td>
                      <td><?= htmlspecialchars($e['symbol']) ?></td>
                      <td><?= number_format($e['quantity'],4,'.',',') ?></td>
                      <td>$<?= number_format($e['price'],2,'.',',') ?></td>
                      <td>$<?= number_format($e['fees'],2,'.',',') ?></td>
                      <td><?= $ts ? date('M d, Y H:i',$ts) : '—' ?></td>
                    </tr>
                  <?php } ?>
                <?php } else { ?>
                  <tr><td colspan="6" class="text-center text-muted py-4">No executions in the last 24 hours.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /right -->
    </div><!-- /row -->
  </div>
</section>

<style>
#available-stocks a.active,
#holdings-list a.active {
  background-color: rgba(255,193,7,.12);
  border-left: 4px solid #ffc107;
  font-weight: 600;
}
#available-stocks a:hover, #holdings-list a:hover { background-color: rgba(255,193,7,.18); }
#available-stocks a.active h5,
#available-stocks a.active h5 span,
#available-stocks a.active h5 small,
#available-stocks a.active .d-block.font-weight-bold,
#holdings-list a.active strong,
#holdings-list a.active .small { color:#000 !important; }
.order-triggered td { background-color: rgba(255,193,7,.15) !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const SELF_URL      = window.location.href.split('?')[0];
  const API_BASE      = "https://api.paper-trade.org";
  const TRADE_FEE     = 1;
  const POLL_MS       = 5000;
  const CHART_REFRESH = 15000;
  const CACHE_MS      = 60000;
  const CHART_CACHE   = 120000;

  // PHP → JS data
  const DB_SYMBOLS    = <?= json_encode($availableSymbols) ?>;
  const HOLDINGS      = <?= json_encode(array_column($userHoldings, null, 'symbol')) ?>;
  let   availableCash = <?= json_encode($availableCash) ?>;

  // ── State ────────────────────────────────────────────────────────────────
  const state = {
    symbol: null, range: '1D', livePrice: 0,
    chartType: (()=>{ try{return localStorage.getItem('trade:chartType')==='line'?'line':'candlestick';}catch(e){return'candlestick';} })(),
    lastChart: null, priceMap: {}
  };
  const stockCache = new Map(), chartCache = new Map();
  let chartInst = null, activeChartType = state.chartType;
  let stockAbort = null, chartAbort = null, pollTimer = null, lastChartUpdate = 0;
  const triggeredOrders = new Set();

  // active orders list (mirrors DOM rows, keyed by order id)
  let activeOrders = <?= json_encode(array_values($activeOrders)) ?>;

  // ── DOM ──────────────────────────────────────────────────────────────────
  const elSym      = id('selected-symbol'),   elCo  = id('selected-company');
  const elPx       = id('selected-price'),    elChg = id('selected-change');
  const elOpen     = id('selected-open'),     elPC  = id('selected-prev-close');
  const elDR       = id('selected-day-range'),elR52 = id('selected-52w-range');
  const elVol      = id('selected-volume'),   elAV  = id('selected-avg-volume');
  const elMC       = id('selected-market-cap'),elPE = id('selected-pe');
  const chartEl    = id('chart'), chartTitle = id('selected-symbol-chart');
  const rangeWrap  = id('chart-range-buttons'), typeToggle = id('chart-type-toggle');
  const holdList   = id('holdings-list'), availList = id('available-stocks');
  const refreshBtn = id('refresh-stocks'), refreshIcon = refreshBtn ? refreshBtn.querySelector('i') : null;
  const cashDisp   = id('cash-display');
  const activeBody = id('active-orders-body'), activeBadge = id('active-orders-badge');
  const recentBody = id('recent-exec-body');

  function id(x){ return document.getElementById(x); }

  // ── Helpers ───────────────────────────────────────────────────────────────
  function toN(v){ if(typeof v==='number') return isFinite(v)?v:NaN; const s=String(v).replace(/[^0-9.\-]/g,''); return s?Number(s):NaN; }
  function fmt(v){ const n=toN(v); return isFinite(n)?n.toLocaleString('en-US',{style:'currency',currency:'USD'}):'—'; }
  function clamp(v){ const n=toN(v); return isFinite(n)?n:0; }
  function roundTo(v, decimals){ const n = Number(v); if(!isFinite(n)) return 0; const factor = Math.pow(10, decimals || 0); return Math.round(n * factor) / factor; }
  function big(v){ const n=toN(v),a=Math.abs(n),s=n<0?'-':''; if(!isFinite(n))return'—'; if(a>=1e12)return s+(a/1e12).toFixed(2)+'T'; if(a>=1e9)return s+(a/1e9).toFixed(2)+'B'; if(a>=1e6)return s+(a/1e6).toFixed(1)+'M'; return s+a.toLocaleString(); }
  function esc(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  function debounce(fn,d){ let t; return function(){ const a=arguments; clearTimeout(t); t=setTimeout(()=>fn.apply(null,a),d||250); }; }
  function escSel(v){ return window.CSS&&CSS.escape?CSS.escape(v):String(v).replace(/([ !"#$%&'()*+,./:;<=>?@\[\\\]^`{|}~])/g,'\\$1'); }
  async function readJsonResponse(res){
    const text = await res.text();
    if (!res.ok) throw new Error('Request failed with status ' + res.status + '.');
    try {
      return JSON.parse(text);
    } catch (err) {
      const start = text.indexOf('{');
      const end = text.lastIndexOf('}');
      if (start !== -1 && end !== -1 && end > start) {
        return JSON.parse(text.slice(start, end + 1));
      }
      throw new Error('Invalid JSON response.');
    }
  }
  async function postSelfJson(formData){
    const res = await fetch(SELF_URL,{method:'POST',body:formData,credentials:'same-origin'});
    return readJsonResponse(res);
  }
  function getHoldingQty(sym){ const holding = sym ? HOLDINGS[sym] : null; return holding ? clamp(holding.quantity) : 0; }
  function getBuyMaxValue(){
    const mode = (id('buy-mode')||{}).value || 'shares';
    const trigger = Number((id('buy-trigger')||{}).value);
    const price = trigger > 0 ? trigger : state.livePrice;
    const spendableCash = Math.max(clamp(availableCash) - TRADE_FEE, 0);
    if (mode === 'amount') return roundTo(spendableCash, 2);
    if (!(price > 0) || !(spendableCash > 0)) return 0;
    return roundTo(spendableCash / price, 4);
  }
  function getSellMaxValue(){
    const sym = (id('sell-symbol')||{}).value || '';
    const mode = (id('sell-mode')||{}).value || 'all';
    const trigger = Number((id('sell-trigger')||{}).value);
    const livePrice = trigger > 0 ? trigger : state.livePrice;
    const holdingQty = getHoldingQty(sym);
    if (!(holdingQty > 0)) return 0;
    if (mode === 'pct') return 100;
    if (mode === 'amount') return livePrice > 0 ? roundTo(holdingQty * livePrice, 2) : 0;
    return roundTo(holdingQty, 4);
  }
  function applyInputLimit(inputEl, maxValue, decimals){
    if(!inputEl) return 0;
    const normalizedMax = Math.max(Number(maxValue) || 0, 0);
    if (normalizedMax > 0) inputEl.max = String(normalizedMax);
    else inputEl.removeAttribute('max');
    const current = Number(inputEl.value);
    if (inputEl.value !== '' && isFinite(current)) {
      let next = current;
      if (current < 0) next = 0;
      if (normalizedMax > 0 && current > normalizedMax) next = normalizedMax;
      if (next !== current) inputEl.value = roundTo(next, decimals).toFixed(decimals);
    }
    return normalizedMax;
  }
  function fillMaxBuy(){
    const input = id('buy-qty');
    const maxValue = getBuyMaxValue();
    if(!input) return;
    if(!(maxValue > 0)){ input.value = ''; updateBuyPreview(); return; }
    const decimals = ((id('buy-mode')||{}).value || 'shares') === 'amount' ? 2 : 4;
    input.value = roundTo(maxValue, decimals).toFixed(decimals);
    updateBuyPreview();
  }
  function fillMaxSell(){
    const input = id('sell-qty');
    const mode = (id('sell-mode')||{}).value || 'all';
    const maxValue = getSellMaxValue();
    if(!input || mode === 'all') return;
    if(!(maxValue > 0)){ input.value = ''; updateSellPreview(); return; }
    const decimals = mode === 'amount' ? 2 : (mode === 'pct' ? 0 : 4);
    input.value = roundTo(maxValue, decimals).toFixed(decimals);
    updateSellPreview();
  }

  // ── Swal loader ───────────────────────────────────────────────────────────
  let swalN=0;
  function openSwal(cfg){ if(!window.Swal)return; swalN++; if(swalN>1)return; Swal.fire(Object.assign({allowOutsideClick:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()},cfg||{})); }
  function closeSwal(){ if(!window.Swal||!swalN)return; swalN--; if(!swalN&&Swal.close)Swal.close(); }

  // ── Chart ─────────────────────────────────────────────────────────────────
  const rangeCfg = {'1D':{period:'1d',interval:'15m'},'1W':{period:'5d',interval:'30m'},'1M':{period:'1mo',interval:'1d'},'YTD':{period:'6mo',interval:'1d'},'1Yr':{period:'1y',interval:'1d'},'3Yr':{period:'3y',interval:'1wk'},'5Yr':{period:'5y',interval:'1mo'}};
  const rangeBtns    = rangeWrap   ? Array.from(rangeWrap.querySelectorAll('button[data-range]'))     : [];
  const chartTypeBtns= typeToggle  ? Array.from(typeToggle.querySelectorAll('button[data-chart-type]')): [];

  function setRange(r){ rangeBtns.forEach(b=>b.classList.toggle('active',b.dataset.range===r)); }
  function setChartType(t){ chartTypeBtns.forEach(b=>b.classList.toggle('active',b.dataset.chartType===t)); }
  function updateChartTitle(){ if(chartTitle) chartTitle.textContent=(state.symbol||'—')+' Price Chart · '+state.range+' · '+(state.chartType==='line'?'Line':'Candles'); }

  function renderChart(ds){
    if(!chartEl)return;
    const payload=ds||state.lastChart, type=state.chartType==='line'?'line':'candlestick';
    const series=payload?(type==='line'?payload.line:payload.candles):null;
    if(!payload||!Array.isArray(series)||!series.length){ if(chartInst){chartInst.destroy();chartInst=null;} chartEl.innerHTML="<div class='text-center text-muted py-5'>No chart data.</div>"; updateChartTitle(); return; }
    const opts=type==='line'
      ?{series:[{name:state.symbol||'Price',data:payload.line}],chart:{type:'line',height:350,animations:{enabled:false},toolbar:{show:false}},stroke:{width:2,curve:'smooth'},xaxis:{type:'datetime'},yaxis:{tooltip:{enabled:true},decimalsInFloat:2}}
      :{series:[{data:payload.candles}],chart:{type:'candlestick',height:350,animations:{enabled:false},toolbar:{show:false}},xaxis:{type:'datetime'},yaxis:{tooltip:{enabled:true}}};
    if(!chartInst||activeChartType!==type){ if(chartInst){chartInst.destroy();chartInst=null;} chartEl.innerHTML=''; chartInst=new ApexCharts(chartEl,opts); chartInst.render(); }
    else chartInst.updateOptions(opts,false,true);
    activeChartType=type; updateChartTitle();
  }

  async function loadChart(sym,range,opts){
    if(!sym||!chartEl)return;
    const cfg=rangeCfg[range]||rangeCfg['1D'], key=sym+'|'+cfg.period+'|'+cfg.interval, now=Date.now();
    const force=opts&&opts.forceRefresh, silent=opts&&opts.silent;
    if(!force&&chartCache.has(key)){const c=chartCache.get(key);if(now-c.ts<CHART_CACHE){state.lastChart=c.data;renderChart(c.data);lastChartUpdate=now;return;}}
    if(chartAbort)chartAbort.abort(); chartAbort=new AbortController();
    try{
      if(!silent)openSwal({title:'Updating chart…'});
      const res=await fetch(API_BASE+'/chart-history?symbol='+encodeURIComponent(sym)+'&period='+cfg.period+'&interval='+cfg.interval,{signal:chartAbort.signal});
      if(!res.ok)throw new Error('Chart '+res.status);
      const json=await res.json(); const raw=json&&Array.isArray(json.data)?json.data:[];
      const candles=raw.map(i=>({x:new Date(i.x).getTime(),y:i.y}));
      const line=raw.map(i=>{const o=Array.isArray(i.y)?i.y:[];const c=o.length?o[o.length-1]:null;return{x:new Date(i.x).getTime(),y:Number(c)};}).filter(p=>isFinite(p.y));
      const ds={candles,line}; chartCache.set(key,{data:ds,ts:now}); state.lastChart=ds; renderChart(ds); lastChartUpdate=now;
    }catch(e){if(e.name==='AbortError')return; if(chartEl)chartEl.innerHTML="<div class='text-center text-danger py-5'>Unable to load chart.</div>";}
    finally{chartAbort=null;if(!silent)closeSwal();}
  }

  // ── Stock data ────────────────────────────────────────────────────────────
  function applyStockData(data,opts){
    const s=data&&data.selected_stock?data.selected_stock:null; if(!s)return Promise.resolve();
    const m=s.metrics||{},dr=m.day_range||{},r52=m['52w_range']||{},pc=s.price_change||{};
    const ca=toN(pc.amount), prev=state.symbol;
    state.symbol=s.ticker; state.livePrice=clamp(s.last_price); state.priceMap[s.ticker]=state.livePrice;

    if(elSym) elSym.textContent=s.ticker||'—';
    if(elCo)  elCo.textContent=(s.company_name||'Unknown')+' · '+(s.exchange||'—');
    if(elPx)  elPx.textContent=state.livePrice?fmt(state.livePrice):'—';
    if(elChg){
      elChg.classList.remove('text-success','text-danger');
      if(isFinite(ca)){
        elChg.classList.add(ca>=0?'text-success':'text-danger');
        const pct=isFinite(Number(pc.percentage))?Number(pc.percentage).toFixed(2)+'%':(pc.percentage||'—');
        elChg.textContent=(ca>=0?'+':'')+ca.toFixed(2)+' ('+pct+')';
      } else elChg.textContent='—';
    }
    if(elOpen) elOpen.textContent=m.open?fmt(m.open):'—';
    if(elPC)   elPC.textContent=m.previous_close?fmt(m.previous_close):'—';
    if(elDR)   elDR.textContent=isFinite(dr.low)&&isFinite(dr.high)?fmt(dr.low)+' – '+fmt(dr.high):'—';
    if(elR52)  elR52.textContent=isFinite(r52.low)&&isFinite(r52.high)?fmt(r52.low)+' – '+fmt(r52.high):'—';
    if(elVol)  elVol.textContent=big(m.volume);
    if(elAV)   elAV.textContent=big(m.avg_volume);
    if(elMC)   elMC.textContent=m.market_cap||'—';
    if(elPE)   elPE.textContent=m.pe_ratio||'—';

    setActiveList(s.ticker);
    updateBuyPreview(); updateSellPreview();
    checkOrderTriggers();
    if(!opts||!opts.skipChart)return loadChart(s.ticker,state.range,opts);
    return Promise.resolve();
  }

  async function fetchStock(sym,opts){
    const now=Date.now(),force=!!(opts&&opts.forceRefresh),silent=!!(opts&&opts.silent);
    if(!force&&stockCache.has(sym)){const c=stockCache.get(sym);if(now-c.ts<CACHE_MS)return applyStockData(c.data,opts);}
    if(stockAbort)stockAbort.abort(); stockAbort=new AbortController();
    let loaded=false; if(!silent){loaded=true;openSwal({title:'Loading '+sym+'…'});}
    try{
      const res=await fetch(API_BASE+'/buy-sell?symbol='+encodeURIComponent(sym),{signal:stockAbort.signal});
      if(!res.ok)throw new Error('Stock '+res.status);
      const data=await res.json(); if(!data||!data.selected_stock)throw new Error('Bad response');
      stockCache.set(sym,{data,ts:now}); return applyStockData(data,opts);
    }catch(e){
      if(e.name==='AbortError')return;
      if(elSym)elSym.textContent=sym; if(elCo)elCo.textContent='Unable to load data.';
    }finally{stockAbort=null;if(loaded)closeSwal();}
  }

  function loadStock(sym,opts){if(!sym)return;state.symbol=sym;return fetchStock(sym,opts);}
  const dbLoad=debounce(s=>loadStock(s),250);

  // ── Available stocks list ─────────────────────────────────────────────────
  function setRefresh(on){
    if(!refreshBtn)return;
    refreshBtn.disabled=on; refreshBtn.classList.toggle('opacity-50',on);
    if(refreshIcon)refreshIcon.classList.toggle('fa-spin',on);
    if(!on)refreshBtn.classList.remove('d-none');
  }

  function setActiveList(sym){
    [availList,holdList].forEach(list=>{
      if(!list)return;
      list.querySelectorAll('a[data-symbol]').forEach(a=>a.classList.remove('active'));
      if(sym){const t=list.querySelector('a[data-symbol="'+escSel(sym)+'"]');if(t)t.classList.add('active');}
    });
  }

  async function loadAvailableStocks(opts){
    if(!availList||!DB_SYMBOLS.length)return;
    const force=opts&&opts.forceRefresh;
    if(force||!availList.getAttribute('data-loaded'))availList.innerHTML="<div class='p-3 text-center text-muted'>Loading data...</div>";
    setRefresh(true); openSwal({title:'Loading available stocks…'});
    try{
      const chunks=[]; for(let i=0;i<DB_SYMBOLS.length;i+=10)chunks.push(DB_SYMBOLS.slice(i,i+10));
      const all=[];
      for(const chunk of chunks){
        const q=chunk.map(encodeURIComponent).join(',');
        const res=await fetch(API_BASE+'/buy-sell-info?symbols='+q);
        if(res.ok){const d=await res.json();if(Array.isArray(d))all.push(...d);}
      }
      availList.innerHTML='';
      const frag=document.createDocumentFragment();
      all.forEach(stock=>{
        if(!stock||stock.error)return;
        const cp=Number(stock.change_pct),isUp=isFinite(cp)?cp>=0:false;
        const cCls=isUp?'text-success':'text-danger', cTxt=isFinite(cp)?(isUp?'+':'')+cp.toFixed(2)+'%':'—';
        const px=Number(stock.current_price);
        let tags=Array.isArray(stock.tags)?stock.tags.filter(Boolean).join(', '):(stock.tags||'');
        const a=document.createElement('a');
        a.href='#'; a.className='list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        a.setAttribute('data-symbol',stock.symbol);
        a.innerHTML=`<div><h5 class="mb-0"><span class="mr-3">${esc(stock.symbol)}</span><small class="text-muted">${esc(stock.company_name||'')}</small></h5><small class="text-muted">${esc(stock.exchange||'')}${tags?' · '+esc(tags):''}</small></div><div class="text-right"><span class="d-block font-weight-bold">${isFinite(px)?fmt(px):'—'}</span><span class="${cCls}">${cTxt}</span></div>`;
        frag.appendChild(a);
      });
      if(!frag.childNodes.length) availList.innerHTML="<div class='p-3 text-center text-muted'>No stocks available.</div>";
      else{ availList.appendChild(frag); availList.setAttribute('data-loaded','1'); setActiveList(state.symbol); }
    }catch(e){availList.innerHTML="<div class='p-3 text-center text-danger'>Unable to load stock list.</div>";}
    finally{setRefresh(false);closeSwal();}
  }

  // ── Sell form: lock/unlock based on whether selected symbol is held ──────────
  function syncSellSymbol(sym){
    const hidden   = id('sell-symbol');
    const noHold   = id('sell-no-holding');
    const holdInfo = id('sell-holding-info');
    const fields   = id('sell-form-fields');
    const isHeld   = !!(sym && HOLDINGS[sym]);

    if(hidden) hidden.value = isHeld ? sym : '';

    // banners
    if(noHold){
      noHold.style.display = (!sym || !isHeld) ? '' : 'none';
      const lbl = id('sell-no-holding-sym');
      if(lbl) lbl.textContent = sym || 'This stock';
    }
    if(holdInfo){
      holdInfo.style.display = isHeld ? '' : 'none';
      if(isHeld){
        const h = HOLDINGS[sym];
        const el = id('sell-info-sym'); if(el) el.textContent = sym;
        const eq = id('sell-info-qty'); if(eq) eq.textContent = Number(h.quantity).toFixed(4);
        const ea = id('sell-info-avg'); if(ea) ea.textContent = fmt(h.avg_cost);
      }
    }

    // disable every input/select/button inside the fields wrapper when not held
    if(fields){
      fields.querySelectorAll('input:not([type=hidden]),select').forEach(el => {
        el.disabled = !isHeld;
      });
    }

    updateSellPreview();
  }

  // ── Buy preview ───────────────────────────────────────────────────────────
  function updateBuyPreview(){
    const sym   = (id('buy-symbol')||{}).value||'—';
    const trig  = Number((id('buy-trigger')||{}).value);
    const mode  = (id('buy-mode')||{}).value||'shares';
    const buyQtyInput = id('buy-qty');
    const maxValue = applyInputLimit(buyQtyInput, getBuyMaxValue(), mode === 'amount' ? 2 : 4);
    const qty   = Number((buyQtyInput||{}).value);
    const cond  = (id('buy-condition')||{}).value||'lte';
    const price = trig > 0 ? trig : state.livePrice;
    let estQty=0, estCost=0;
    if(mode==='shares'){estQty=qty>0?qty:0;estCost=estQty*price;}
    else{estCost=qty>0?qty:0;estQty=price>0?estCost/price:0;}
    const condLbl=cond==='lte'?'Price ≤ trigger':'Price ≥ trigger';
    setText('buy-prev-symbol',sym);
    setText('buy-prev-trigger',trig>0?fmt(trig):'—');
    setText('buy-prev-cond',condLbl);
    setText('buy-prev-qty',estQty>0?estQty.toFixed(4):'—');
    setText('buy-prev-cost',estCost>0?fmt(estCost):'—');
    setText('buy-prev-fees',fmt(TRADE_FEE));
    setText('buy-prev-cash',estCost>0?fmt(Math.max(availableCash-estCost-TRADE_FEE,0)):'—');
    const buyMaxBtn = id('buy-max-btn');
    if (buyMaxBtn) buyMaxBtn.disabled = !(maxValue > 0);
  }

  // ── Sell preview ──────────────────────────────────────────────────────────
  function updateSellPreview(){
    const hidden = id('sell-symbol'); const sym = hidden ? hidden.value : '';
    const trig  = Number((id('sell-trigger')||{}).value);
    const mode  = (id('sell-mode')||{}).value||'all';
    const sellQtyInput = id('sell-qty');
    const maxValue = applyInputLimit(sellQtyInput, getSellMaxValue(), mode === 'amount' ? 2 : (mode === 'pct' ? 0 : 4));
    const qty   = Number((sellQtyInput||{}).value);
    const cond  = (id('sell-condition')||{}).value||'lte';
    const hQty  = getHoldingQty(sym);
    const price=trig>0?trig:state.livePrice;
    let estQty=hQty;
    if(mode==='shares')estQty=qty>0?Math.min(qty,hQty):hQty;
    else if(mode==='pct')estQty=qty>0?hQty*Math.min(qty/100,1):hQty;
    else if(mode==='amount'&&qty>0&&price>0)estQty=Math.min(qty/price,hQty);
    const proceeds=estQty*price-TRADE_FEE;
    const condLbl=cond==='lte'?'Price ≤ trigger':'Price ≥ trigger';
    setText('sell-prev-symbol',sym||'—');
    setText('sell-prev-trigger',trig>0?fmt(trig):'—');
    setText('sell-prev-cond',condLbl);
    setText('sell-prev-qty',estQty>0?estQty.toFixed(4):'—');
    setText('sell-prev-proceeds',proceeds>0?fmt(proceeds):'—');
    setText('sell-prev-fees',fmt(TRADE_FEE));
    const sellMaxBtn = id('sell-max-btn');
    if (sellMaxBtn) sellMaxBtn.disabled = mode === 'all' || !(maxValue > 0);
  }

  function setText(elId,val){ const e=id(elId); if(e)e.textContent=val; }

  // ── Buy mode toggle ───────────────────────────────────────────────────────
  function updateBuyModeUI(){
    const mode=(id('buy-mode')||{}).value||'shares';
    const g=id('buy-qty-group'), lbl=id('buy-qty-label'), inp=id('buy-qty');
    if(!g)return;
    if(mode==='shares'){ if(lbl)lbl.textContent='Shares'; if(inp)inp.placeholder='e.g. 10'; }
    else{ if(lbl)lbl.textContent='Amount (USD)'; if(inp)inp.placeholder='e.g. 1000'; }
    if (inp) inp.step = mode === 'amount' ? '0.01' : '0.0001';
    updateBuyPreview();
  }

  // ── Sell mode toggle ──────────────────────────────────────────────────────
  function updateSellModeUI(){
    const mode=(id('sell-mode')||{}).value||'all';
    const g=id('sell-qty-group'), lbl=id('sell-qty-label'), inp=id('sell-qty');
    if(!g)return;
    if(mode==='all'){ g.style.display='none'; if(inp) inp.value=''; }
    else{
      g.style.display='';
      const lblMap={shares:'Shares to Sell',pct:'Percent (%)',amount:'Amount ($)'};
      const plMap ={shares:'e.g. 5',        pct:'e.g. 50',   amount:'e.g. 500'};
      if(lbl)lbl.textContent=lblMap[mode]||'Value';
      if(inp)inp.placeholder=plMap[mode]||'';
      if(inp) inp.step = mode === 'amount' ? '0.01' : (mode === 'pct' ? '1' : '0.0001');
    }
    updateSellPreview();
  }

  // ── Place limit order ─────────────────────────────────────────────────────
  async function placeOrder(){
    const activeSide = document.querySelector('#order-side-tabs .nav-link.active');
    const side = activeSide ? activeSide.dataset.side : 'BUY';

    let symbol, trigger, condition, mode, qty, amount, pct, note;

    if(side==='BUY'){
      symbol    = (id('buy-symbol')||{}).value||'';
      trigger   = Number((id('buy-trigger')||{}).value);
      condition = (id('buy-condition')||{}).value||'lte';
      mode      = (id('buy-mode')||{}).value||'shares';
      const buyInputVal = clamp((id('buy-qty')||{}).value);
      const buyMaxValue = getBuyMaxValue();
      qty       = mode==='shares' ? (buyMaxValue > 0 ? Math.min(buyInputVal, buyMaxValue) : buyInputVal) : 0;
      amount    = mode==='amount' ? (buyMaxValue > 0 ? Math.min(buyInputVal, buyMaxValue) : buyInputVal) : 0;
      note      = (id('buy-note')||{}).value||'';
    } else {
      const sellHidden = id('sell-symbol'); symbol=sellHidden?sellHidden.value:'';
      trigger   = Number((id('sell-trigger')||{}).value);
      condition = (id('sell-condition')||{}).value||'lte';
      mode      = (id('sell-mode')||{}).value||'all';
      const qv  = clamp((id('sell-qty')||{}).value);
      const sellMaxValue = getSellMaxValue();
      qty       = mode==='shares' ? (sellMaxValue > 0 ? Math.min(qv, sellMaxValue) : qv) : 0;
      amount    = mode==='amount' ? (sellMaxValue > 0 ? Math.min(qv, sellMaxValue) : qv) : 0;
      pct       = mode==='pct' ? (sellMaxValue > 0 ? Math.min(qv, sellMaxValue) : qv) : 0;
      note      = (id('sell-note')||{}).value||'';
    }

    if(!symbol){ showErr('Select a symbol first.'); return; }
    if(!(trigger>0)){ showErr('Enter a valid trigger price.'); return; }
    if(side==='BUY' && mode==='shares' && !(qty>0)){ showErr('Enter a valid share quantity.'); return; }
    if(side==='BUY' && mode==='amount' && !(amount>0)){ showErr('Enter a valid dollar amount.'); return; }
    if(side==='SELL' && mode==='shares' && !(qty>0)){ showErr('Enter a valid sell quantity.'); return; }
    if(side==='SELL' && mode==='amount' && !(amount>0)){ showErr('Enter a valid sell amount.'); return; }
    if(side==='SELL' && mode==='pct' && !(pct>0)){ showErr('Enter a valid sell percentage.'); return; }

    const fd=new FormData();
    fd.append('ajax_create_order','1');
    fd.append('side',side); fd.append('symbol',symbol);
    fd.append('trigger_price',trigger); fd.append('condition',condition);
    fd.append('order_mode',mode);
    if(qty>0)  fd.append('quantity',qty.toFixed(4));
    if(amount>0)fd.append('amount',amount.toFixed(2));
    if(pct>0)  fd.append('sell_pct',pct.toFixed(2));
    fd.append('fees',TRADE_FEE.toFixed(2));
    fd.append('note',note);

    try{
      openSwal({title:'Placing order…'});
      const json=await postSelfJson(fd);
      closeSwal();
      if(json.success){
        // add to active orders list and re-render
        const newOrder={id:json.id,side,symbol,trigger_price:trigger,condition,order_mode:mode,
          quantity:mode==='shares'?qty:0,amount:mode==='amount'?amount:0,sell_pct:pct||0,fees:TRADE_FEE,note,created_at:new Date().toISOString()};
        activeOrders.push(newOrder);
        renderActiveOrders();
        if(window.Swal)Swal.fire({icon:'success',title:'Order Placed',text:json.message,timer:2500,timerProgressBar:true});
        clearTicket(side);
      } else showErr(json.message);
    }catch(e){closeSwal();showErr(e && e.message ? e.message : 'Network error. Please try again.');}
  }

  function clearTicket(side){
    if(side==='BUY'||!side){
      ['buy-trigger','buy-qty','buy-note'].forEach(i=>{const e=id(i);if(e)e.value='';});
      updateBuyPreview();
    }
    if(side==='SELL'||!side){
      ['sell-trigger','sell-qty','sell-note'].forEach(i=>{const e=id(i);if(e)e.value='';});
      updateSellPreview();
    }
  }

  function showErr(msg){ if(window.Swal)Swal.fire({icon:'error',title:'Error',text:msg}); else alert(msg); }

  // ── Render active orders table ────────────────────────────────────────────
  function renderActiveOrders(){
    if(!activeBody)return;
    if(activeBadge)activeBadge.textContent=activeOrders.length+' active';
    if(!activeOrders.length){
      activeBody.innerHTML='<tr id="no-orders-row"><td colspan="8" class="text-center text-muted py-4">No active limit orders. Place one above.</td></tr>';
      return;
    }
    activeBody.innerHTML='';
    activeOrders.forEach(o=>{
      const price  = state.priceMap[o.symbol];
      const pxStr  = price!=null&&isFinite(price)?fmt(price):'—';
      const cond   = o.condition==='lte'?'≤':'≥';
      const condLbl= `Price ${cond} ${fmt(o.trigger_price)}`;
      const modeLbl= o.order_mode==='all'?'All shares':o.order_mode==='pct'?(o.sell_pct||0)+'% of pos.':o.order_mode==='amount'?fmt(o.amount):number(o.quantity)+' sh';
      const sideCls= o.side==='BUY'?'success':'danger';
      const triggered=price!=null&&isFinite(price)&&(o.condition==='lte'?price<=o.trigger_price:price>=o.trigger_price);
      const statusBadge=triggered
        ?'<span class="badge badge-warning text-dark"><i class="fas fa-exclamation-triangle mr-1"></i>Triggered</span>'
        :'<span class="badge badge-success"><i class="fas fa-clock mr-1"></i>Watching</span>';
      const tr=document.createElement('tr');
      if(triggered)tr.classList.add('order-triggered');
      tr.setAttribute('data-order-id',o.id);
      tr.innerHTML=`<td><span class="badge badge-${sideCls}">${o.side}</span></td>`
        +`<td><strong>${esc(o.symbol)}</strong></td>`
        +`<td class="small">${condLbl}</td>`
        +`<td>${fmt(o.trigger_price)}</td>`
        +`<td id="ord-price-${o.id}">${pxStr}</td>`
        +`<td class="small">${modeLbl}</td>`
        +`<td>${statusBadge}</td>`
        +`<td><button class="btn btn-sm btn-outline-danger cancel-order-btn" data-id="${o.id}"><i class="fas fa-times"></i></button></td>`;
      activeBody.appendChild(tr);
    });

    // cancel listeners
    activeBody.querySelectorAll('.cancel-order-btn').forEach(btn=>{
      btn.addEventListener('click',async function(){
        try{
          const oid=parseInt(this.dataset.id);
          let confirmed = true;
          if (window.Swal) {
            const result = await Swal.fire({
              icon:'warning',
              title:'Cancel limit order?',
              text:'This active order will be removed.',
              showCancelButton:true,
              confirmButtonText:'Yes, cancel it',
              cancelButtonText:'Keep order'
            });
            confirmed = !!result.isConfirmed;
          } else {
            confirmed = window.confirm('Cancel this active limit order?');
          }
          if (!confirmed) return;
          const fd=new FormData(); fd.append('ajax_cancel_order','1'); fd.append('order_id',oid);
          const json=await postSelfJson(fd);
          if(json.success){
            if (window.Swal) {
              await Swal.fire({icon:'success',title:'Cancelled',text:json.message,timer:1600,timerProgressBar:true});
            }
            location.reload();
          } else showErr(json.message);
        }catch(e){
          showErr(e && e.message ? e.message : 'Unable to cancel order.');
        }
      });
    });
  }

  function number(v){ return isFinite(v)?Number(v).toLocaleString('en-US',{maximumFractionDigits:4}):'—'; }

  // ── Check triggers ────────────────────────────────────────────────────────
  function checkOrderTriggers(){
    activeOrders.forEach(o=>{
      if(triggeredOrders.has(o.id))return;
      const price=state.priceMap[o.symbol];
      if(price==null||!isFinite(price))return;
      const hit=o.condition==='lte'?price<=o.trigger_price:price>=o.trigger_price;
      if(!hit)return;
      triggeredOrders.add(o.id);
      promptExecution(o,price);
    });
  }

  function promptExecution(order,livePrice){
    const condLbl=order.condition==='lte'?'dropped to':'rose to';
    const html=`<p>${order.symbol} ${condLbl} <strong>${fmt(livePrice)}</strong> (trigger: ${fmt(order.trigger_price)})</p>
      <table class="table table-sm text-left mt-2 mb-0"><tbody>
      <tr><td>Side</td><td class="text-right"><strong>${order.side}</strong></td></tr>
      <tr><td>Symbol</td><td class="text-right">${esc(order.symbol)}</td></tr>
      <tr><td>Live Price</td><td class="text-right">${fmt(livePrice)}</td></tr>
      </tbody></table>`;

    if(!window.Swal){ if(confirm('LIMIT ORDER TRIGGERED: '+order.side+' '+order.symbol+'?\nPrice: '+livePrice)) executeOrder(order,livePrice); else triggeredOrders.delete(order.id); return; }
    Swal.fire({
      icon:'warning', title:`Limit Order Triggered: ${order.symbol}`, html,
      showCancelButton:true, confirmButtonText:`Execute ${order.side}`, cancelButtonText:'Skip',
      confirmButtonColor: order.side==='BUY'?'#28a745':'#dc3545'
    }).then(r=>{ if(r.isConfirmed)executeOrder(order,livePrice); else triggeredOrders.delete(order.id); });
  }

  async function executeOrder(order,livePrice){
    const fd=new FormData();
    fd.append('ajax_execute_order','1');
    fd.append('order_id',order.id);
    fd.append('live_price',livePrice.toFixed(4));
    try{
      openSwal({title:'Executing order…'});
      const json=await postSelfJson(fd); closeSwal();
      if(json.success){
        activeOrders=activeOrders.filter(o=>o.id!==order.id);
        triggeredOrders.delete(order.id);
        availableCash=json.newBalance;
        if(cashDisp)cashDisp.textContent='$'+json.newBalance.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        renderActiveOrders();
        prependExecution(json);
        if(window.Swal)Swal.fire({icon:'success',title:'Order Executed!',text:json.message,timer:3000,timerProgressBar:true});
      } else { triggeredOrders.delete(order.id); showErr(json.message); }
    }catch(e){closeSwal();triggeredOrders.delete(order.id);showErr(e && e.message ? e.message : 'Network error.');}
  }

  function prependExecution(json){
    if(!recentBody)return;
    const empty=recentBody.querySelector('td[colspan]'); if(empty)empty.closest('tr').remove();
    const now=new Date();
    const t=now.toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'})+' '+now.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false});
    const cls=json.side==='BUY'?'success':'danger';
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><span class="badge badge-${cls}">${json.side}</span></td><td>${esc(json.symbol)}</td><td>${(+json.qty).toFixed(4)}</td><td>${fmt(json.price)}</td><td>${fmt(TRADE_FEE)}</td><td>${t}</td>`;
    recentBody.insertBefore(tr,recentBody.firstChild);
  }

  // ── Poll all order symbols ────────────────────────────────────────────────
  async function pollPrices(){
    const symbols=[...new Set(activeOrders.map(o=>o.symbol))];
    if(!symbols.length)return;
    try{
      const chunks=[]; for(let i=0;i<symbols.length;i+=10)chunks.push(symbols.slice(i,i+10));
      for(const chunk of chunks){
        const q=chunk.map(encodeURIComponent).join(',');
        const res=await fetch(API_BASE+'/buy-sell-info?symbols='+q);
        if(!res.ok)continue;
        const data=await res.json();
        if(!Array.isArray(data))continue;
        data.forEach(item=>{
          if(!item||item.error||!item.symbol)return;
          const sym=item.symbol.toUpperCase(), px=Number(item.current_price);
          if(isFinite(px)){
            state.priceMap[sym]=px;
            // update any matching live price cells in active orders table
            activeOrders.filter(o=>o.symbol===sym).forEach(o=>{
              const cell=id('ord-price-'+o.id);
              if(cell)cell.textContent=fmt(px);
            });
            // update holding badge
            const hp=id('hlp-'+sym); if(hp)hp.textContent=fmt(px);
          }
        });
        checkOrderTriggers();
        renderActiveOrders();
      }
    }catch(e){/* silent */}
  }

  // ── Auto-refresh ──────────────────────────────────────────────────────────
  function startPoll(){
    if(pollTimer)clearInterval(pollTimer);
    pollTimer=setInterval(()=>{
      if(state.symbol)loadStock(state.symbol,{forceRefresh:true,silent:true,skipChart:true});
      pollPrices();
      if(Date.now()-lastChartUpdate>=CHART_REFRESH){ lastChartUpdate=Date.now(); if(state.symbol)loadChart(state.symbol,state.range,{silent:true}); }
    },POLL_MS);
  }

  window.addEventListener('beforeunload',()=>{ if(pollTimer)clearInterval(pollTimer); });

  // ── Listeners ─────────────────────────────────────────────────────────────
  if(availList) availList.addEventListener('click',e=>{
    const a=e.target.closest('a[data-symbol]'); if(!a)return; e.preventDefault();
    const sym=a.dataset.symbol; dbLoad(sym);
    const bs=id('buy-symbol'); if(bs)bs.value=sym; updateBuyPreview();
    syncSellSymbol(sym); // will lock sell form if sym not held
  });
  if(holdList) holdList.addEventListener('click',e=>{
    const a=e.target.closest('a[data-symbol]'); if(!a)return; e.preventDefault();
    const sym=a.dataset.symbol; dbLoad(sym);
    const bs=id('buy-symbol'); if(bs)bs.value=sym; updateBuyPreview();
    syncSellSymbol(sym); // always held — unlocks sell form
  });
  if(refreshBtn)refreshBtn.addEventListener('click',()=>{ stockCache.clear();chartCache.clear();if(availList)availList.removeAttribute('data-loaded'); loadAvailableStocks({forceRefresh:true}); if(state.symbol){loadStock(state.symbol,{forceRefresh:true});loadChart(state.symbol,state.range,{forceRefresh:true});} });

  if(rangeWrap) rangeWrap.addEventListener('click',e=>{ const b=e.target.closest('button[data-range]');if(!b)return; state.range=b.dataset.range;setRange(state.range);updateChartTitle();if(state.symbol)loadChart(state.symbol,state.range); });
  if(typeToggle)typeToggle.addEventListener('click',e=>{ const b=e.target.closest('button[data-chart-type]');if(!b||b.dataset.chartType===state.chartType)return; state.chartType=b.dataset.chartType==='line'?'line':'candlestick';setChartType(state.chartType);try{localStorage.setItem('trade:chartType',state.chartType);}catch(x){}; if(state.lastChart)renderChart(state.lastChart);else if(state.symbol)loadChart(state.symbol,state.range);else updateChartTitle(); });

  const bm=id('buy-mode'); if(bm){bm.addEventListener('change',updateBuyModeUI);}
  const sm=id('sell-mode');if(sm){sm.addEventListener('change',updateSellModeUI);}
  const buyMaxBtn = id('buy-max-btn'); if (buyMaxBtn) buyMaxBtn.addEventListener('click', fillMaxBuy);
  const sellMaxBtn = id('sell-max-btn'); if (sellMaxBtn) sellMaxBtn.addEventListener('click', fillMaxSell);
  ['buy-trigger','buy-qty','buy-condition'].forEach(i=>{const e=id(i);if(e)e.addEventListener('input',updateBuyPreview);});
  ['sell-trigger','sell-qty','sell-condition'].forEach(i=>{const e=id(i);if(e)e.addEventListener('input',updateSellPreview);});

  const placeBtn=id('place-order-btn'); if(placeBtn)placeBtn.addEventListener('click',placeOrder);
  const clearBtn=id('clear-ticket-btn');if(clearBtn)clearBtn.addEventListener('click',()=>clearTicket());

  // ── Init ──────────────────────────────────────────────────────────────────
  setRange(state.range); setChartType(state.chartType); updateChartTitle();
  updateBuyModeUI(); updateSellModeUI(); renderActiveOrders();
  syncSellSymbol(null); // sell form starts locked until a holding is clicked

  const DEFAULT = (()=>{ try{return localStorage.getItem('trade:lastSymbol')||<?= json_encode($defaultSymbol) ?>;}catch(e){return<?= json_encode($defaultSymbol) ?>;} })();

  openSwal({title:'Loading trade data…'});
  Promise.all([loadAvailableStocks(), loadStock(DEFAULT), pollPrices()])
    .catch(()=>{}).finally(()=>{ closeSwal(); const bs=id('buy-symbol');if(bs)bs.value=DEFAULT; updateBuyPreview(); startPoll(); });
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
