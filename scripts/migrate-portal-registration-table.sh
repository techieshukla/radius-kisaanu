#!/usr/bin/env sh
set -eu

docker compose exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-change_root_password}" radius <<'SQL'
CREATE TABLE IF NOT EXISTS portal_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  mobile_number VARCHAR(20) NOT NULL,
  aadhaar_number_masked VARCHAR(32) NOT NULL,
  address_text VARCHAR(500) NOT NULL,
  client_mac VARCHAR(32) DEFAULT '',
  ap_mac VARCHAR(32) DEFAULT '',
  ssid_name VARCHAR(128) DEFAULT '',
  plan_code VARCHAR(32) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_portal_reg_username (username),
  KEY idx_portal_reg_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL

echo "portal_registrations table is ready."
