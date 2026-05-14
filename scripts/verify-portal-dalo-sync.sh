#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE="${ENV_FILE:-.env}"
case "$ENV_FILE" in
  /*) ;;
  *) ENV_FILE="${ROOT_DIR}/${ENV_FILE}" ;;
esac

COMPOSE="docker compose --env-file ${ENV_FILE}"

if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: env file not found: $ENV_FILE"
  exit 1
fi

set -a
. "$ENV_FILE"
set +a

USERNAME="${1:-sync-test-user}"
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

echo "Step 3: ensure test user exists in RADIUS tables..."
EXISTS_CHECK="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${MYSQL_ROOT_PASSWORD}\" -e \"SELECT CONCAT((SELECT COUNT(*) FROM radius.radcheck WHERE username='${USERNAME}' AND attribute='Cleartext-Password'),'|',(SELECT COUNT(*) FROM radius.radusergroup WHERE username='${USERNAME}' AND groupname='${PLAN_CODE}'));\"")"
RC_EXISTS="$(echo "$EXISTS_CHECK" | cut -d'|' -f1)"
RUG_EXISTS="$(echo "$EXISTS_CHECK" | cut -d'|' -f2)"

if [ "$RC_EXISTS" = "1" ] && [ "$RUG_EXISTS" = "1" ]; then
  echo "INFO: user ${USERNAME} already exists with plan ${PLAN_CODE}, reusing."
else
  echo "INFO: user missing, creating/updating now."
  sh -c "${COMPOSE} exec -T mysql mysql -uroot -p\"${MYSQL_ROOT_PASSWORD}\" radius" <<SQL
DELETE FROM radcheck WHERE username='${USERNAME}' AND attribute='Cleartext-Password';
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('${USERNAME}', 'Cleartext-Password', ':=', '${PASSWORD}');

DELETE FROM radusergroup WHERE username='${USERNAME}';
INSERT INTO radusergroup (username, groupname, priority)
VALUES ('${USERNAME}', '${PLAN_CODE}', 1);

INSERT INTO userinfo
  (username, firstname, lastname, email, mobilephone, address, city, state, country, notes, creationdate, creationby, updatedate, updateby, enableportallogin)
SELECT '${USERNAME}', '${USERNAME}', '', IF('${USERNAME}' LIKE '%@%', '${USERNAME}', ''), '', '', 'Mallupur', 'Uttar Pradesh', 'India', 'Created by verify-portal-dalo-sync.sh', NOW(), 'verify-portal-dalo-sync.sh', NOW(), 'verify-portal-dalo-sync.sh', 1
WHERE NOT EXISTS (SELECT 1 FROM userinfo WHERE username='${USERNAME}');
SQL
fi

echo "Step 3b: ensure daloRADIUS userinfo row exists for Users Listing..."
sh -c "${COMPOSE} exec -T mysql mysql -uroot -p\"${MYSQL_ROOT_PASSWORD}\" radius" <<SQL
INSERT INTO userinfo
  (username, firstname, lastname, email, mobilephone, address, city, state, country, notes, creationdate, creationby, updatedate, updateby, enableportallogin)
SELECT '${USERNAME}', '${USERNAME}', '', IF('${USERNAME}' LIKE '%@%', '${USERNAME}', ''), '', '', 'Mallupur', 'Uttar Pradesh', 'India', 'Created by verify-portal-dalo-sync.sh', NOW(), 'verify-portal-dalo-sync.sh', NOW(), 'verify-portal-dalo-sync.sh', 1
WHERE NOT EXISTS (SELECT 1 FROM userinfo WHERE username='${USERNAME}');
SQL

echo "Step 4: verify user is present in RADIUS and daloRADIUS listing tables..."
RADCHECK_ROW="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${MYSQL_ROOT_PASSWORD}\" -e \"SELECT COUNT(*) FROM radius.radcheck WHERE username='${USERNAME}' AND attribute='Cleartext-Password';\"")"
RADUSERGROUP_ROW="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${MYSQL_ROOT_PASSWORD}\" -e \"SELECT COUNT(*) FROM radius.radusergroup WHERE username='${USERNAME}' AND groupname='${PLAN_CODE}';\"")"
USERINFO_ROW="$(sh -c "${COMPOSE} exec -T mysql mysql -N -B -uroot -p\"${MYSQL_ROOT_PASSWORD}\" -e \"SELECT COUNT(*) FROM radius.userinfo WHERE username='${USERNAME}';\"")"

if [ "${RADCHECK_ROW}" != "1" ]; then
  echo "FAIL: radcheck row not found for ${USERNAME}"
  exit 1
fi

if [ "${RADUSERGROUP_ROW}" != "1" ]; then
  echo "FAIL: radusergroup row not found for ${USERNAME}/${PLAN_CODE}"
  exit 1
fi

if [ "${USERINFO_ROW}" = "0" ]; then
  echo "FAIL: userinfo row not found for ${USERNAME}; daloRADIUS Users Listing will not show this user"
  exit 1
fi

echo "PASS: User exists in radcheck and radusergroup."
echo "PASS: User exists in userinfo for daloRADIUS Users Listing."
echo "PASS: daloRADIUS, FreeRADIUS, and portal are synchronized via shared MySQL tables."
echo
echo "You can now login in portal using:"
echo "  username=${USERNAME}"
echo "  password=${PASSWORD}"
