#!/usr/bin/env sh
set -eu

echo "== Omada Cutover Precheck =="

required_vars="RADIUS_SHARED_SECRET RADIUS_CLIENT_IP NGINX_BIND_IP NGINX_HTTP_PORT RADIUS_BIND_IP RADIUS_AUTH_PORT RADIUS_ACCT_PORT"
for v in $required_vars; do
  if [ -z "${!v:-}" ]; then
    echo "FAIL: Missing env var $v"
    exit 1
  fi
done

echo "Checking compose services..."
docker compose ps

echo "Checking local HTTP endpoints..."
curl -sSI "http://127.0.0.1:${NGINX_HTTP_PORT}/wifi.php" | head -n 5
curl -sSI "http://127.0.0.1:${DALORADIUS_HTTP_PORT:-8091}/daloradius/app/operators/index.php" | head -n 6
curl -sSI "http://127.0.0.1:${PHPMYADMIN_HTTP_PORT:-8092}/" | head -n 5

echo "Checking RADIUS listener ports in compose mapping..."
docker compose ps freeradius

echo "Running local radtest..."
docker compose exec -T freeradius sh -lc "radtest demo-user demo-pass 127.0.0.1 0 ${RADIUS_SHARED_SECRET}" || {
  echo "WARN: radtest failed. Check secret and freeradius logs."
  exit 1
}

echo "PASS: Precheck completed."
