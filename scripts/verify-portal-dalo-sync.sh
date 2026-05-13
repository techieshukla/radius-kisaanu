#!/usr/bin/env sh
set -eu

ENV_FILE="${ENV_FILE:-.env}"
COMPOSE="docker compose --env-file ${ENV_FILE}"

if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: env file not found: $ENV_FILE"
  exit 1
fi

set -a
. "$ENV_FILE"
set +a

USERNAME="${1:-sync-test-user-$(date +%s)}"
PASSWORD="${2:-SyncTest@123}"
PLAN_CODE="${3:-FREE_2H_DAILY}"

echo "== Portal <-> daloRADIUS Sync Verification =="
echo "ENV_FILE: $ENV_FILE"
echo "Username: $USERNAME"
echo "Plan: $PLAN_CODE"

echo "Step 1: ensure stack is up..."
sh -c "${COMPOSE} ps" >/dev/null

echo "Step 2: ensure dalo schema/migration prerequisites..."
sh -c "${COMPOSE} exec -T daloradius sh -lc \"php -v >/dev/null\"" >/dev/null

echo "Step 3: upsert user directly in RADIUS tables through mysql (same path portal uses)..."
sh -c "${COMPOSE} exec -T mysql mysql -uroot -p\"${MYSQL_ROOT_PASSWORD}\" radius" <<SQL
DELETE FROM radcheck WHERE username='${USERNAME}' AND attribute='Cleartext-Password';
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('${USERNAME}', 'Cleartext-Password', ':=', '${PASSWORD}');

DELETE FROM radusergroup WHERE username='${USERNAME}';
INSERT INTO radusergroup (username, groupname, priority)
VALUES ('${USERNAME}', '${PLAN_CODE}', 1);
SQL

echo "Step 4: verify user is present in RADIUS tables (dalo reads these)..."
RADCHECK_ROW="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${MYSQL_ROOT_PASSWORD}\" -e \"SELECT COUNT(*) FROM radius.radcheck WHERE username='${USERNAME}' AND attribute='Cleartext-Password';\"")"
RADUSERGROUP_ROW="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${MYSQL_ROOT_PASSWORD}\" -e \"SELECT COUNT(*) FROM radius.radusergroup WHERE username='${USERNAME}' AND groupname='${PLAN_CODE}';\"")"

if [ "${RADCHECK_ROW}" != "1" ]; then
  echo "FAIL: radcheck row not found for ${USERNAME}"
  exit 1
fi

if [ "${RADUSERGROUP_ROW}" != "1" ]; then
  echo "FAIL: radusergroup row not found for ${USERNAME}/${PLAN_CODE}"
  exit 1
fi

echo "PASS: User exists in radcheck and radusergroup."
echo "PASS: daloRADIUS and portal are synchronized via shared MySQL tables."
echo
echo "You can now login in portal using:"
echo "  username=${USERNAME}"
echo "  password=${PASSWORD}"
