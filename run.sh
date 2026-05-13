#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

log() { printf "\n[%s] %s\n" "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
die() { printf "\nERROR: %s\n" "$*" >&2; exit 1; }

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1"
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
    die "Detected insecure placeholder secrets (change_*). Update .env before deploy."
  fi
}

wait_mysql_healthy() {
  local retries=30
  local i
  for ((i=1; i<=retries; i++)); do
    if docker compose ps mysql | grep -q "healthy"; then
      return 0
    fi
    sleep 2
  done
  return 1
}

log "Precheck: required tools"
require_cmd git
require_cmd docker
require_cmd php
require_cmd curl

docker info >/dev/null 2>&1 || die "Docker daemon is not reachable."
docker compose version >/dev/null 2>&1 || die "Docker Compose plugin is not available."

[[ -d .git ]] || die "This directory is not a git repository."

log "Git: fetch, switch main, pull latest"
git fetch --all --prune
git checkout main
git pull --ff-only origin main

if [[ "${CLEAN_BRANCHES:-1}" == "1" ]]; then
  log "Git: cleaning merged local branches (excluding main/master)"
  mapfile -t merged_branches < <(git for-each-ref refs/heads --format='%(refname:short)' | grep -Ev '^(main|master)$' || true)
  if [[ "${#merged_branches[@]}" -gt 0 ]]; then
    for b in "${merged_branches[@]}"; do
      git branch --merged main | grep -q " $b$" && git branch -d "$b" || true
    done
  fi
fi

log "Tests: PHP lint and unit tests"
find portal -type f -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null
php portal/tests/run.php

log "Precheck: load and validate .env"
[[ -f .env ]] || die ".env not found. Copy from .env.example first."
set -a
source .env
set +a
check_required_env

log "Docker Compose config validation"
docker compose config >/dev/null

log "Deploy: mysql"
docker compose up -d --build mysql
wait_mysql_healthy || die "MySQL did not become healthy."

log "Deploy: php and nginx"
docker compose up -d --build php nginx

log "Deploy: freeradius"
docker compose up -d --build freeradius

log "Deploy: daloradius and phpmyadmin"
docker compose up -d --build daloradius phpmyadmin

log "Run post-deploy DB tasks"
./scripts/migrate-portal-registration-table.sh
./scripts/setup-daloradius-db.sh || true

log "Post-deploy checks"
./scripts/check-captive-stack.sh
./scripts/omada-cutover-precheck.sh

log "Deployment completed successfully."
docker compose ps
