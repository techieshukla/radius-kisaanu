#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"

SEED_USERNAME="${SEED_USERNAME:-info@kisaanu.com}" \
SEED_PASSWORD="${SEED_PASSWORD:-Kisaanu123765}" \
SEED_PLAN="${SEED_PLAN:-FREE_8H_DAILY}" \
SEED_FULL_NAME="${SEED_FULL_NAME:-Kisaanu Admin}" \
SEED_FATHER_NAME="${SEED_FATHER_NAME:-Admin Father}" \
SEED_MOTHER_NAME="${SEED_MOTHER_NAME:-Admin Mother}" \
SEED_VILLAGE="${SEED_VILLAGE:-Mallupur}" \
SEED_MOBILE="${SEED_MOBILE:-9999999999}" \
SEED_AADHAAR_MASKED="${SEED_AADHAAR_MASKED:-XXXXXXXX0000}" \
SEED_ADDRESS="${SEED_ADDRESS:-Kisaanu Admin Office, Mallupur, Uttar Pradesh}" \
SEED_SSID="${SEED_SSID:-MALLUPUR-KISAANU-WIFI}" \
"${SCRIPT_DIR}/seed-techieanurag-user.sh"
