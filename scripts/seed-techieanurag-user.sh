#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
REPO_ROOT="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE_INPUT="${ENV_FILE:-.env}"
case "$ENV_FILE_INPUT" in
  /*) ENV_FILE_RESOLVED="$ENV_FILE_INPUT" ;;
  *) ENV_FILE_RESOLVED="${REPO_ROOT}/${ENV_FILE_INPUT}" ;;
esac
COMPOSE="docker compose --env-file ${ENV_FILE_RESOLVED}"
SEED_USERNAME="${SEED_USERNAME:-techieanurag@gmail.com}"
SEED_PASSWORD="${SEED_PASSWORD:-1234567890}"
SEED_PLAN="${SEED_PLAN:-FREE_8H_DAILY}"
SEED_FULL_NAME="${SEED_FULL_NAME:-Techie Anurag}"
SEED_FATHER_NAME="${SEED_FATHER_NAME:-Sample Father}"
SEED_MOTHER_NAME="${SEED_MOTHER_NAME:-Sample Mother}"
SEED_VILLAGE="${SEED_VILLAGE:-Mallupur}"
SEED_MOBILE="${SEED_MOBILE:-9876543210}"
SEED_AADHAAR_MASKED="${SEED_AADHAAR_MASKED:-XXXXXXXX9012}"
SEED_ADDRESS="${SEED_ADDRESS:-Sample Address, Mallupur, Uttar Pradesh}"
SEED_SSID="${SEED_SSID:-MALLUPUR-KISAANU-WIFI}"

if [ -f "$ENV_FILE_RESOLVED" ]; then
  set -a
  # shellcheck disable=SC1090
  . "$ENV_FILE_RESOLVED"
  set +a
fi

ROOT_PASS="${MYSQL_ROOT_PASSWORD_OVERRIDE:-${MYSQL_ROOT_PASSWORD:-change_root_password}}"
DB_NAME="${MYSQL_DATABASE:-radius}"

echo "Seeding user: ${SEED_USERNAME}"

eval "${COMPOSE} exec -T mysql mysql -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\"" <<SQL
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

INSERT INTO radcheck (username, attribute, op, value)
VALUES ('${SEED_USERNAME}', 'Cleartext-Password', ':=', '${SEED_PASSWORD}')
ON DUPLICATE KEY UPDATE value = VALUES(value);

DELETE FROM radusergroup WHERE username = '${SEED_USERNAME}';
INSERT INTO radusergroup (username, groupname, priority)
VALUES ('${SEED_USERNAME}', '${SEED_PLAN}', 1);

INSERT INTO portal_registrations
  (username, full_name, father_name, mother_name, village, mobile_number, aadhaar_number_masked, address_text, client_mac, ap_mac, ssid_name, plan_code)
VALUES
  ('${SEED_USERNAME}', '${SEED_FULL_NAME}', '${SEED_FATHER_NAME}', '${SEED_MOTHER_NAME}', '${SEED_VILLAGE}', '${SEED_MOBILE}', '${SEED_AADHAAR_MASKED}', '${SEED_ADDRESS}', '', '', '${SEED_SSID}', '${SEED_PLAN}');
SQL

echo "Seed completed for ${SEED_USERNAME}."

echo "Verify:"
eval "${COMPOSE} exec -T mysql mysql -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\" -e \"SELECT username,attribute,value FROM radcheck WHERE username='${SEED_USERNAME}';\""
eval "${COMPOSE} exec -T mysql mysql -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\" -e \"SELECT username,groupname,priority FROM radusergroup WHERE username='${SEED_USERNAME}';\""
eval "${COMPOSE} exec -T mysql mysql -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\" -e \"SELECT username,full_name,village,ssid_name,plan_code,created_at FROM portal_registrations WHERE username='${SEED_USERNAME}' ORDER BY id DESC LIMIT 1;\""
