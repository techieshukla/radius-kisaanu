# radius-kisaanu

Dockerized FreeRADIUS + MySQL + PHP/Nginx starter for a village captive portal backend.

## Quick start
1. Copy env file:
   - `cp .env.example .env`
2. Update secrets in `.env`.
3. Start stack:
   - `docker compose up -d`
4. Verify portal bootstrap:
   - `curl http://127.0.0.1:${NGINX_HTTP_PORT:-80}`
5. Verify default RADIUS auth:
   - `docker compose exec -T freeradius sh -lc "radtest demo-user demo-pass 127.0.0.1 0 change_shared_secret"`

## Services
- MySQL: internal-only (`mysql:3306` on Docker network)
- FreeRADIUS auth: UDP `127.0.0.1:1812`
- FreeRADIUS acct: UDP `127.0.0.1:1813`
- Portal: `http://127.0.0.1:${NGINX_HTTP_PORT:-80}`

## Current status
- Phase 1 scaffold complete.
- Phase 2 baseline complete: SQL auth/accounting active, seeded plan profiles + NAS + default users, radtest validated.
- Portal login now performs local DB auth (`radcheck`) + per-day quota checks (`radacct` + `plan_profiles`) before Omada forward-auth.
- Session timeout is clamped to remaining daily seconds and forwarded to Omada as `sessionTimeout`.
- Register form supports selectable daily plans (`2h/4h/6h/8h`) sourced from active `plan_profiles`.
- Registration metadata is captured in `portal_registrations` (masked Aadhaar, address, MAC/AP/SSID, plan).

## Default seeded users
- `demo-user / demo-pass` (`FREE_2H_DAILY`)
- `village-user / VillageUser@123` (`FREE_4H_DAILY`)
- `village-admin / VillageAdmin@123` (`FREE_8H_DAILY`)

Change these credentials before production.

## Omada EAP225 Outdoor
- Integration and ports/protocol matrix:
  - [OMADA_EAP225_OUTDOOR.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/OMADA_EAP225_OUTDOOR.md)
- Wi-Fi/LAN deployment steps:
  - [DEPLOY.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/DEPLOY.md)
- Ubuntu 24.04 EC2 full-command deployment:
  - [DEPLOY_EC2_UBUNTU24.md](/Applications/XAMPP/xamppfiles/htdocs/radius-kisaanu/DEPLOY_EC2_UBUNTU24.md)

## Portal validation checks
- Success (local auth + quota):
  - `curl -sS -X POST 'http://127.0.0.1:${NGINX_HTTP_PORT:-80}/wifi.php' --data 'formMode=login&username=demo-user&password=demo-pass'`
- Wrong password rejection:
  - `curl -sS -X POST 'http://127.0.0.1:${NGINX_HTTP_PORT:-80}/wifi.php' --data 'formMode=login&username=demo-user&password=wrong-pass'`

## Unit tests
- Run backend unit tests:
  - `php portal/tests/run.php`

## MySQL data loading
- Seed files in `mysql/init/` are loaded automatically on fresh volume.
- For existing DB volumes, ensure registration table exists:
  - `./scripts/migrate-portal-registration-table.sh`
- Add sample accounting row:
  - `./scripts/load-test-data.sh`

## Logging
- Structured JSON logs are written by backend service layer.
- Default path:
  - `/tmp/portal.log` (container fallback)

## GUI management
- daloRADIUS (RADIUS users/plans/accounting):
  - URL: `http://127.0.0.1:8091` (redirects to operators UI)
  - Direct URL: `http://127.0.0.1:8091/daloradius/app/operators/index.php`
  - DB schema load (one-time or when DB is fresh): `./scripts/setup-daloradius-db.sh`
- phpMyAdmin (MySQL admin):
  - URL: `http://127.0.0.1:8092`
  - Server/host: `mysql`
  - User: `radius` (or `root`)
