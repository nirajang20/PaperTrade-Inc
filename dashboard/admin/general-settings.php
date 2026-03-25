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

function generalAddFlash(string $type, string $title, string $message): void
{
  $_SESSION['admin_flash'][] = [
    "type" => $type,
    "title" => $title,
    "message" => $message
  ];
}

function generalRedirectSelf(): void
{
  header("Location: " . URL_HOST . "admin/general-settings.php");
  exit;
}

function generalEscape(?string $value): string
{
  return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function generalGenerateDatabaseBackup(mysqli $conn): string
{
  if (function_exists('set_time_limit')) {
    @set_time_limit(0);
  }

  mysqli_set_charset($conn, DB_CHARSET);

  $lines = [];
  $lines[] = "-- PaperTrade backup";
  $lines[] = "-- Host: " . DB_HOST . ":" . DB_PORT;
  $lines[] = "-- Database: " . DB_NAME;
  $lines[] = "-- Generated: " . gmdate("Y-m-d H:i:s") . " UTC";
  $lines[] = "SET FOREIGN_KEY_CHECKS=0;";

  $tables = [];
  $tableRes = mysqli_query($conn, "SHOW FULL TABLES");
  while ($row = mysqli_fetch_row($tableRes)) {
    $tables[] = [
      "name" => $row[0],
      "type" => strtoupper($row[1] ?? "BASE TABLE")
    ];
  }
  mysqli_free_result($tableRes);

  foreach ($tables as $table) {
    $name = $table["name"];
    $type = $table["type"];

    $lines[] = "\n-- ----------------------------";
    $lines[] = "-- Structure for {$type} `$name`";
    $lines[] = "-- ----------------------------";

    if ($type === "VIEW") {
      $lines[] = "DROP VIEW IF EXISTS `$name`;";
      $createRes = mysqli_query($conn, "SHOW CREATE VIEW `$name`");
      $createRow = mysqli_fetch_row($createRes);
      mysqli_free_result($createRes);
      if (!$createRow || empty($createRow[1])) {
        throw new RuntimeException("Unable to read definition for view `$name`.");
      }
      $lines[] = $createRow[1] . ";";
      continue;
    }

    $lines[] = "DROP TABLE IF EXISTS `$name`;";
    $createRes = mysqli_query($conn, "SHOW CREATE TABLE `$name`");
    $createRow = mysqli_fetch_row($createRes);
    mysqli_free_result($createRes);
    if (!$createRow || empty($createRow[1])) {
      throw new RuntimeException("Unable to read table structure for `$name`.");
    }
    $lines[] = $createRow[1] . ";";

    $dataRes = mysqli_query($conn, "SELECT * FROM `$name`");
    $rowCount = 0;
    while ($row = mysqli_fetch_row($dataRes)) {
      $rowCount++;
      $values = [];
      foreach ($row as $value) {
        if ($value === null) {
          $values[] = "NULL";
        } else {
          $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
        }
      }
      $lines[] = "INSERT INTO `$name` VALUES (" . implode(", ", $values) . ");";
    }
    mysqli_free_result($dataRes);

    if ($rowCount === 0) {
      $lines[] = "-- (no rows)";
    }
  }

  $lines[] = "\nSET FOREIGN_KEY_CHECKS=1;";
  return implode("\n", $lines) . "\n";
}

function generalRestoreDatabaseFromSql(mysqli $conn, string $sql): void
{
  if (function_exists('set_time_limit')) {
    @set_time_limit(0);
  }

  $trimmed = trim($sql);
  if ($trimmed === "") {
    throw new RuntimeException("The uploaded backup seems to be empty.");
  }

  mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
  try {
    if (!mysqli_multi_query($conn, $trimmed)) {
      throw new RuntimeException("Restore failed: " . mysqli_error($conn));
    }

    while (true) {
      if ($result = mysqli_store_result($conn)) {
        mysqli_free_result($result);
      }
      if (!mysqli_more_results($conn)) {
        break;
      }
      if (!mysqli_next_result($conn)) {
        throw new RuntimeException("Restore halted: " . mysqli_error($conn));
      }
    }
  } finally {
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  try {
    switch ($action) {
      case "download_backup":
        $dump = generalGenerateDatabaseBackup($conn);
        $filename = "trade-backup-" . date("Ymd-His") . ".sql";
        while (ob_get_level() > 0) {
          ob_end_clean();
        }
        header("Content-Type: application/sql");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Content-Length: " . strlen($dump));
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Pragma: no-cache");
        echo $dump;
        exit;

      case "restore_backup":
        if (!isset($_FILES["db_file"])) {
          throw new RuntimeException("Please select a backup file to upload.");
        }

        $fileInfo = $_FILES["db_file"];
        if ($fileInfo["error"] !== UPLOAD_ERR_OK) {
          throw new RuntimeException("Upload failed with error code " . $fileInfo["error"] . ".");
        }

        $extension = strtolower(pathinfo($fileInfo["name"], PATHINFO_EXTENSION));
        if (!in_array($extension, ["sql", "txt"], true)) {
          throw new RuntimeException("Only .sql or .txt files are allowed.");
        }

        if ($fileInfo["size"] <= 0) {
          throw new RuntimeException("The uploaded file is empty.");
        }

        $sql = file_get_contents($fileInfo["tmp_name"]);
        if ($sql === false) {
          throw new RuntimeException("Unable to read the uploaded file.");
        }

        generalRestoreDatabaseFromSql($conn, $sql);
        generalAddFlash("success", "Database Restored", "The uploaded backup has been applied successfully.");
        break;

      case "update_api_keys":
        $siteKey = trim($_POST["site_key"] ?? "");
        $secretKey = trim($_POST["secret_key"] ?? "");

        if (empty($siteKey) || empty($secretKey)) {
          throw new RuntimeException("Both Site Key and Secret Key are required.");
        }

        $stmt = $conn->prepare(
          "INSERT INTO `settings` (`id`, `site_key`, `secret_key`) VALUES (1, ?, ?)
           ON DUPLICATE KEY UPDATE `site_key` = VALUES(`site_key`), `secret_key` = VALUES(`secret_key`)"
        );
        $stmt->bind_param("ss", $siteKey, $secretKey);
        $stmt->execute();
        $stmt->close();

        generalAddFlash("success", "API Keys Updated", "Site Key and Secret Key have been saved successfully.");
        break;

      default:
        generalAddFlash("error", "Invalid Action", "The requested action is not supported.");
    }
  } catch (Throwable $th) {
    generalAddFlash("error", "Operation Failed", $th->getMessage());
  }

  generalRedirectSelf();
}

// Fetch current API keys
$currentSiteKey = "";
$currentSecretKey = "";
$result = $conn->query("SELECT site_key, secret_key FROM settings WHERE id = 1");
if ($result && $row = $result->fetch_assoc()) {
  $currentSiteKey = $row["site_key"] ?? "";
  $currentSecretKey = $row["secret_key"] ?? "";
}

$alerts = $_SESSION["admin_flash"];
$_SESSION["admin_flash"] = [];

require PATH_LIB . "PAGE-top.php";
?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="mb-0">General Settings</h1>
        <small class="text-muted">Backup and restore the full trading database.</small>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <style>
      .settings-card-icon {
        font-size: 2rem;
      }
      .restore-card {
        border: 1px solid #dc3545;
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
          <strong><?= generalEscape($alert["title"] ?? "") ?></strong>
          <div><?= generalEscape($alert["message"] ?? "") ?></div>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="row">
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white border-0">
            <div class="d-flex align-items-center">
              <div class="pr-3 text-primary settings-card-icon">
                <i class="fas fa-download"></i>
              </div>
              <div>
                <h3 class="card-title mb-0">Download Database Backup</h3>
                <small class="text-muted">Creates an SQL dump of every table in <?= generalEscape(DB_NAME) ?>.</small>
              </div>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted mb-4">
              Use this action to create a full snapshot of the current database. Keep the downloaded file in a secure
              location because it contains every user record, balance, and transaction.
            </p>
            <form method="post">
              <input type="hidden" name="action" value="download_backup">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-cloud-download-alt mr-1"></i>
                Download Backup
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4 restore-card">
          <div class="card-header bg-white border-0">
            <div class="d-flex align-items-center">
              <div class="pr-3 text-danger settings-card-icon">
                <i class="fas fa-upload"></i>
              </div>
              <div>
                <h3 class="card-title mb-0 text-danger">Restore Database</h3>
                <small class="text-muted">Overwrite the current database with a previous backup file.</small>
              </div>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted mb-4">
              Restoring will drop and recreate every table using the contents of the uploaded SQL file. Make sure you
              have an up-to-date backup before continuing.
            </p>
            <form method="post" enctype="multipart/form-data" id="restoreForm">
              <input type="hidden" name="action" value="restore_backup">
              <div class="form-group">
                <label for="db_file">Backup file (.sql)</label>
                <input type="file" id="db_file" name="db_file" class="form-control-file" accept=".sql,.txt" required>
                <small class="form-text text-muted">
                  Maximum recommended file size: 50&nbsp;MB. Only use files generated from this dashboard.
                </small>
              </div>
              <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
              <button type="submit" class="btn btn-danger">
                <i class="fas fa-history mr-1"></i>
                Restore Backup
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white border-0">
            <div class="d-flex align-items-center">
              <div class="pr-3 text-info settings-card-icon">
                <i class="fas fa-key"></i>
              </div>
              <div>
                <h3 class="card-title mb-0">API Keys</h3>
                <small class="text-muted">Configure your Site Key and Secret Key.</small>
              </div>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted mb-4">
              Update your Cloudflare reCAPTCHA credentials. These keys are required to enable CAPTCHA protection on your forms and secure user interactions against automated attacks and bots.
            </p>
            <form method="post" autocomplete="off">
              <input type="hidden" name="action" value="update_api_keys">
              <div class="form-group">
                <label for="site_key">Site Key</label>
                <input type="text" id="site_key" name="site_key" class="form-control" value="<?= generalEscape($currentSiteKey) ?>" required>
              </div>
              <div class="form-group">
                <label for="secret_key">Secret Key</label>
                <input type="password" id="secret_key" name="secret_key" class="form-control" value="<?= generalEscape($currentSecretKey) ?>" required>
              </div>
              <button type="submit" class="btn btn-info">
                <i class="fas fa-save mr-1"></i>
                Save API Keys
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var restoreForm = document.getElementById('restoreForm');
    if (restoreForm) {
      restoreForm.addEventListener('submit', function (event) {
        var confirmMessage = 'This will overwrite the current database with the uploaded backup. Continue?';
        if (!confirm(confirmMessage)) {
          event.preventDefault();
        }
      });
    }
  });
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
