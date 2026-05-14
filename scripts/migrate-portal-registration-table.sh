#!/usr/bin/env sh
set -eu
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-.env}"
case "$ENV_FILE" in
  /*) ;;
  *) ENV_FILE="${ROOT_DIR}/${ENV_FILE}" ;;
esac

if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: env file not found: $ENV_FILE"
  exit 1
fi

set -a
. "$ENV_FILE"
set +a

COMPOSE="docker compose --env-file ${ENV_FILE}"
DB_NAME="${RADIUS_DB_NAME:-${MYSQL_DATABASE:-radius}}"

sh -c "${COMPOSE} exec -T mysql mysql -uroot -p\"${MYSQL_ROOT_PASSWORD}\" \"${DB_NAME}\"" <<'SQL'
CREATE TABLE IF NOT EXISTS portal_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  father_name VARCHAR(150) DEFAULT '',
  mother_name VARCHAR(150) DEFAULT '',
  village VARCHAR(150) DEFAULT '',
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

SET @db := DATABASE();
SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'portal_registrations' AND column_name = 'father_name'
);
SET @sql := IF(@exists = 0, 'ALTER TABLE portal_registrations ADD COLUMN father_name VARCHAR(150) DEFAULT '''' AFTER full_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'portal_registrations' AND column_name = 'mother_name'
);
SET @sql := IF(@exists = 0, 'ALTER TABLE portal_registrations ADD COLUMN mother_name VARCHAR(150) DEFAULT '''' AFTER father_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'portal_registrations' AND column_name = 'village'
);
SET @sql := IF(@exists = 0, 'ALTER TABLE portal_registrations ADD COLUMN village VARCHAR(150) DEFAULT '''' AFTER mother_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SQL

echo "portal_registrations table is ready."
