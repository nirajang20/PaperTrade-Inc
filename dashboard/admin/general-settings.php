// Add this case in the switch statement (around line 249):
case "update_api_keys":
  $siteKey = trim($_POST["site_key"] ?? "");
  $secretKey = trim($_POST["secret_key"] ?? "");
  $finhubApi = trim($_POST["finhub_api"] ?? "");

  if (empty($siteKey) || empty($secretKey) || empty($finhubApi)) {
    throw new RuntimeException("Site Key, Secret Key, and Finnhub API key are required.");
  }

  $stmt = $conn->prepare(
    "INSERT INTO `settings` (`id`, `site_key`, `secret_key`, `finhub_api`) VALUES (1, ?, ?, ?)
     ON DUPLICATE KEY UPDATE `site_key` = VALUES(`site_key`), `secret_key` = VALUES(`secret_key`), `finhub_api` = VALUES(`finhub_api`)"
  );
  $stmt->bind_param("sss", $siteKey, $secretKey, $finhubApi);
  $stmt->execute();
  $stmt->close();

  generalAddFlash("success", "API Keys Updated", "Site Key, Secret Key, and Finnhub API key have been saved successfully.");
  break;

// Update the fetch section (around line 281):
$currentSiteKey = "";
$currentSecretKey = "";
$currentFinhubApi = "";
$result = $conn->query("SELECT site_key, secret_key, finhub_api FROM settings WHERE id = 1");
if ($result && $row = $result->fetch_assoc()) {
  $currentSiteKey = $row["site_key"] ?? "";
  $currentSecretKey = $row["secret_key"] ?? "";
  $currentFinhubApi = $row["finhub_api"] ?? "";
}

// Add this form field in the API Keys card (after the secret_key field):
<div class="form-group">
  <label for="finhub_api">Finnhub API Key</label>
  <input type="password" id="finhub_api" name="finhub_api" class="form-control" value="<?= generalEscape($currentFinhubApi) ?>" required>
</div>
