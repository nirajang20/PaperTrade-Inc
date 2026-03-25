<?php
class NewsRepository {
  private $conn = null;
  private $cacheAvailable = null;
  private $fetchLockTableAvailable = null;

  function __construct(mysqli $connection) {
    $this->conn = $connection;
  }

  private function hasCacheTable() {
    if ($this->cacheAvailable !== null) {
      return $this->cacheAvailable;
    }

    $this->cacheAvailable = false;
    $result = $this->conn->query("SHOW TABLES LIKE 'news_cache'");
    if ($result instanceof mysqli_result) {
      $this->cacheAvailable = $result->num_rows > 0;
      $result->close();
    }

    return $this->cacheAvailable;
  }

  private function hasFetchLockTable() {
    if ($this->fetchLockTableAvailable !== null) {
      return $this->fetchLockTableAvailable;
    }

    $this->fetchLockTableAvailable = false;
    $result = $this->conn->query("SHOW TABLES LIKE 'news_fetch_lock'");
    if ($result instanceof mysqli_result) {
      $this->fetchLockTableAvailable = $result->num_rows > 0;
      $result->close();
    }

    return $this->fetchLockTableAvailable;
  }

  function getCachedArticles($cacheKey) {
    if (!$this->hasCacheTable()) {
      return null;
    }

    $sql = "SELECT payload_json FROM news_cache WHERE cache_key = ? AND expires_at > NOW() LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
      return null;
    }

    $payload = null;
    $stmt->bind_param("s", $cacheKey);
    if ($stmt->execute()) {
      $stmt->bind_result($payload);
      if ($stmt->fetch()) {
        $stmt->close();
        $decoded = json_decode((string) $payload, true);
        return is_array($decoded) ? $decoded : null;
      }
    }

    $stmt->close();
    return null;
  }

  function getLatestCachedArticles($cacheKey) {
    if (!$this->hasCacheTable()) {
      return null;
    }

    $sql = "SELECT payload_json FROM news_cache WHERE cache_key = ? LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
      return null;
    }

    $payload = null;
    $stmt->bind_param("s", $cacheKey);
    if ($stmt->execute()) {
      $stmt->bind_result($payload);
      if ($stmt->fetch()) {
        $stmt->close();
        $decoded = json_decode((string) $payload, true);
        return is_array($decoded) ? $decoded : null;
      }
    }

    $stmt->close();
    return null;
  }

  function saveCachedArticles($cacheKey, $papertradeSymbol, $finnhubSymbol, array $articles) {
    if (!$this->hasCacheTable()) {
      return false;
    }

    $payload = json_encode($articles, JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
      return false;
    }

    $sql = "
      INSERT INTO news_cache (cache_key, papertrade_symbol, finnhub_symbol, payload_json, expires_at, created_at, updated_at)
      VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        papertrade_symbol = VALUES(papertrade_symbol),
        finnhub_symbol = VALUES(finnhub_symbol),
        payload_json = VALUES(payload_json),
        expires_at = VALUES(expires_at),
        updated_at = NOW()
    ";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
      return false;
    }

    $ttl = defined("NEWS_CACHE_TTL_SECONDS") ? (int) NEWS_CACHE_TTL_SECONDS : 1800;
    $stmt->bind_param("ssssi", $cacheKey, $papertradeSymbol, $finnhubSymbol, $payload, $ttl);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
  }

  function tryAcquireFetchLock($cacheKey, $ttlSeconds = 15) {
    if (!$this->hasFetchLockTable()) {
      return true;
    }

    $ttl = max(1, (int) $ttlSeconds);

    $updateSql = "
      UPDATE news_fetch_lock
      SET lock_expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND), updated_at = NOW()
      WHERE cache_key = ? AND lock_expires_at <= NOW()
    ";
    $updateStmt = $this->conn->prepare($updateSql);
    if ($updateStmt) {
      $updateStmt->bind_param("is", $ttl, $cacheKey);
      if ($updateStmt->execute() && $updateStmt->affected_rows === 1) {
        $updateStmt->close();
        return true;
      }
      $updateStmt->close();
    }

    $insertSql = "
      INSERT IGNORE INTO news_fetch_lock (cache_key, lock_expires_at, created_at, updated_at)
      VALUES (?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW(), NOW())
    ";
    $insertStmt = $this->conn->prepare($insertSql);
    if (!$insertStmt) {
      return false;
    }

    $insertStmt->bind_param("si", $cacheKey, $ttl);
    $ok = $insertStmt->execute() && $insertStmt->affected_rows === 1;
    $insertStmt->close();

    return $ok;
  }

  function releaseFetchLock($cacheKey) {
    if (!$this->hasFetchLockTable()) {
      return;
    }

    $sql = "UPDATE news_fetch_lock SET lock_expires_at = NOW(), updated_at = NOW() WHERE cache_key = ? LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
      return;
    }

    $stmt->bind_param("s", $cacheKey);
    $stmt->execute();
    $stmt->close();
  }

  function purgeExpiredCache() {
    if (!$this->hasCacheTable()) {
      return;
    }

    $this->conn->query("DELETE FROM news_cache WHERE expires_at <= NOW()");
  }

  function getPortfolioSymbolsForUser($userId) {
    $symbols = [];
    $sql = "
      SELECT DISTINCT at.symbol
      FROM holding h
      INNER JOIN available_trade at ON at.id = h.symbol_id
      WHERE h.user_id = ?
      ORDER BY at.symbol ASC
    ";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
      return $symbols;
    }

    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
      $stmt->bind_result($symbol);
      while ($stmt->fetch()) {
        $symbols[] = strtoupper((string) $symbol);
      }
    }
    $stmt->close();

    return $symbols;
  }

  function getUserIdByEmail($email) {
    $sql = "SELECT user_id FROM users WHERE user_email = ? LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
      return 0;
    }

    $userId = 0;
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($userIdRaw);
      if ($stmt->fetch()) {
        $userId = (int) $userIdRaw;
      }
    }
    $stmt->close();

    return $userId;
  }
}
