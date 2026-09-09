-- C2 preparation only. Run separately after approval; never from a public request.
-- DDL may commit implicitly. These empty tables must exist before applying retention.
CREATE TABLE IF NOT EXISTS rf_stats_daily_totals (
    event_date DATE NOT NULL,
    event_type VARCHAR(32) NOT NULL,
    event_count BIGINT UNSIGNED NOT NULL,
    visitor_day_values BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (event_date, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rf_stats_daily_dimensions (
    event_date DATE NOT NULL,
    event_type VARCHAR(32) NOT NULL,
    path VARCHAR(255) NOT NULL,
    section VARCHAR(48) NOT NULL,
    event_count BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (event_date, event_type, path, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
