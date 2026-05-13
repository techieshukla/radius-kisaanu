#!/bin/sh
set -eu

RADIUS_HOST="${1:-127.0.0.1}"
RADIUS_SECRET="${2:-change_shared_secret}"

if ! command -v radtest >/dev/null 2>&1; then
  echo "radtest is not installed on host."
  echo "Install freeradius-utils and retry."
  exit 1
fi

radtest demo-user demo-pass "$RADIUS_HOST" 0 "$RADIUS_SECRET"
