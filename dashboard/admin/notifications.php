<?php
require "../lib/Core.php";

$_ISADMIN = isset($_SESSION['user']);
require_once PATH_LIB . "fetch.php";

$activeRole = strtolower(trim($active_user['role'] ?? ''));
if ($activeRole !== 'admin') {
  $titleJson = json_encode('No Permission');
  $messageJson = json_encode('You do not have permission to access this page.');
  $redirectUrlJson = json_encode(URL_HOST);
  echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Redirecting...</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.all.min.js"></script>
</head>
<body>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      Swal.fire({
        icon: 'error',
        title: {$titleJson},
        text: {$messageJson}
      }).then(function () {
        window.location.href = {$redirectUrlJson};
      });
    });
  </script>
</body>
</html>
HTML;
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
  $_SESSION['admin_flash'] = [];
}

function notificationsAddFlash(string $type, string $title, string $message): void
{
  $_SESSION['admin_flash'][] = [
    "type" => $type,
    "title" => $title,
    "message" => $message
  ];
}

function notificationsRedirectSelf(): void
{
  header("Location: " . URL_HOST . "admin/notifications.php");
  exit;
}

function notificationsEscape(?string $value): string
{
  return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    $userId = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
    $title = trim($_POST["title"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if (!$userId || $userId < 1) {
      throw new RuntimeException("Please choose a recipient.");
    }
    if ($title === "" || strlen($title) > 255) {
      throw new RuntimeException("Title is required and must be less than 255 characters.");
    }
    if ($message === "") {
      throw new RuntimeException("Message content cannot be empty.");
    }

    $stmtUser = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtUser, "i", $userId);
    mysqli_stmt_execute($stmtUser);
    mysqli_stmt_store_result($stmtUser);
    $exists = mysqli_stmt_num_rows($stmtUser) === 1;
    mysqli_stmt_close($stmtUser);

    if (!$exists) {
      throw new RuntimeException("Unable to find the selected user.");
    }

    $stmtInsert = mysqli_prepare(
      $conn,
      "INSERT INTO notifications (user_id, title, message, status, created_at) VALUES (?, ?, ?, 'Unread', NOW())"
    );
    mysqli_stmt_bind_param($stmtInsert, "iss", $userId, $title, $message);
    mysqli_stmt_execute($stmtInsert);
    mysqli_stmt_close($stmtInsert);

    notificationsAddFlash("success", "Notification Sent", "The message was delivered successfully.");
  } catch (Throwable $th) {
    notificationsAddFlash("error", "Send Failed", $th->getMessage());
  }

  notificationsRedirectSelf();
}

$alerts = $_SESSION["admin_flash"];
$_SESSION["admin_flash"] = [];

$recipients = [];
$usersResult = mysqli_query($conn, "SELECT user_id, user_name, user_email FROM users ORDER BY user_name ASC");
while ($row = mysqli_fetch_assoc($usersResult)) {
  $recipients[] = $row;
}
mysqli_free_result($usersResult);

$recentNotifications = [];
$recentResult = mysqli_query(
  $conn,
  "SELECT n.notification_id, n.title, n.message, n.status, n.created_at, u.user_name, u.user_email
   FROM notifications n
   INNER JOIN users u ON u.user_id = n.user_id
   ORDER BY n.created_at DESC
   LIMIT 15"
);
while ($row = mysqli_fetch_assoc($recentResult)) {
  $recentNotifications[] = $row;
}
mysqli_free_result($recentResult);

