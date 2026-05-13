#!/usr/bin/env sh
set -eu

PORTAL_PORT="${NGINX_HTTP_PORT:-8090}"
DALO_PORT="${DALORADIUS_HTTP_PORT:-8091}"
PMA_PORT="${PHPMYADMIN_HTTP_PORT:-8092}"

echo "== Captive Stack Health Check =="
docker compose ps

echo "-- Portal --"
curl -sSI "http://127.0.0.1:${PORTAL_PORT}/wifi.php" | head -n 5

echo "-- daloRADIUS --"
curl -sSI "http://127.0.0.1:${DALO_PORT}/daloradius/app/operators/index.php" | head -n 6

echo "-- phpMyAdmin --"
curl -sSI "http://127.0.0.1:${PMA_PORT}/" | head -n 5

echo "-- FreeRADIUS --"
docker compose exec -T freeradius sh -lc "radtest demo-user demo-pass 127.0.0.1 0 ${RADIUS_SHARED_SECRET:-change_shared_secret}" | tail -n 10 || true

echo "Health check completed."
