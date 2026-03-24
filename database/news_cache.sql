CREATE TABLE IF NOT EXISTS `news_cache` (
  `news_cache_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cache_key` VARCHAR(120) NOT NULL,
  `papertrade_symbol` VARCHAR(20) NOT NULL,
  `finnhub_symbol` VARCHAR(32) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`news_cache_id`),
  UNIQUE KEY `uniq_news_cache_key` (`cache_key`),
  KEY `idx_news_cache_symbol_expiry` (`papertrade_symbol`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
