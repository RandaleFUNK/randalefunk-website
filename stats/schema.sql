CREATE TABLE IF NOT EXISTS rf_stats_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  event_date DATE NOT NULL,
  event_type VARCHAR(32) NOT NULL,
  path VARCHAR(255) NOT NULL,
  section VARCHAR(48) NOT NULL,
  visitor_day_hash CHAR(64) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_event_date (event_date),
  KEY idx_event_type (event_type),
  KEY idx_path (path),
  KEY idx_section (section),
  KEY idx_visitor_day (event_date, visitor_day_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
