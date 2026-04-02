<?php

class ActivityLogger
{
  private static $schemaEnsured = false;
  private static $preferencesEnsured = false;

  private static function hasColumn($conn, $table, $column)
  {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
    if ($table === '' || $column === '' || !$conn) {
      return false;
    }

    try {
      $sql = "
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
      ";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        return false;
      }
      $stmt->bind_param("ss", $table, $column);
      $ok = $stmt->execute();
      if (!$ok) {
        $stmt->close();
        return false;
      }
      $res = $stmt->get_result();
      $exists = $res && $res->num_rows > 0;
      $stmt->close();
      return $exists;
    } catch (Throwable $e) {
      return false;
    }
  }

  private static function hasIndex($conn, $table, $indexName)
  {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $indexName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$indexName);
    if ($table === '' || $indexName === '' || !$conn) {
      return false;
    }

    try {
      $sql = "
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
        LIMIT 1
      ";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        return false;
      }
      $stmt->bind_param("ss", $table, $indexName);
      $ok = $stmt->execute();
      if (!$ok) {
        $stmt->close();
        return false;
      }
      $res = $stmt->get_result();
      $exists = $res && $res->num_rows > 0;
      $stmt->close();
      return $exists;
    } catch (Throwable $e) {
      return false;
    }
  }

  public static function ensureSchema($conn)
  {
    if (self::$schemaEnsured || !$conn) {
      return;
    }

    if (!self::hasColumn($conn, 'activities', 'event_type')) {
      $conn->query("ALTER TABLE activities ADD COLUMN event_type VARCHAR(120) NULL AFTER page_name");
    }
    if (!self::hasColumn($conn, 'activities', 'metadata_json')) {
      $conn->query("ALTER TABLE activities ADD COLUMN metadata_json LONGTEXT NULL AFTER notes");
    }
    if (!self::hasColumn($conn, 'activities', 'is_suspicious')) {
      $conn->query("ALTER TABLE activities ADD COLUMN is_suspicious TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
    }

    if (!self::hasIndex($conn, 'activities', 'idx_activities_user_created')) {
      $conn->query("CREATE INDEX idx_activities_user_created ON activities (user_id, created_at)");
    }
    if (!self::hasIndex($conn, 'activities', 'idx_activities_event_type')) {
      $conn->query("CREATE INDEX idx_activities_event_type ON activities (event_type)");
    }

    self::$schemaEnsured = true;
  }

  public static function ensurePreferencesTable($conn)
  {
    if (self::$preferencesEnsured || !$conn) {
      return;
    }

    $conn->query("
      CREATE TABLE IF NOT EXISTS activity_preferences (
        user_id INT NOT NULL,
        muted_event_types LONGTEXT NULL,
        items_per_page SMALLINT UNSIGNED NOT NULL DEFAULT 50,
        grouping_window_minutes TINYINT UNSIGNED NOT NULL DEFAULT 12,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    self::$preferencesEnsured = true;
  }

  public static function normalizeStatus($status)
  {
    $allowed = ['Success', 'Pending', 'Failed', 'Warning'];
    return in_array($status, $allowed, true) ? $status : 'Success';
  }

  public static function deriveEventType($title, $pageName = '', $module = '')
  {
    $text = strtolower(trim((string)$title . ' ' . (string)$pageName . ' ' . (string)$module));

    if (strpos($text, 'viewed dashboard') !== false) return 'dashboard.opened';
    if (strpos($text, 'bought') !== false || strpos($text, 'buy order') !== false) return 'trade.buy';
    if (strpos($text, 'sold') !== false || strpos($text, 'sell order') !== false) return 'trade.sell';
    if (strpos($text, 'created reminder') !== false) return 'trading.reminder_created';
    if (strpos($text, 'updated reminder') !== false) return 'trading.reminder_updated';
    if (strpos($text, 'deleted reminder') !== false) return 'trading.reminder_deleted';
    if (strpos($text, 'searched stock symbol') !== false) return 'search.symbol_queried';
    if (strpos($text, 'viewed transaction details') !== false) return 'trading.transaction_viewed';
    if (strpos($text, 'viewed holdings') !== false) return 'portfolio.holdings_viewed';
    if (strpos($text, 'added') !== false && strpos($text, 'watchlist') !== false) return 'watchlist.add';
    if (strpos($text, 'removed') !== false && strpos($text, 'watchlist') !== false) return 'watchlist.remove';
    if (strpos($text, 'viewed performance') !== false) return 'portfolio.performance_viewed';
    if (strpos($text, 'exported') !== false && strpos($text, 'csv') !== false) return 'portfolio.export_csv';
    if (strpos($text, 'ran stock screener') !== false) return 'portfolio.screener_run';
    if (strpos($text, 'updated profile name') !== false) return 'account.profile_name_updated';
    if (strpos($text, 'updated email') !== false) return 'account.email_updated';
    if (strpos($text, 'changed password') !== false) return 'account.password_changed';
    if (strpos($text, 'updated notification settings') !== false) return 'account.notifications_updated';
    if (strpos($text, 'failed sign-in') !== false) return 'auth.sign_in_failed';
    if (strpos($text, 'signed in from new device') !== false) return 'auth.new_device_sign_in';
    if (strpos($text, 'suspicious sign-in') !== false) return 'security.suspicious_signin';

    return 'activity.generic';
  }

  public static function shouldLogSession($key, $ttlSeconds = 300)
  {
    $key = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string)$key);
    if ($key === '') {
      return true;
    }
    $ttlSeconds = (int)$ttlSeconds;
    if ($ttlSeconds < 10) {
      $ttlSeconds = 10;
    }
    if ($ttlSeconds > 86400) {
      $ttlSeconds = 86400;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
      return true;
    }
    if (!isset($_SESSION['_activity_once']) || !is_array($_SESSION['_activity_once'])) {
      $_SESSION['_activity_once'] = [];
    }
    $now = time();
    $last = isset($_SESSION['_activity_once'][$key]) ? (int)$_SESSION['_activity_once'][$key] : 0;
    if ($last > 0 && ($now - $last) < $ttlSeconds) {
      return false;
    }
    $_SESSION['_activity_once'][$key] = $now;
    return true;
  }

  public static function log(
    $conn,
    $userId,
    $eventType,
    $actionTitle,
    $module,
    $status = 'Success',
    $notes = null,
    $metadata = [],
    $options = []
  ) {
    if (!$conn || !$userId) {
      return false;
    }

    self::ensureSchema($conn);
    self::ensurePreferencesTable($conn);

    $status = self::normalizeStatus((string)$status);
    $eventType = trim((string)$eventType);
    if ($eventType === '') {
      $eventType = self::deriveEventType($actionTitle, $module, $module);
    }

    $pageName = trim((string)($options['page'] ?? $module));
    if ($pageName === '') {
      $pageName = 'activity';
    }

    $device = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 120);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $ipv4 = null;
    $ipv6 = null;
    if ($ipAddress) {
      if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipv4 = $ipAddress;
      } elseif (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ipv6 = $ipAddress;
      }
    }

    $notesValue = $notes !== null ? trim((string)$notes) : null;
    if ($notesValue === '') {
      $notesValue = null;
    }
    $createdAt = date('Y-m-d H:i:s');

    $isSuspicious = !empty($options['is_suspicious']) ? 1 : 0;
    $metadataJson = null;
    if (is_array($metadata) && !empty($metadata)) {
      $encoded = json_encode($metadata, JSON_UNESCAPED_SLASHES);
      $metadataJson = $encoded !== false ? $encoded : null;
    }

    $stmt = $conn->prepare("
      INSERT INTO activities (
        action_title, page_name, event_type, notes, metadata_json, status, is_suspicious, device, ipv4, ipv6, user_id, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
      $stmt->bind_param(
        "ssssssisssis",
        $actionTitle,
        $pageName,
        $eventType,
        $notesValue,
        $metadataJson,
        $status,
        $isSuspicious,
        $device,
        $ipv4,
        $ipv6,
        $userId,
        $createdAt
      );
      $ok = $stmt->execute();
      $stmt->close();
      return (bool)$ok;
    }

    $legacy = $conn->prepare("INSERT INTO activities (action_title, page_name, notes, status, device, ipv4, ipv6, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$legacy) {
      return false;
    }
    $legacy->bind_param("sssssssis", $actionTitle, $pageName, $notesValue, $status, $device, $ipv4, $ipv6, $userId, $createdAt);
    $ok = $legacy->execute();
    $legacy->close();
    return (bool)$ok;
  }

  public static function getPreferences($conn, $userId)
  {
    self::ensurePreferencesTable($conn);

    $defaults = [
      'muted_event_types' => [],
      'items_per_page' => 50,
      'grouping_window_minutes' => 12,
    ];

    if (!$conn || !$userId) {
      return $defaults;
    }

    $stmt = $conn->prepare("SELECT muted_event_types, items_per_page, grouping_window_minutes FROM activity_preferences WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
      return $defaults;
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
      $stmt->close();
      return $defaults;
    }
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
      $stmt->close();
      return $defaults;
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    $muted = [];
    if (!empty($row['muted_event_types'])) {
      $decoded = json_decode((string)$row['muted_event_types'], true);
      if (is_array($decoded)) {
        foreach ($decoded as $value) {
          $value = trim((string)$value);
          if ($value !== '') $muted[] = $value;
        }
      }
    }

    $items = (int)($row['items_per_page'] ?? 50);
    if ($items < 20) $items = 20;
    if ($items > 200) $items = 200;

    $window = (int)($row['grouping_window_minutes'] ?? 12);
    if ($window < 3) $window = 3;
    if ($window > 60) $window = 60;

    return [
      'muted_event_types' => array_values(array_unique($muted)),
      'items_per_page' => $items,
      'grouping_window_minutes' => $window,
    ];
  }

  public static function savePreferences($conn, $userId, $prefs)
  {
    self::ensurePreferencesTable($conn);
    if (!$conn || !$userId) {
      return false;
    }

    $muted = [];
    if (!empty($prefs['muted_event_types']) && is_array($prefs['muted_event_types'])) {
      foreach ($prefs['muted_event_types'] as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
          $muted[] = $value;
        }
      }
    }
    $muted = array_values(array_unique($muted));
    $mutedJson = json_encode($muted, JSON_UNESCAPED_SLASHES);
    if ($mutedJson === false) $mutedJson = '[]';

    $items = (int)($prefs['items_per_page'] ?? 50);
    if ($items < 20) $items = 20;
    if ($items > 200) $items = 200;

    $window = (int)($prefs['grouping_window_minutes'] ?? 12);
    if ($window < 3) $window = 3;
    if ($window > 60) $window = 60;

    $stmt = $conn->prepare("
      INSERT INTO activity_preferences (user_id, muted_event_types, items_per_page, grouping_window_minutes)
      VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        muted_event_types = VALUES(muted_event_types),
        items_per_page = VALUES(items_per_page),
        grouping_window_minutes = VALUES(grouping_window_minutes),
        updated_at = NOW()
    ");
    if (!$stmt) {
      return false;
    }
    $stmt->bind_param("isii", $userId, $mutedJson, $items, $window);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
  }
}
