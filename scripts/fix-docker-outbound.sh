#!/usr/bin/env bash
set -euo pipefail

echo "== Docker outbound connectivity fix (Ubuntu) =="

if [[ $EUID -ne 0 ]]; then
  echo "Run as root: sudo ./scripts/fix-docker-outbound.sh"
  exit 1
fi

echo "[1/6] Ensuring DNS/network tools are available..."
apt-get update
apt-get install -y curl dnsutils ca-certificates

echo "[2/6] Writing Docker daemon DNS config..."
mkdir -p /etc/docker
cat >/etc/docker/daemon.json <<'JSON'
{
  "dns": ["1.1.1.1", "8.8.8.8"],
  "features": { "buildkit": true }
}
JSON

echo "[3/6] Restarting Docker..."
systemctl daemon-reload
systemctl restart docker

echo "[4/6] Verifying DNS + registry reachability..."
nslookup registry-1.docker.io || true
curl -I --max-time 15 https://registry-1.docker.io/v2/ || true
curl -I --max-time 15 https://auth.docker.io/token || true

echo "[5/6] Pull test with retries..."
for img in nginx:stable-alpine php:8.3-fpm-alpine mysql:8.4; do
  echo "Pulling $img ..."
  ok=0
  for i in 1 2 3 4 5; do
    if docker pull "$img"; then
      ok=1
      break
    fi
    sleep 8
  done
  if [[ $ok -ne 1 ]]; then
    echo "FAILED pulling $img after retries."
    exit 1
  fi
done

echo "[6/6] Done. Run deploy:"
echo "  ./run.sh"
