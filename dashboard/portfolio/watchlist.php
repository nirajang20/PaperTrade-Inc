<?php
require "../lib/Core.php";

if (!$conn) {
  require "../lib/PAGE-top.php";
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-danger'>Unable to connect to the watchlist database.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

require "../lib/PAGE-top.php";

if (empty($active_user) || !isset($active_user["user_id"])) {
  echo "<section class='content'><div class='container-fluid'><div class='alert alert-warning'>Please log in again to manage your watchlist.</div></div></section>";
  require "../lib/PAGE-bottom.php";
  exit;
}

$userId = (int) $active_user["user_id"];

$alert = [
  "type" => null,
  "title" => null,
  "message" => null
];

function sanitize_symbol($symbol) {
  $symbol = strtoupper(trim($symbol));
  return preg_replace("/[^A-Z0-9\.\-\^]/", "", $symbol);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  if ($action === "add") {
    $symbolInput = sanitize_symbol($_POST["symbol"] ?? "");
    $noteInput = trim($_POST["note"] ?? "");

    if ($symbolInput === "") {
      $alert = [
        "type" => "error",
        "title" => "Add Failed",
        "message" => "Please enter a valid ticker symbol before adding."
      ];
    } else {
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

      if ($symbolId) {
        $stmtWatch = $conn->prepare("INSERT INTO watchlist (user_id, symbol_id, note) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE note = VALUES(note)");
        if ($stmtWatch) {
          $noteParam = $noteInput !== "" ? $noteInput : null;
          $stmtWatch->bind_param("iis", $userId, $symbolId, $noteParam);
          if ($stmtWatch->execute()) {
            $alert = [
              "type" => "success",
              "title" => "Added to Watchlist",
              "message" => "{$symbolInput} is now being tracked."
            ];
          } else {
            $alert = [
              "type" => "error",
              "title" => "Add Failed",
              "message" => "Unable to add the selected symbol. Please try again."
            ];
          }
          $stmtWatch->close();
        }
      } else {
        $alert = [
          "type" => "error",
          "title" => "Add Failed",
          "message" => "Unable to register the selected ticker."
        ];
      }
    }
  } elseif ($action === "delete") {
    $watchlistId = isset($_POST["watchlist_id"]) ? (int) $_POST["watchlist_id"] : 0;
    if ($watchlistId > 0) {
      $stmtDelete = $conn->prepare("DELETE FROM watchlist WHERE watchlist_id = ? AND user_id = ?");
      if ($stmtDelete) {
        $stmtDelete->bind_param("ii", $watchlistId, $userId);
        if ($stmtDelete->execute() && $stmtDelete->affected_rows > 0) {
          $alert = [
            "type" => "success",
            "title" => "Removed",
            "message" => "Symbol removed from your watchlist."
          ];
        } else {
          $alert = [
            "type" => "error",
            "title" => "Delete Failed",
            "message" => "Unable to remove the selected item."
          ];
        }
        $stmtDelete->close();
      }
    }
  }
}

$watchlist = [];
$watchlistStmt = $conn->prepare("
  SELECT w.watchlist_id, at.symbol, w.note, w.created_at
  FROM watchlist w
  INNER JOIN available_trade at ON at.id = w.symbol_id
  WHERE w.user_id = ?
  ORDER BY w.created_at DESC
");

if ($watchlistStmt) {
  $watchlistStmt->bind_param("i", $userId);
  if ($watchlistStmt->execute()) {
    $result = $watchlistStmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $row["symbol"] = strtoupper($row["symbol"]);
      $watchlist[] = $row;
    }
  }
  $watchlistStmt->close();
}

$snapshots = [];
if (!empty($watchlist)) {
  $symbols = array_map(function ($item) {
    return $item["symbol"];
  }, $watchlist);

  $chunks = array_chunk($symbols, 10);
  foreach ($chunks as $chunk) {
    $query = implode(",", array_map("urlencode", $chunk));
    $url = "http://74.208.158.229/stock-api/buy-sell-info?symbols=" . $query;
    $response = @file_get_contents($url);
    if ($response !== false) {
      $decoded = json_decode($response, true);
      if (is_array($decoded)) {
        foreach ($decoded as $item) {
          if (!empty($item["symbol"])) {
            $snapshots[strtoupper($item["symbol"])] = $item;
          }
        }
      }
    }
  }
}

foreach ($watchlist as &$entry) {
  $symbol = $entry["symbol"];
  $snapshot = $snapshots[$symbol] ?? null;
  $entry["company"] = $snapshot["company_name"] ?? null;
  $entry["exchange"] = $snapshot["exchange"] ?? null;
  $entry["price"] = isset($snapshot["current_price"]) ? (float) $snapshot["current_price"] : null;
  $entry["change_pct"] = isset($snapshot["change_pct"]) ? (float) $snapshot["change_pct"] : null;
  if (!empty($snapshot["tags"])) {
    $entry["tags"] = is_array($snapshot["tags"]) ? $snapshot["tags"] : explode(",", $snapshot["tags"]);
  } else {
    $entry["tags"] = [];
  }
}
unset($entry);

$totalTracked = count($watchlist);
$positiveCount = 0;
$negativeCount = 0;
foreach ($watchlist as $entry) {
  $pct = $entry["change_pct"];
  if ($pct !== null) {
    if ($pct >= 0) $positiveCount++;
    if ($pct < 0) $negativeCount++;
  }
}

?>

<section class="content">
  <div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
      <div>
        <h1 class="h3 mb-1"><i class="far fa-eye text-info mr-2"></i>Personal Watchlist</h1>
        <p class="text-muted mb-0">Track symbols you care about, add quick notes, and monitor pricing at a glance.</p>
      </div>
      <div class="mt-3 mt-md-0">
        <span class="badge badge-info badge-pill px-3 py-2"><?=number_format($totalTracked); ?> symbols tracked</span>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-5">
        <div class="card card-outline card-info">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Add to Watchlist</h3>
          </div>
          <div class="card-body">
            <form method="post" id="watchlist-add-form">
              <input type="hidden" name="action" value="add">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="watchlist-symbol">Ticker Symbol</label>
                  <input type="text"
                         class="form-control form-control-lg"
                         id="watchlist-symbol"
                         name="symbol"
                         placeholder="e.g. NVDA, BTC-USD"
                         required>
                  <small class="form-text text-muted">Use Yahoo-style tickers for indices (e.g. ^GSPC).</small>
                </div>
                <div class="form-group col-md-6">
                  <label for="watchlist-note">Quick Note <span class="text-muted small">(optional)</span></label>
                  <input type="text"
                         class="form-control"
                         id="watchlist-note"
                         name="note"
                         maxlength="150"
                         placeholder="Why track this?">
                </div>
              </div>
              <button type="submit" class="btn btn-info btn-lg">
                <i class="fas fa-plus mr-2"></i>Add Symbol
              </button>
            </form>
          </div>
        </div>

        <div class="card card-outline card-secondary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Insights</h3>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>Positive movers</span>
                <span class="badge badge-success badge-pill"><?=number_format($positiveCount); ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>Negative movers</span>
                <span class="badge badge-danger badge-pill"><?=number_format($negativeCount); ?></span>
              </li>
              <li class="list-group-item">
                <small class="text-muted">Symbols in your watchlist are independent of your holdings. Add anything you want to follow before entering.</small>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card card-outline card-dark">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Tracked Symbols</h3>
              <small class="text-muted">Live pricing powered by the stock API.</small>
            </div>
          </div>
          <div class="card-body p-0">
            <?php if (empty($watchlist)) { ?>
              <div class="text-center text-muted py-5">You haven't added any symbols yet. Start by adding your favourites on the left.</div>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="watchlist-table">
                  <thead class="thead-light">
                    <tr>
                      <th>Symbol</th>
                      <th>Company</th>
                      <th class="text-right">Last</th>
                      <th class="text-right">Change</th>
                      <th>Tags</th>
                      <th>Note</th>
                      <th>Added</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($watchlist as $item) {
                      $changePct = $item["change_pct"];
                      $changeClass = $changePct !== null ? ($changePct >= 0 ? "text-success" : "text-danger") : "text-muted";
                      $changeText = $changePct !== null ? (($changePct >= 0 ? "+" : "") . number_format($changePct, 2) . "%") : "—";
                      ?>
                      <tr data-watchlist-id="<?=$item["watchlist_id"]; ?>">
                        <td>
                          <div class="d-flex align-items-center">
                            <span class="badge badge-dark badge-pill mr-2"><?=htmlspecialchars($item["symbol"]); ?></span>
                            <span class="badge <?=$changePct >= 0 ? "badge-success" : "badge-danger"; ?>"><?=$changeText; ?></span>
                          </div>
                        </td>
                        <td>
                          <strong><?=htmlspecialchars($item["company"] ?? "—"); ?></strong>
                          <div class="small text-muted"><?=htmlspecialchars($item["exchange"] ?? "—"); ?></div>
                        </td>
                        <td class="text-right"><?=$item["price"] !== null ? "$" . number_format($item["price"], 2, ".", ",") : "—"; ?></td>
                        <td class="text-right <?=$changeClass; ?>"><?=$changeText; ?></td>
                        <td>
                          <?php if (!empty($item["tags"])) {
                            foreach ($item["tags"] as $tag) {
                              if (!$tag) continue;
                              echo "<span class='badge badge-light mr-1 mb-1'>" . htmlspecialchars(trim($tag)) . "</span>";
                            }
                          } ?>
                        </td>
                        <td>
                          <?php if (!empty($item["note"])) { ?>
                            <span class="badge badge-info badge-soft">Note</span>
                            <span class="d-block small text-muted"><?=nl2br(htmlspecialchars($item["note"])); ?></span>
                          <?php } ?>
                        </td>
                        <td><?=!empty($item["created_at"]) ? date("M d, Y", strtotime($item["created_at"])) : "—"; ?></td>
                        <td class="text-right">
                          <form method="post" class="watchlist-remove-form d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="watchlist_id" value="<?=$item["watchlist_id"]; ?>">
                            <button type="button" class="btn btn-outline-danger btn-sm watchlist-remove-btn">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
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
    </div>
  </div>
</section>

<style>
  .badge-soft {
    background: rgba(23, 162, 184, 0.12);
    color: #117a8b;
  }
  #watchlist-table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.06);
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const removeButtons = document.querySelectorAll('.watchlist-remove-btn');

  removeButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const form = button.closest('form');
      if (!form) return;

      Swal.fire({
        icon: 'warning',
        title: 'Remove Symbol?',
        text: 'This symbol will be removed from your watchlist.',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
      }).then(function (result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  const flash = <?=json_encode($alert, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  if (flash && flash.type) {
    if (flash.type === 'success') {
      toastr.success(flash.message || 'Action completed successfully.', flash.title || 'Success');
    } else if (flash.type === 'error') {
      toastr.error(flash.message || 'Unable to complete the action.', flash.title || 'Error');
    } else {
      toastr.info(flash.message || '', flash.title || 'Notice');
    }
  }
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
