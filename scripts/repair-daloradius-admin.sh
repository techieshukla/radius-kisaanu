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
ROOT_PASS="${MYSQL_ROOT_PASSWORD_OVERRIDE:-${MYSQL_ROOT_PASSWORD:-change_root_password}}"
DALO_ADMIN_USERNAME="${DALO_ADMIN_USERNAME:-administrator}"
DALO_ADMIN_PASSWORD="${DALO_ADMIN_PASSWORD:-Kisaanu123765}"
DALO_ADMIN_EMAIL="${DALO_ADMIN_EMAIL:-info@kisaanu.com}"

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

ADMIN_USER_SQL="$(sql_escape "$DALO_ADMIN_USERNAME")"
ADMIN_PASS_SQL="$(sql_escape "$DALO_ADMIN_PASSWORD")"
ADMIN_EMAIL_SQL="$(sql_escape "$DALO_ADMIN_EMAIL")"

TABLES_PRESENT="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\" -e \"SELECT CONCAT((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='operators'),'|',(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='operators_acl'),'|',(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='operators_acl_files'));\"")"

if [ "$TABLES_PRESENT" != "1|1|1" ]; then
  echo "ERROR: daloRADIUS operator tables are missing in ${DB_NAME}."
  echo "Run ./scripts/setup-daloradius-db.sh first, then rerun this script."
  exit 1
fi

echo "Repairing daloRADIUS operator admin: ${DALO_ADMIN_USERNAME}"

sh -c "${COMPOSE} exec -T mysql mysql -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\"" <<SQL
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS _dalo_admin_ids;
CREATE TEMPORARY TABLE _dalo_admin_ids AS
  SELECT id FROM operators WHERE username = '${ADMIN_USER_SQL}';

DELETE FROM operators_acl
WHERE operator_id IN (SELECT id FROM _dalo_admin_ids);

DELETE FROM operators
WHERE username = '${ADMIN_USER_SQL}';

INSERT INTO operators
  (username, password, firstname, lastname, title, department, company, phone1, phone2, email1, email2, messenger1, messenger2, notes, lastlogin, creationdate, creationby, updatedate, updateby)
VALUES
  ('${ADMIN_USER_SQL}', '${ADMIN_PASS_SQL}', 'Kisaanu', 'Admin', 'Administrator', 'WiFi', 'Kisaanu', '', '', '${ADMIN_EMAIL_SQL}', '', '', '', 'Managed by scripts/repair-daloradius-admin.sh', NULL, NOW(), 'script', NULL, NULL);

SET @operator_id := LAST_INSERT_ID();

INSERT INTO operators_acl (operator_id, file, access)
SELECT @operator_id, file, 1
FROM operators_acl_files;

COMMIT;

SELECT id, username, password, firstname, lastname, title, email1, lastlogin, creationdate
FROM operators
WHERE username = '${ADMIN_USER_SQL}';

SELECT COUNT(*) AS admin_rows
FROM operators
WHERE username = '${ADMIN_USER_SQL}';

SELECT COUNT(*) AS admin_acl_rows
FROM operators_acl
WHERE operator_id = @operator_id AND access = 1;
SQL

echo "daloRADIUS admin repaired. Login with:"
echo "  username=${DALO_ADMIN_USERNAME}"
echo "  password=${DALO_ADMIN_PASSWORD}"
