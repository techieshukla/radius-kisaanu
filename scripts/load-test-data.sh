#!/bin/sh
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
INSERT INTO radacct (
  acctsessionid,
  acctuniqueid,
  username,
  nasipaddress,
  acctstarttime,
  acctstoptime,
  acctsessiontime,
  calledstationid,
  callingstationid,
  acctterminatecause,
  framedipaddress
) VALUES (
  CONCAT('sess-', UNIX_TIMESTAMP()),
  MD5(CONCAT('demo-user', UNIX_TIMESTAMP())),
  'demo-user',
  '192.168.0.2',
  UTC_TIMESTAMP(),
  UTC_TIMESTAMP(),
  600,
  'KISAANU-SSID',
  'AA-BB-CC-DD-EE-FF',
  'User-Request',
  '10.0.0.10'
);
SQL

echo "Inserted sample radacct row for demo-user"
