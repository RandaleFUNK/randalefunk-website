CREATE TABLE IF NOT EXISTS rf_polls (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(120) NOT NULL,
  question VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_active (is_active, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rf_poll_options (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id INT UNSIGNED NOT NULL,
  option_text VARCHAR(255) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_poll_sort (poll_id, sort_order),
  CONSTRAINT fk_rf_poll_options_poll
    FOREIGN KEY (poll_id) REFERENCES rf_polls (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rf_poll_votes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  voter_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_poll_voter (poll_id, voter_hash),
  KEY idx_poll_option (poll_id, option_id),
  CONSTRAINT fk_rf_poll_votes_poll
    FOREIGN KEY (poll_id) REFERENCES rf_polls (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_rf_poll_votes_option
    FOREIGN KEY (option_id) REFERENCES rf_poll_options (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rf_polls (title, question, is_active)
VALUES ('Umfrage der Woche', 'Wie bist du auf RandaleFUNK aufmerksam geworden?', 1);

SET @poll_id = LAST_INSERT_ID();

INSERT INTO rf_poll_options (poll_id, option_text, sort_order) VALUES
(@poll_id, 'Über die asozialen Medien', 1),
(@poll_id, 'Durch die Empfehlung einer Band', 2),
(@poll_id, 'Wo zur Hölle bin ich hier?', 3),
(@poll_id, 'Bier!', 4);
