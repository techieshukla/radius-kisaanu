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

sh -c "${COMPOSE} exec -T daloradius sh -lc \"php -v >/dev/null\""

SCHEMA_READY="$(sh -c "${COMPOSE} exec -T mysql sh -lc \"mysql -N -B -uroot -p\\\"${MYSQL_ROOT_PASSWORD}\\\" \\\"${DB_NAME}\\\" -e \\\"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name IN ('operators','operators_acl_files','userinfo');\\\"\"")"

if [ "$SCHEMA_READY" = "3" ]; then
  echo "daloRADIUS schema already present; skipping schema import."
else
  sh -c "${COMPOSE} exec -T daloradius sh -lc \"cat /var/www/html/daloradius/contrib/db/mariadb-daloradius.sql\"" | \
    sh -c "${COMPOSE} exec -T mysql sh -lc \"mysql -uroot -p\\\"${MYSQL_ROOT_PASSWORD}\\\" \\\"${DB_NAME}\\\"\""
  echo "daloRADIUS schema imported from mariadb-daloradius.sql."
fi
