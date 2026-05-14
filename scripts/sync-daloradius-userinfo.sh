#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE_INPUT="${ENV_FILE:-.env}"
case "$ENV_FILE_INPUT" in
  /*) ENV_FILE_RESOLVED="$ENV_FILE_INPUT" ;;
  *) ENV_FILE_RESOLVED="${ROOT_DIR}/${ENV_FILE_INPUT}" ;;
esac

if [ ! -f "$ENV_FILE_RESOLVED" ]; then
  echo "ERROR: env file not found: $ENV_FILE_RESOLVED"
  exit 1
fi

set -a
# shellcheck disable=SC1090
. "$ENV_FILE_RESOLVED"
set +a

COMPOSE="docker compose --env-file ${ENV_FILE_RESOLVED}"
ROOT_PASS="${MYSQL_ROOT_PASSWORD_OVERRIDE:-${MYSQL_ROOT_PASSWORD:-change_root_password}}"
DB_NAME="${MYSQL_DATABASE:-radius}"

echo "Syncing daloRADIUS userinfo rows from RADIUS users..."

sh -c "${COMPOSE} exec -T mysql mysql -uroot -p\"${ROOT_PASS}\" \"${DB_NAME}\"" <<'SQL'
INSERT INTO userinfo
  (username, firstname, lastname, email, mobilephone, address, city, state, country, notes, creationdate, creationby, updatedate, updateby, enableportallogin)
SELECT
  users.username,
  CASE
    WHEN TRIM(COALESCE(pr.full_name, '')) <> '' THEN SUBSTRING_INDEX(TRIM(pr.full_name), ' ', 1)
    ELSE users.username
  END AS firstname,
  CASE
    WHEN TRIM(COALESCE(pr.full_name, '')) <> '' AND INSTR(TRIM(pr.full_name), ' ') > 0
      THEN TRIM(SUBSTRING(TRIM(pr.full_name), LENGTH(SUBSTRING_INDEX(TRIM(pr.full_name), ' ', 1)) + 1))
    ELSE ''
  END AS lastname,
  CASE WHEN users.username LIKE '%@%' THEN users.username ELSE '' END AS email,
  COALESCE(NULLIF(pr.mobile_number, ''), '') AS mobilephone,
  COALESCE(NULLIF(pr.address_text, ''), '') AS address,
  COALESCE(NULLIF(pr.village, ''), 'Mallupur') AS city,
  'Uttar Pradesh' AS state,
  'India' AS country,
  'Backfilled by scripts/sync-daloradius-userinfo.sh' AS notes,
  NOW() AS creationdate,
  'sync-daloradius-userinfo.sh' AS creationby,
  NOW() AS updatedate,
  'sync-daloradius-userinfo.sh' AS updateby,
  1 AS enableportallogin
FROM (
  SELECT DISTINCT username
  FROM radcheck
  WHERE attribute = 'Auth-Type' OR attribute LIKE '%-Password'
) users
LEFT JOIN userinfo ui ON ui.username = users.username
LEFT JOIN portal_registrations pr
  ON pr.id = (
    SELECT MAX(pr2.id)
    FROM portal_registrations pr2
    WHERE pr2.username = users.username
  )
WHERE ui.username IS NULL;

UPDATE userinfo ui
LEFT JOIN portal_registrations pr
  ON pr.id = (
    SELECT MAX(pr2.id)
    FROM portal_registrations pr2
    WHERE pr2.username = ui.username
  )
SET
  ui.firstname = CASE
    WHEN TRIM(COALESCE(pr.full_name, '')) <> '' THEN SUBSTRING_INDEX(TRIM(pr.full_name), ' ', 1)
    WHEN TRIM(COALESCE(ui.firstname, '')) = '' THEN ui.username
    ELSE ui.firstname
  END,
  ui.lastname = CASE
    WHEN TRIM(COALESCE(pr.full_name, '')) <> '' AND INSTR(TRIM(pr.full_name), ' ') > 0
      THEN TRIM(SUBSTRING(TRIM(pr.full_name), LENGTH(SUBSTRING_INDEX(TRIM(pr.full_name), ' ', 1)) + 1))
    ELSE COALESCE(ui.lastname, '')
  END,
  ui.email = CASE WHEN ui.username LIKE '%@%' THEN ui.username ELSE COALESCE(ui.email, '') END,
  ui.mobilephone = COALESCE(NULLIF(pr.mobile_number, ''), ui.mobilephone, ''),
  ui.address = COALESCE(NULLIF(pr.address_text, ''), ui.address, ''),
  ui.city = COALESCE(NULLIF(pr.village, ''), ui.city, 'Mallupur'),
  ui.updatedate = NOW(),
  ui.updateby = 'sync-daloradius-userinfo.sh'
WHERE EXISTS (
  SELECT 1
  FROM radcheck rc
  WHERE rc.username = ui.username
    AND (rc.attribute = 'Auth-Type' OR rc.attribute LIKE '%-Password')
);

SELECT
  (SELECT COUNT(DISTINCT username) FROM radcheck WHERE attribute = 'Auth-Type' OR attribute LIKE '%-Password') AS radius_users,
  (SELECT COUNT(DISTINCT ui.username)
     FROM userinfo ui
     JOIN radcheck rc ON rc.username = ui.username
    WHERE rc.attribute = 'Auth-Type' OR rc.attribute LIKE '%-Password') AS daloradius_visible_users;
SQL

echo "daloRADIUS userinfo sync completed."
