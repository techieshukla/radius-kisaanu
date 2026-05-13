# radius-kisaanu

[![CI](https://github.com/techieshukla/radius-kisaanu/actions/workflows/ci.yml/badge.svg)](https://github.com/techieshukla/radius-kisaanu/actions/workflows/ci.yml)
![Docker Compose](https://img.shields.io/badge/docker-compose-blue)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-3.x-2a7fff)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4)
![Nginx](https://img.shields.io/badge/Nginx-stable-009639)

Production-focused captive portal backend for public Wi-Fi with:
- FreeRADIUS auth/accounting
- MySQL user/plan/session data
- PHP captive portal (register + login)
- daloRADIUS admin GUI
- phpMyAdmin DB GUI
- Omada ER605 + EAP225 Outdoor integration support

## Stack

| Layer | Component | Purpose | Exposed Ports |
|---|---|---|---|
| Portal | Nginx + PHP-FPM | Captive login/register pages | `${NGINX_BIND_IP}:${NGINX_HTTP_PORT}` |
| AAA | FreeRADIUS | RADIUS auth/accounting | `${RADIUS_BIND_IP}:${RADIUS_AUTH_PORT}/udp`, `${RADIUS_BIND_IP}:${RADIUS_ACCT_PORT}/udp` |
| Data | MySQL 8.4 | Users, plans, accounting, portal metadata | internal only |
| RADIUS GUI | daloRADIUS | User/group/accounting management | `${DALORADIUS_BIND_IP}:${DALORADIUS_HTTP_PORT}` |
| DB GUI | phpMyAdmin | DB admin and SQL inspection | `${PHPMYADMIN_BIND_IP}:${PHPMYADMIN_HTTP_PORT}` |

## Runtime Packages and Images

| Service | Base Image / Runtime |
|---|---|
| `freeradius` | `debian:bookworm-slim` + `freeradius`, `freeradius-utils`, `freeradius-mysql` |
| `mysql` | `mysql:8.4` |
| `php` | `php:8.3-fpm-alpine` + `pdo_mysql`, `mysqli`, `curl` |
| `nginx` | `nginx:stable-alpine` |
| `daloradius` | `php:8.3-apache` + PEAR `DB` + patched daloRADIUS |
| `phpmyadmin` | `phpmyadmin:latest` + Apache `ServerName` fix |

## Key Features

- Plan-based free usage (`2h/4h/6h/8h`) via `plan_profiles`
- Daily quota enforcement from `radacct`
- Registration flow with profile capture (`portal_registrations`)
- Omada forward-auth bridge (`target`, `clientMac`, `apMac`, `ssidName`, `radioId`)
- daloRADIUS PHP 8.x compatibility and permission fixes
- Bind-IP controls for all exposed services via `.env`

## Repository Structure

- `docker-compose.yml`: full service topology
- `portal/public/wifi.php`: captive portal UI + request flow
- `portal/src/`: auth service, DB repo, Omada client, logging
- `freeradius/`: site policy, SQL integration, clients template
- `mysql/init/`: schema + seed data
- `daloradius/`: image build and runtime fixes
- `nginx/`: portal routing + PHP fastcgi
- `scripts/`: checks, migrations, bootstrap helpers
- `docs/OMADA_ER605_EAP225_EXECUTION_PLAN.md`: 1-8 execution plan
- `DEPLOY_EC2_UBUNTU24.md`: full EC2 deploy commands

## Quick Start (Local)

1. Configure local env:
```bash
nano .env.local
```

2. Build and start:
```bash
docker compose --env-file .env.local up -d --build
docker compose --env-file .env.local ps
```

3. Optional DB updates for existing volume:
```bash
ENV_FILE=.env.local ./scripts/migrate-portal-registration-table.sh
ENV_FILE=.env.local ./scripts/setup-daloradius-db.sh
```

4. Health checks:
```bash
ENV_FILE=.env.local ./scripts/check-captive-stack.sh
ENV_FILE=.env.local ./scripts/omada-cutover-precheck.sh
```

## One-Command Server Deploy

After cloning on server, run:

```bash
./run.sh
```

What it does:
- prechecks tooling and docker daemon
- pre-pulls required Docker base images with retries
- fetch/pull latest `main`
- cleans merged local branches (can disable: `CLEAN_BRANCHES=0 ./run.sh`)
- runs PHP lint + unit tests
- validates `.env` required keys/secrets
- deploys services step-by-step with streaming output
- runs DB migration/bootstrap tasks
- executes post-deploy health checks
- verifies portal/daloRADIUS sync user exists in shared MySQL tables

Environment selection:
- Production default: `.env`
- Local: `ENV_FILE=.env.local ./run.sh`
- Sync verification overrides (optional): `SYNC_VERIFY_USER`, `SYNC_VERIFY_PASS`, `SYNC_VERIFY_PLAN`
- Optional known-port bypass for precheck (comma-separated): `ALLOW_PORT_CONFLICTS`
  - Example: `ALLOW_PORT_CONFLICTS=8090 ./run.sh`

## Full Deploy Guides

- General Wi-Fi/LAN deploy:
  - [DEPLOY.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/DEPLOY.md)
- Ubuntu 24.04 EC2 full command runbook:
  - [DEPLOY_EC2_UBUNTU24.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/DEPLOY_EC2_UBUNTU24.md)
- Omada ER605 + EAP225 implementation plan:
  - [docs/OMADA_ER605_EAP225_EXECUTION_PLAN.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/docs/OMADA_ER605_EAP225_EXECUTION_PLAN.md)
- Omada form field values for `wifi.kisaanu.com`:
  - [docs/OMADA_FIELD_VALUES_WIFI_KISAANU.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/docs/OMADA_FIELD_VALUES_WIFI_KISAANU.md)
- `wifi.kisaanu.com` without port via host Nginx reverse proxy:
  - [docs/WIFI_DOMAIN_NO_PORT_NGINX_PROXY.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/docs/WIFI_DOMAIN_NO_PORT_NGINX_PROXY.md)
- Protocol/port matrix:
  - [OMADA_EAP225_OUTDOOR.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/OMADA_EAP225_OUTDOOR.md)

## Omada Mapping (Paste Values)

Use in Omada Controller (production):
- External Portal URL:
  - `https://wifi.kisaanu.com/wifi.php`
- Same domain admin URLs:
  - daloRADIUS: `https://wifi.kisaanu.com/daloradius/`
  - phpMyAdmin: `https://wifi.kisaanu.com/phpmyadmin/`
- RADIUS Server:
  - Host: `3.111.219.106`
  - Auth: `1812`
  - Acct: `1813`
  - Secret: `.env -> RADIUS_SHARED_SECRET`

## Environment Variables

Core bind and ports:
- `NGINX_BIND_IP`, `NGINX_HTTP_PORT`
- `DALORADIUS_BIND_IP`, `DALORADIUS_HTTP_PORT`
- `PHPMYADMIN_BIND_IP`, `PHPMYADMIN_HTTP_PORT`
- `RADIUS_BIND_IP`, `RADIUS_AUTH_PORT`, `RADIUS_ACCT_PORT`
- `MYSQL_VOLUME_NAME` (named persistent mysql volume)

RADIUS/DB:
- `RADIUS_SHARED_SECRET`
- `RADIUS_CLIENT_NAME`, `RADIUS_CLIENT_IP`
- `RADIUS_DB_HOST`, `RADIUS_DB_NAME`, `RADIUS_DB_USER`, `RADIUS_DB_PASS`

Portal/infra:
- `OMADA_CONTROLLER_IP`
- `TZ`

## Testing

Backend tests:
```bash
php portal/tests/run.php
```

Portal endpoint quick check:
```bash
ENV_FILE=.env.local ./scripts/check-captive-stack.sh
```

RADIUS local check:
```bash
docker compose exec -T freeradius sh -lc "radtest demo-user demo-pass 127.0.0.1 0 ${RADIUS_SHARED_SECRET:-change_shared_secret}"
```

## CI

GitHub Actions workflow: `.github/workflows/ci.yml`

CI validates:
- PHP syntax lint for portal source
- Portal unit tests (`portal/tests/run.php`)
- Shell script lint (`shellcheck`)
- Docker Compose config resolution (`docker compose config`)

## Git Labels

Label catalog:
- [.github/LABELS.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/.github/LABELS.md)

Create/update labels in GitHub repo:
```bash
./scripts/setup-github-labels.sh techieshukla/radius-kisaanu
```

## Operations

- Captive stack checks: `./scripts/check-captive-stack.sh`
- Omada cutover readiness: `./scripts/omada-cutover-precheck.sh`
- Restrict admin ports to CIDR:
  - `./scripts/secure-admin-ports-ufw.sh <admin-cidr>`
- Fix outbound Docker registry connectivity on Ubuntu servers:
  - `sudo ./scripts/fix-docker-outbound.sh`

## Optional: Isolated Omada Controller Compose

For separated controller deployment:
- `omada-controller/docker-compose.yml`
- `omada-controller/.env.example`

Start:
```bash
cd omada-controller
cp .env.example .env
docker compose up -d
```

## Security Notes

- Rotate all default credentials/secrets before production.
- Keep `8091` and `8092` restricted to admin IPs.
- Prefer HTTPS in front of captive portal for production.
- Backup MySQL volume regularly.
