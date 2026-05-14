# radius-kisaanu

[![CI](https://github.com/techieshukla/radius-kisaanu/actions/workflows/ci.yml/badge.svg)](https://github.com/techieshukla/radius-kisaanu/actions/workflows/ci.yml)
![Docker Compose](https://img.shields.io/badge/docker-compose-blue)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-3.x-2a7fff)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4)
![Nginx](https://img.shields.io/badge/Nginx-stable-009639)

Kisaanu Mallupur public Wi-Fi portal and enterprise RADIUS stack. The portal lets users register, log in, view SSID/login/usage information, and manage their profile. FreeRADIUS authenticates Wi-Fi clients through MySQL tables shared with the portal and daloRADIUS.

## Current Production Flow

1. User opens `https://wifi.kisaanu.com/`.
2. Landing page explains the Mallupur Wi-Fi project and shows two actions: `Register` and `Login`.
3. `Register` opens `/register`, captures user details, creates/updates the Radius username/password, assigns a Wi-Fi package, stores the profile, and redirects to `/dashboard`.
4. `Login` opens `/login`, checks the Radius username/password from MySQL, verifies daily quota, and redirects to `/dashboard`.
5. `Dashboard` shows SSID information, usage information, package, Radius username, and Radius password in plain text.
6. `Profile` shows the registration details plus Radius information.
7. Admin users see daloRADIUS and phpMyAdmin links on the dashboard.

This is not using Omada captive-portal target callback for cloud authorization. Keep `OMADA_TARGET_CALLBACK_ENABLED=0` for AWS/cloud RADIUS-only mode.

## Main URLs

| URL | Purpose |
|---|---|
| `/` | Wi-Fi project landing page |
| `/wifi.php` | Same portal entry style for compatibility |
| `/register` | Registration form |
| `/login` | Login form |
| `/dashboard` | Authenticated user dashboard |
| `/profile` | Authenticated user profile |
| `/daloradius/` | Admin RADIUS GUI |
| `/phpmyadmin/` | Admin DB GUI |

## Stack

| Layer | Component | Purpose | Port |
|---|---|---|---|
| Portal | Nginx + PHP-FPM | Landing, register, login, dashboard, profile | `${NGINX_HTTP_PORT}` |
| AAA | FreeRADIUS | RADIUS auth/accounting for Omada enterprise Wi-Fi | `${RADIUS_AUTH_PORT}/udp`, `${RADIUS_ACCT_PORT}/udp` |
| Data | MySQL 8.4 | RADIUS tables, plans, sessions, portal profiles | internal |
| RADIUS Admin | daloRADIUS | Manage RADIUS users/groups/accounting | `${DALORADIUS_HTTP_PORT}` |
| DB Admin | phpMyAdmin | Inspect and manage MySQL | `${PHPMYADMIN_HTTP_PORT}` |

## Repository Structure

| Path | Purpose |
|---|---|
| `docker-compose.yml` | Full Docker topology |
| `.env.example` | Required environment template |
| `portal/public/` | PHP web pages and shared UI/bootstrap |
| `portal/src/` | Auth service, MySQL repository, config, logging |
| `mysql/init/` | Fresh-volume schema and seed data |
| `freeradius/` | FreeRADIUS SQL/client configuration |
| `nginx/` | Portal routing and PHP FastCGI config |
| `daloradius/` | daloRADIUS image and runtime patches |
| `phpmyadmin/` | phpMyAdmin image customization |
| `scripts/` | Migration, seed, verification, and operational scripts |
| `docs/` | Omada and deployment support docs |

## Default Seeded Users

Fresh MySQL initialization seeds these users automatically from `mysql/init/`.

| Username | Password | Plan | Purpose |
|---|---|---|---|
| `info@kisaanu.com` | `Kisaanu123765` | `FREE_8H_DAILY` | Default portal admin |
| `techieanurag@gmail.com` | `1234567890` | `FREE_8H_DAILY` | Sample/test admin user |
| `demo-user` | `demo-pass` | `FREE_2H_DAILY` | RADIUS smoke test |
| `village-admin` | `VillageAdmin@123` | `FREE_8H_DAILY` | Legacy admin seed |
| `village-user` | `VillageUser@123` | `FREE_4H_DAILY` | Legacy user seed |

`info@kisaanu.com` is always treated as an admin by the portal. Add more admins with `PORTAL_ADMIN_USERS` in `.env`.

## Normal User Behavior

Normal users register from `/register`. The portal writes:

- Radius password into `radcheck` as `Cleartext-Password`.
- Radius plan/group into `radusergroup`.
- Profile details into `portal_registrations`.
- Usage is read from `radacct` accounting rows.

Omada enterprise Wi-Fi should authenticate users against this FreeRADIUS server. The same MySQL tables are visible to daloRADIUS.

## Environment Mapping

Copy `.env.example` to `.env` on the server and set real values.

```bash
cp .env.example .env
nano .env
```

Required DB values:

| Variable | Meaning |
|---|---|
| `MYSQL_ROOT_PASSWORD` | MySQL root password used by scripts and container healthcheck |
| `MYSQL_DATABASE` | MySQL database, normally `radius` |
| `MYSQL_USER` | MySQL app user created by MySQL image |
| `MYSQL_PASSWORD` | Password for `MYSQL_USER` |
| `RADIUS_DB_NAME` | DB name used by PHP, FreeRADIUS, daloRADIUS |
| `RADIUS_DB_USER` | DB user used by PHP, FreeRADIUS, daloRADIUS |
| `RADIUS_DB_PASS` | DB password used by PHP, FreeRADIUS, daloRADIUS |
| `RADIUS_DB_HOST` | DB hostname inside Docker, normally `mysql` |

Required RADIUS/Omada values:

| Variable | Meaning |
|---|---|
| `RADIUS_SHARED_SECRET` | Shared secret configured in Omada and FreeRADIUS |
| `RADIUS_CLIENT_NAME` | Friendly RADIUS client name |
| `RADIUS_CLIENT_IP` | Omada controller/AP source subnet or IP allowed by FreeRADIUS |
| `OMADA_CONTROLLER_IP` | Omada controller IP for documentation/precheck context |
| `OMADA_TARGET_CALLBACK_ENABLED` | Keep `0` for cloud-safe RADIUS-only mode |

Exposed service values:

| Variable | Meaning |
|---|---|
| `NGINX_BIND_IP`, `NGINX_HTTP_PORT` | Portal bind IP and HTTP port |
| `RADIUS_BIND_IP`, `RADIUS_AUTH_PORT`, `RADIUS_ACCT_PORT` | RADIUS UDP bind and ports |
| `DALORADIUS_BIND_IP`, `DALORADIUS_HTTP_PORT` | daloRADIUS bind and port |
| `PHPMYADMIN_BIND_IP`, `PHPMYADMIN_HTTP_PORT` | phpMyAdmin bind and port |
| `MYSQL_VOLUME_NAME` | Persistent Docker volume name |
| `PORTAL_ADMIN_USERS` | Optional comma-separated extra admins. `info@kisaanu.com` is always admin. |

## Server Deploy Commands

Use these commands after pushing changes to GitHub.

```bash
cd ~/radius-kisaanu
git pull origin main

chmod +x scripts/*.sh

docker compose --env-file .env up -d --build mysql
./scripts/sync-mysql-env-users.sh
./scripts/migrate-portal-registration-table.sh
./scripts/setup-daloradius-db.sh
./scripts/seed-default-admin-user.sh

docker compose --env-file .env up -d --build php nginx freeradius daloradius phpmyadmin
./scripts/verify-portal-dalo-sync.sh
```

If the MySQL volume is newly created or force deleted, the files in `mysql/init/` run automatically on first MySQL startup and seed the default admin. If the MySQL volume already exists, run `./scripts/seed-default-admin-user.sh` to add or repair the admin user.

## MySQL Credential Sync

If `.env` passwords changed but the existing MySQL volume still has old users, run:

```bash
cd ~/radius-kisaanu
FORCE=1 ./scripts/sync-mysql-env-users.sh
```

This rewrites MySQL users to match `.env` without deleting data.

If you know the current root password and do not want force mode:

```bash
MYSQL_CURRENT_ROOT_PASSWORD='old-password-here' ./scripts/sync-mysql-env-users.sh
```

## Seed Commands

Seed or repair the default admin:

```bash
./scripts/seed-default-admin-user.sh
```

Seed or repair the sample techie user:

```bash
./scripts/seed-techieanurag-user.sh
```

Use another env file:

```bash
ENV_FILE=.env.local ./scripts/seed-default-admin-user.sh
```

Override seed values when needed:

```bash
SEED_USERNAME='user@example.com' \
SEED_PASSWORD='StrongPassword123' \
SEED_PLAN='FREE_2H_DAILY' \
SEED_FULL_NAME='User Name' \
./scripts/seed-techieanurag-user.sh
```

## Verification Commands

Check containers:

```bash
docker compose --env-file .env ps
```

Check portal routes locally from the server:

```bash
curl -sSI http://127.0.0.1:${NGINX_HTTP_PORT:-8090}/
curl -sSI http://127.0.0.1:${NGINX_HTTP_PORT:-8090}/register
curl -sSI http://127.0.0.1:${NGINX_HTTP_PORT:-8090}/login
curl -sSI http://127.0.0.1:${NGINX_HTTP_PORT:-8090}/wifi.php
```

Verify admin exists in Radius tables:

```bash
docker compose --env-file .env exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" radius -e "SELECT username, attribute, value FROM radcheck WHERE username='info@kisaanu.com';"
docker compose --env-file .env exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" radius -e "SELECT username, groupname, priority FROM radusergroup WHERE username='info@kisaanu.com';"
docker compose --env-file .env exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" radius -e "SELECT username, full_name, village, ssid_name, plan_code FROM portal_registrations WHERE username='info@kisaanu.com' ORDER BY id DESC LIMIT 1;"
```

Verify FreeRADIUS accepts admin credentials:

```bash
docker compose --env-file .env exec -T freeradius sh -lc 'radtest info@kisaanu.com Kisaanu123765 127.0.0.1 0 "$RADIUS_SHARED_SECRET"'
```

Expected result includes:

```text
Received Access-Accept
Session-Timeout = 28800
```

Run portal unit tests locally:

```bash
php portal/tests/run.php
```

Run stack health checks:

```bash
./scripts/check-captive-stack.sh
./scripts/omada-cutover-precheck.sh
./scripts/verify-portal-dalo-sync.sh
```

## Omada EAP225 / Controller Values

Use these production values in Omada:

| Setting | Value |
|---|---|
| SSID | `MALLUPUR-KISAANU-WIFI` |
| RADIUS server IP | `13.205.154.39` |
| RADIUS auth port | `1812` |
| RADIUS accounting port | `1813` |
| RADIUS shared secret | `.env -> RADIUS_SHARED_SECRET` |
| Portal domain | `https://wifi.kisaanu.com/` |
| Register | `https://wifi.kisaanu.com/register` |
| Login | `https://wifi.kisaanu.com/login` |

For this flow, users manually register/login on the website and then use Radius credentials in the Wi-Fi enterprise login prompt. Do not depend on private callback targets such as `192.168.0.100:22080` from AWS.

## daloRADIUS and phpMyAdmin

Admin URLs on the Wi-Fi domain:

- `https://wifi.kisaanu.com/daloradius/`
- `https://wifi.kisaanu.com/phpmyadmin/`

Only admin users should see these links in the dashboard. Restrict direct access with firewall or reverse-proxy rules where possible.

## One-Command Deploy

`run.sh` can be used for a fuller guided deploy:

```bash
./run.sh
```

It validates tooling, pulls latest code, runs tests, deploys Docker services, runs DB tasks, and performs post-deploy checks. Use manual commands above when you want precise control over each step.

## Troubleshooting

MySQL denies root password:

```bash
FORCE=1 ./scripts/sync-mysql-env-users.sh
```

Seed script missing or old on server:

```bash
git pull origin main
ls -l scripts/seed-default-admin-user.sh
chmod +x scripts/seed-default-admin-user.sh
```

Portal code updated but UI still old:

```bash
docker compose --env-file .env up -d --build php nginx
```

FreeRADIUS not accepting a known user:

```bash
docker compose --env-file .env logs --tail 120 freeradius
./scripts/seed-default-admin-user.sh
docker compose --env-file .env restart freeradius
```

Check whether duplicate password rows exist:

```bash
docker compose --env-file .env exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" radius -e "SELECT username, attribute, value, COUNT(*) AS rows_found FROM radcheck WHERE username='info@kisaanu.com' AND attribute='Cleartext-Password' GROUP BY username, attribute, value;"
```

The current seed scripts delete and reinsert the password row so the result should be one row.

## Docker Hygiene

Safe dangling-image cleanup:

```bash
docker images -f dangling=true
docker image prune -f
docker system df
```

Do not delete the MySQL volume unless you intentionally want a clean database. If you do delete it, fresh init will recreate schema and seed defaults.

## Security Notes

- Rotate default passwords before production if the deployment is public.
- Restrict `daloradius` and `phpmyadmin` ports to trusted admin IPs.
- Keep `RADIUS_SHARED_SECRET` strong and matched with Omada.
- Back up the MySQL Docker volume regularly.
- Keep `OMADA_TARGET_CALLBACK_ENABLED=0` for AWS/cloud RADIUS-only mode.
