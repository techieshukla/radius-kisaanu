#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"
ENV_FILE="${ENV_FILE:-.env}"
export ENV_FILE
COMPOSE=(docker compose --env-file "$ENV_FILE")
CURRENT_PHASE="init"

log() { printf "\n[%s] %s\n" "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
die() { printf "\nERROR: %s\n" "$*" >&2; exit 1; }
on_error() {
  local exit_code=$?
  printf "\nERROR: run.sh failed during phase: %s (exit=%s)\n" "$CURRENT_PHASE" "$exit_code" >&2
}
trap on_error ERR

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1"
}

check_port_conflicts() {
  local tcp_ports=("${NGINX_HTTP_PORT}" "${DALORADIUS_HTTP_PORT}" "${PHPMYADMIN_HTTP_PORT}")
  local udp_ports=("${RADIUS_AUTH_PORT}" "${RADIUS_ACCT_PORT}")
  local p
  local out

  for p in "${tcp_ports[@]}"; do
    out="$(ss -ltnp "( sport = :${p} )" 2>/dev/null | tail -n +2 || true)"
    if [[ -n "$out" ]] && ! grep -q "docker-proxy" <<<"$out"; then
      printf "\nERROR: TCP port %s is already in use by a non-docker process.\n%s\n" "$p" "$out" >&2
      die "Resolve conflict (or change port in ${ENV_FILE}) before deploy."
    fi
  done

  for p in "${udp_ports[@]}"; do
    out="$(ss -lunp "( sport = :${p} )" 2>/dev/null | tail -n +2 || true)"
    if [[ -n "$out" ]] && ! grep -q "docker-proxy" <<<"$out"; then
      printf "\nERROR: UDP port %s is already in use by a non-docker process.\n%s\n" "$p" "$out" >&2
      die "Resolve conflict (or change RADIUS port in ${ENV_FILE}) before deploy."
    fi
  done
}

retry_cmd() {
  local tries="$1"; shift
  local delay="$1"; shift
  local n=1
  until "$@"; do
    if (( n >= tries )); then
      return 1
    fi
    log "Retry ${n}/${tries} failed. Waiting ${delay}s: $*"
    sleep "$delay"
    n=$((n + 1))
  done
}

prepull_base_images() {
  local images=(
    "php:8.3-fpm-alpine"
    "nginx:stable-alpine"
    "mysql:8.4"
    "phpmyadmin:latest"
    "php:8.3-apache"
    "debian:bookworm-slim"
  )
  local img
  for img in "${images[@]}"; do
    log "Pre-pull image: ${img}"
    retry_cmd 5 10 docker pull "$img" || die "Failed to pull ${img}. Check outbound DNS/443 to Docker Hub."
  done
}

check_required_env() {
  local required=(
    MYSQL_ROOT_PASSWORD
    MYSQL_PASSWORD
    RADIUS_DB_PASS
    RADIUS_SHARED_SECRET
    RADIUS_CLIENT_IP
    NGINX_BIND_IP
    NGINX_HTTP_PORT
    DALORADIUS_BIND_IP
    DALORADIUS_HTTP_PORT
    PHPMYADMIN_BIND_IP
    PHPMYADMIN_HTTP_PORT
    RADIUS_BIND_IP
    RADIUS_AUTH_PORT
    RADIUS_ACCT_PORT
    OMADA_CONTROLLER_IP
  )

  for key in "${required[@]}"; do
    if [[ -z "${!key:-}" ]]; then
      die "Missing .env value: $key"
    fi
  done

  if [[ "${MYSQL_ROOT_PASSWORD}" == change_* || "${MYSQL_PASSWORD}" == change_* || "${RADIUS_SHARED_SECRET}" == change_* ]]; then
    die "Detected insecure placeholder secrets (change_*). Update ${ENV_FILE} before deploy."
  fi
  if [[ "${MYSQL_ROOT_PASSWORD}" == REPLACE_ME* || "${MYSQL_PASSWORD}" == REPLACE_ME* || "${RADIUS_SHARED_SECRET}" == REPLACE_ME* ]]; then
    die "Detected placeholder values (REPLACE_ME*). Update ${ENV_FILE} before deploy."
  fi
}

wait_mysql_healthy() {
  local retries=30
  local i
  for ((i=1; i<=retries; i++)); do
    if "${COMPOSE[@]}" ps mysql | grep -q "healthy"; then
      return 0
    fi
    sleep 2
  done
  return 1
}

check_radius_runtime() {
  local psout
  local auth_bind
  local acct_bind

  psout="$("${COMPOSE[@]}" ps freeradius)"
  echo "$psout"
  echo "$psout" | grep -q "Up" || die "freeradius is not running."

  auth_bind="$(ss -lunp "( sport = :${RADIUS_AUTH_PORT} )" 2>/dev/null | tail -n +2 || true)"
  acct_bind="$(ss -lunp "( sport = :${RADIUS_ACCT_PORT} )" 2>/dev/null | tail -n +2 || true)"

  [[ -n "$auth_bind" ]] || die "freeradius auth UDP port ${RADIUS_AUTH_PORT} is not bound."
  [[ -n "$acct_bind" ]] || die "freeradius acct UDP port ${RADIUS_ACCT_PORT} is not bound."
}

