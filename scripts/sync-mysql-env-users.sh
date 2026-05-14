#!/bin/sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE="${ENV_FILE:-.env}"
case "$ENV_FILE" in
  /*) ;;
  *) ENV_FILE="${ROOT_DIR}/${ENV_FILE}" ;;
esac

FALLBACK_ENV_FILE="${FALLBACK_ENV_FILE:-${ROOT_DIR}/.env.local}"
compose() {
  docker compose --env-file "$ENV_FILE" "$@"
}

wait_for_mysql_passwordless() {
  container="$1"
  i=0
  while [ "$i" -lt 60 ]; do
    if docker exec "$container" mysqladmin ping -uroot --silent >/dev/null 2>&1; then
      return 0
    fi
    i=$((i + 1))
    sleep 1
  done
  return 1
}

wait_for_compose_mysql() {
  i=0
  while [ "$i" -lt 60 ]; do
    if compose exec -T mysql mysql -uroot -p"$TARGET_ROOT_PASSWORD" -N -B -e "SELECT 1;" >/dev/null 2>&1; then
      return 0
    fi
    i=$((i + 1))
    sleep 1
  done
  return 1
}

if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: env file not found: $ENV_FILE"
  exit 1
fi

set -a
. "$ENV_FILE"
set +a

TARGET_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-}"
TARGET_RADIUS_DB="${RADIUS_DB_NAME:-${MYSQL_DATABASE:-radius}}"
TARGET_RADIUS_USER="${RADIUS_DB_USER:-${MYSQL_USER:-radius}}"
TARGET_RADIUS_PASSWORD="${RADIUS_DB_PASS:-${MYSQL_PASSWORD:-}}"

if [ -z "$TARGET_ROOT_PASSWORD" ] || [ -z "$TARGET_RADIUS_PASSWORD" ]; then
  echo "ERROR: MYSQL_ROOT_PASSWORD and RADIUS_DB_PASS/MYSQL_PASSWORD must be set in $ENV_FILE"
  exit 1
fi

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

ROOT_SQL="$(sql_escape "$TARGET_ROOT_PASSWORD")"
DB_SQL="$(sql_escape "$TARGET_RADIUS_DB")"
USER_SQL="$(sql_escape "$TARGET_RADIUS_USER")"
PASS_SQL="$(sql_escape "$TARGET_RADIUS_PASSWORD")"

root_auth_ok() {
  password="$1"
  compose exec -T mysql mysql -uroot -p"$password" -N -B -e "SELECT 1;" >/dev/null 2>&1
}

CURRENT_ROOT_PASSWORD="${MYSQL_CURRENT_ROOT_PASSWORD:-$TARGET_ROOT_PASSWORD}"
if ! root_auth_ok "$CURRENT_ROOT_PASSWORD"; then
  if [ -f "$FALLBACK_ENV_FILE" ]; then
    set -a
    . "$FALLBACK_ENV_FILE"
    set +a
    CURRENT_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-$CURRENT_ROOT_PASSWORD}"
  fi
fi

if ! root_auth_ok "$CURRENT_ROOT_PASSWORD"; then
  if [ "${FORCE:-0}" = "1" ]; then
    echo "FORCE=1 enabled: rewriting MySQL credentials to match $ENV_FILE without deleting data."
    echo "Stopping services that depend on MySQL..."
    compose stop freeradius php nginx daloradius phpmyadmin >/dev/null 2>&1 || true
    compose stop mysql >/dev/null 2>&1 || true
    docker stop radius-freeradius radius-php radius-nginx radius-daloradius radius-phpmyadmin >/dev/null 2>&1 || true
    docker stop radius-mysql >/dev/null 2>&1 || true
    docker rm -f radius-mysql-env-force >/dev/null 2>&1 || true

    echo "Starting temporary MySQL recovery container with grant checks disabled..."
    compose run --rm --no-deps --name radius-mysql-env-force -d mysql --skip-grant-tables --skip-networking=0 >/dev/null
    if ! wait_for_mysql_passwordless radius-mysql-env-force; then
      docker logs radius-mysql-env-force || true
      docker rm -f radius-mysql-env-force >/dev/null 2>&1 || true
      echo "ERROR: temporary MySQL recovery container did not become ready."
      exit 1
    fi

    echo "Applying .env credentials inside MySQL grant tables..."
    docker exec -i radius-mysql-env-force mysql -uroot <<SQL
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS \`${DB_SQL}\`;
CREATE USER IF NOT EXISTS '${USER_SQL}'@'%' IDENTIFIED BY '${PASS_SQL}';
ALTER USER '${USER_SQL}'@'%' IDENTIFIED BY '${PASS_SQL}';
GRANT ALL PRIVILEGES ON \`${DB_SQL}\`.* TO '${USER_SQL}'@'%';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '${ROOT_SQL}';
ALTER USER 'root'@'%' IDENTIFIED BY '${ROOT_SQL}';
ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_SQL}';
FLUSH PRIVILEGES;
SQL

    docker rm -f radius-mysql-env-force >/dev/null 2>&1 || true
    echo "Starting normal MySQL container..."
    if docker ps -a --format '{{.Names}}' | grep -qx 'radius-mysql'; then
      docker start radius-mysql >/dev/null
    else
      compose up -d mysql >/dev/null
    fi
    if ! wait_for_compose_mysql; then
      compose logs --tail 80 mysql || true
      echo "ERROR: normal MySQL did not accept credentials from $ENV_FILE after forced sync."
      exit 1
    fi
    CURRENT_ROOT_PASSWORD="$TARGET_ROOT_PASSWORD"
  else
    echo "ERROR: could not connect to MySQL as root with $ENV_FILE or fallback env."
    echo "Set MYSQL_CURRENT_ROOT_PASSWORD to the current MySQL volume root password and rerun this script."
    echo "Or run FORCE=1 $0 to rewrite the existing MySQL volume users to match $ENV_FILE."
    exit 1
  fi
fi

echo "Synchronizing MySQL users from $ENV_FILE..."
compose exec -T mysql mysql -uroot -p"$CURRENT_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_SQL}\`;
CREATE USER IF NOT EXISTS '${USER_SQL}'@'%' IDENTIFIED BY '${PASS_SQL}';
ALTER USER '${USER_SQL}'@'%' IDENTIFIED BY '${PASS_SQL}';
GRANT ALL PRIVILEGES ON \`${DB_SQL}\`.* TO '${USER_SQL}'@'%';
ALTER USER 'root'@'%' IDENTIFIED BY '${ROOT_SQL}';
ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_SQL}';
FLUSH PRIVILEGES;
SQL

echo "Restarting PHP and FreeRADIUS so they pick up synchronized credentials..."
if docker ps -a --format '{{.Names}}' | grep -qx 'radius-php'; then
  docker start radius-php >/dev/null 2>&1 || true
fi
if docker ps -a --format '{{.Names}}' | grep -qx 'radius-nginx'; then
  docker start radius-nginx >/dev/null 2>&1 || true
fi
if docker ps -a --format '{{.Names}}' | grep -qx 'radius-daloradius'; then
  docker start radius-daloradius >/dev/null 2>&1 || true
fi
if docker ps -a --format '{{.Names}}' | grep -qx 'radius-phpmyadmin'; then
  docker start radius-phpmyadmin >/dev/null 2>&1 || true
fi
if docker ps -a --format '{{.Names}}' | grep -qx 'radius-freeradius'; then
  docker restart radius-freeradius >/dev/null 2>&1 || true
else
  compose up -d --no-deps --force-recreate php freeradius >/dev/null
fi
echo "PASS: MySQL credentials now match $ENV_FILE."
