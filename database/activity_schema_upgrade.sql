-- Activity timeline schema upgrades
ALTER TABLE activities
  ADD COLUMN event_type VARCHAR(120) NULL AFTER page_name,
  ADD COLUMN metadata_json LONGTEXT NULL AFTER notes,
  ADD COLUMN is_suspicious TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

CREATE INDEX idx_activities_user_created ON activities (user_id, created_at);
CREATE INDEX idx_activities_event_type ON activities (event_type);

CREATE TABLE IF NOT EXISTS activity_preferences (
  user_id INT NOT NULL,
  muted_event_types LONGTEXT NULL,
  items_per_page SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  grouping_window_minutes TINYINT UNSIGNED NOT NULL DEFAULT 12,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