CURRENT_PHASE="precheck.tools"
log "Phase precheck.tools: required tools"
require_cmd git
require_cmd docker
require_cmd php
require_cmd curl
require_cmd ss

docker info >/dev/null 2>&1 || die "Docker daemon is not reachable."
docker compose version >/dev/null 2>&1 || die "Docker Compose plugin is not available."

[[ -d .git ]] || die "This directory is not a git repository."

CURRENT_PHASE="precheck.registry"
log "Phase precheck.registry: pre-pull Docker base images with retries"
prepull_base_images
log "PASS: Docker base images pulled"

CURRENT_PHASE="git.sync"
log "Phase git.sync: fetch, switch main, pull latest"
git fetch --all --prune
git checkout main
git pull --ff-only origin main

if [[ "${CLEAN_BRANCHES:-1}" == "1" ]]; then
  CURRENT_PHASE="git.cleanup"
  log "Phase git.cleanup: cleaning merged local branches (excluding main/master)"
  mapfile -t merged_branches < <(git for-each-ref refs/heads --format='%(refname:short)' | grep -Ev '^(main|master)$' || true)
  if [[ "${#merged_branches[@]}" -gt 0 ]]; then
    for b in "${merged_branches[@]}"; do
      git branch --merged main | grep -q " $b$" && git branch -d "$b" || true
    done
  fi
fi

CURRENT_PHASE="tests.lint"
log "Phase tests.lint: PHP lint"
find portal -type f -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null
log "PASS: PHP lint completed"
CURRENT_PHASE="tests.unit"
log "Phase tests.unit: unit tests"
php portal/tests/run.php
log "PASS: UNIT TESTS PASSED"

CURRENT_PHASE="env.validate"
log "Phase env.validate: load and validate env file (${ENV_FILE})"
[[ -f "$ENV_FILE" ]] || die "${ENV_FILE} not found."
set -a
source "$ENV_FILE"
set +a
check_required_env

CURRENT_PHASE="compose.validate"
log "Phase compose.validate: docker compose config"
"${COMPOSE[@]}" config >/dev/null
log "PASS: docker compose config is valid"

CURRENT_PHASE="precheck.ports"
log "Phase precheck.ports: checking host port conflicts"
check_port_conflicts
log "PASS: no blocking host port conflicts detected"

CURRENT_PHASE="deploy.mysql"
log "Phase deploy.mysql: mysql"
"${COMPOSE[@]}" up -d --build mysql
wait_mysql_healthy || die "MySQL did not become healthy."
log "PASS: mysql healthy"

CURRENT_PHASE="deploy.portal"
log "Phase deploy.portal: php and nginx"
"${COMPOSE[@]}" up -d --build php nginx
log "PASS: php and nginx deployed"

CURRENT_PHASE="deploy.radius"
log "Phase deploy.radius: freeradius precheck/restart/postcheck"
if [[ -n "$("${COMPOSE[@]}" ps -q freeradius 2>/dev/null || true)" ]]; then
  log "Precheck: existing freeradius status before restart"
  check_radius_runtime
else
  log "Precheck: freeradius container not created yet, proceeding with first start"
fi

"${COMPOSE[@]}" up -d --build freeradius
"${COMPOSE[@]}" restart freeradius
sleep 2
log "Postcheck: freeradius status after restart"
check_radius_runtime
log "PASS: freeradius deployed and restart checks passed"

CURRENT_PHASE="deploy.admin"
log "Phase deploy.admin: daloradius and phpmyadmin"
"${COMPOSE[@]}" up -d --build daloradius phpmyadmin
log "PASS: admin UIs deployed"

CURRENT_PHASE="postdeploy.dbtasks"
log "Phase postdeploy.dbtasks: DB migration/bootstrap"
./scripts/migrate-portal-registration-table.sh
./scripts/setup-daloradius-db.sh || true
log "PASS: DB tasks completed"

CURRENT_PHASE="postdeploy.checks"
log "Phase postdeploy.checks: health checks"
./scripts/check-captive-stack.sh
./scripts/omada-cutover-precheck.sh
log "PASS: health checks completed"

CURRENT_PHASE="postdeploy.syncverify"
log "Phase postdeploy.syncverify: verify portal-dalo sync"
SYNC_VERIFY_USER="${SYNC_VERIFY_USER:-sync-test-user}"
SYNC_VERIFY_PASS="${SYNC_VERIFY_PASS:-SyncTest@123}"
SYNC_VERIFY_PLAN="${SYNC_VERIFY_PLAN:-FREE_2H_DAILY}"
ENV_FILE="$ENV_FILE" ./scripts/verify-portal-dalo-sync.sh "$SYNC_VERIFY_USER" "$SYNC_VERIFY_PASS" "$SYNC_VERIFY_PLAN"
log "PASS: portal-dalo sync verified"

CURRENT_PHASE="done"
log "Deployment completed successfully."
"${COMPOSE[@]}" ps