require PATH_LIB . "PAGE-top.php";
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="mb-0">Send Notification</h1>
        <small class="text-muted">Deliver an in-app message to any user. Sender is always Admin.</small>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <style>
      .notification-card textarea {
        min-height: 160px;
      }
      .badge-unread {
        background-color: #ffc107;
      }
      .badge-read {
        background-color: #28a745;
      }
      .recent-message {
        max-width: 420px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .notification-modal-message {
        white-space: pre-wrap;
      }
    </style>

    <?php if (!empty($alerts)) : ?>
      <?php foreach ($alerts as $alert) :
        $type = strtolower($alert["type"]);
        $classMap = [
          "success" => "alert-success",
          "error" => "alert-danger",
          "warning" => "alert-warning",
          "info" => "alert-info"
        ];
        $alertClass = $classMap[$type] ?? "alert-secondary";
      ?>
        <div class="alert <?= $alertClass ?> alert-dismissible fade show">
          <strong><?= notificationsEscape($alert["title"] ?? "") ?></strong>
          <div><?= notificationsEscape($alert["message"] ?? "") ?></div>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="row">
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm notification-card">
          <div class="card-header border-0 bg-white">
            <h3 class="card-title mb-0">Compose Notification</h3>
          </div>
          <div class="card-body">
            <form method="post">
              <div class="form-group">
                <label for="user_id">To</label>
                <select name="user_id" id="user_id" class="form-control" required>
                  <option value="">Select a user…</option>
                  <?php foreach ($recipients as $user) :
                    $label = sprintf(
                      "%s (%s)",
                      notificationsEscape($user["user_name"] ?? ""),
                      notificationsEscape($user["user_email"] ?? "")
                    );
                  ?>
                    <option value="<?= (int) $user["user_id"] ?>"><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>From</label>
                <input type="text" class="form-control" value="Admin" disabled>
                <small class="form-text text-muted">All notifications originate from the Admin account.</small>
              </div>

              <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" class="form-control" maxlength="255" required>
              </div>

              <div class="form-group mb-4">
                <label for="message">Message</label>
                <textarea name="message" id="message" class="form-control" required></textarea>
              </div>

              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane mr-1"></i>
                Send Notification
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header border-0 bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Recent Notifications</h3>
            <small class="text-muted">Last <?= count($recentNotifications) ?> sent</small>
          </div>
          <div class="card-body table-responsive p-0">
            <?php if (empty($recentNotifications)) : ?>
              <div class="p-4 text-center text-muted">No notifications have been sent yet.</div>
            <?php else : ?>
              <table class="table table-hover mb-0 notification-table">
                <thead>
                  <tr>
                    <th>Recipient</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Sent</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentNotifications as $row) :
                    $status = strtolower($row["status"] ?? "Unread");
                    $badgeClass = $status === "read" ? "badge-read" : "badge-unread";
                    $createdAt = $row["created_at"] ?? null;
                    $displayDate = $createdAt ? date("M j, Y g:i A", strtotime($createdAt)) : "--";
                    $rowPayload = htmlspecialchars(json_encode([
                      "recipient" => $row["user_name"] ?? "",
                      "email" => $row["user_email"] ?? "",
                      "title" => $row["title"] ?? "",
                      "message" => $row["message"] ?? "",
                      "status" => $row["status"] ?? "Unread",
                      "sentAt" => $displayDate
                    ]), ENT_QUOTES, "UTF-8");
                  ?>
                    <tr class="notification-row" data-notification="<?= $rowPayload ?>">
                      <td>
                        <div class="font-weight-bold"><?= notificationsEscape($row["user_name"] ?? "") ?></div>
                        <small class="text-muted"><?= notificationsEscape($row["user_email"] ?? "") ?></small>
                      </td>
                      <td>
                        <div class="recent-message" title="<?= notificationsEscape($row["message"] ?? "") ?>">
                          <?= notificationsEscape($row["title"] ?? "") ?>
                        </div>
                      </td>
                      <td>
                        <span class="badge <?= $badgeClass ?>">
                          <?= notificationsEscape($row["status"] ?? "Unread") ?>
                        </span>
                      </td>
                      <td>
                        <small class="text-muted">
                          <?= notificationsEscape($displayDate) ?>
                        </small>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">To</dt>
              <dd class="col-sm-9">
                <div id="modalRecipient" class="font-weight-bold"></div>
                <small id="modalEmail" class="text-muted"></small>
              </dd>

              <dt class="col-sm-3">From</dt>
              <dd class="col-sm-9">Admin</dd>

              <dt class="col-sm-3">Title</dt>
              <dd class="col-sm-9" id="modalTitle"></dd>

              <dt class="col-sm-3">Status</dt>
              <dd class="col-sm-9" id="modalStatus"></dd>

              <dt class="col-sm-3">Sent</dt>
              <dd class="col-sm-9" id="modalSentAt"></dd>

              <dt class="col-sm-3">Message</dt>
              <dd class="col-sm-9">
                <pre id="modalMessage" class="mb-0 notification-modal-message"></pre>
              </dd>
            </dl>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var rows = document.querySelectorAll('.notification-row');
    var modalElement = $('#notificationModal');
    if (!rows.length || !modalElement.length) {
      return;
    }

    var recipientEl = document.getElementById('modalRecipient');
    var emailEl = document.getElementById('modalEmail');
    var titleEl = document.getElementById('modalTitle');
    var statusEl = document.getElementById('modalStatus');
    var sentEl = document.getElementById('modalSentAt');
    var messageEl = document.getElementById('modalMessage');

    rows.forEach(function (row) {
      row.addEventListener('click', function () {
        var payload = row.getAttribute('data-notification');
        if (!payload) {
          return;
        }
        try {
          var data = JSON.parse(payload);
          recipientEl.textContent = data.recipient || '--';
          emailEl.textContent = data.email || '';
          titleEl.textContent = data.title || '--';
          statusEl.textContent = data.status || 'Unread';
          sentEl.textContent = data.sentAt || '--';
          messageEl.textContent = data.message || '';
          modalElement.modal('show');
        } catch (err) {
          console.error('Failed to parse notification payload', err);
        }
      });
    });
  });
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
