# Captive Portal Project Plan (FreeRADIUS + PHP + MySQL via Docker)

## Execution Status (2026-05-13)
- `Phase 1` completed: project scaffold + Docker services created.
- `Phase 2` baseline completed: FreeRADIUS SQL auth running on MySQL, default users and plan profiles seeded, `radtest` returns `Access-Accept`.
- Omada integration guide added in `OMADA_EAP225_OUTDOOR.md` with required protocols and ports.

## 1. Objective
Build a production-ready, open-source captive portal backend for village free Wi‑Fi with:
- `FreeRADIUS` for AAA (Authentication, Authorization, Accounting)
- `MySQL` for users, plans, sessions, and quota/accounting data
- `PHP` web app for captive portal login, voucher/user management, and reporting
- Daily free access plans of `2`, `4`, `6`, and `8` hours per user
- Compatibility with common NAS/AP controllers (MikroTik, OpenWISP/CoovaChilli, Omada external portal flow, etc.)

## 2. Core Requirements
- Latest stable Docker images (pin major versions after validation)
- Separate containers:
  - `freeradius`
  - `mysql`
  - `php-fpm` + `nginx` (or Apache PHP image)
- Persistent volumes for DB and RADIUS config/logs
- Open-source manager UI for RADIUS users/plans (priority: `daloRADIUS`)
- Daily quota reset behavior
- Accounting + session tracking enabled
- Captive portal flow for OTP/voucher/user-pass login
- Admin UI for user management, usage reports, and plan assignment

## 3. Recommended Stack (Latest-first, then pin)
- `FreeRADIUS 3.2.x`
- `MySQL 8.4 LTS` (or latest stable 8.x supported by your manager tool)
- `PHP 8.3` + `Nginx stable`
- `daloRADIUS` (actively used OSS RADIUS web manager)

Note:
- If manager compatibility issues appear with MySQL 8.4, fallback to a compatible 8.0.x image and pin exact version.

## 4. High-Level Architecture
1. User connects to village Wi‑Fi SSID.
2. AP/Gateway redirects user to captive portal page.
3. Portal collects credentials (mobile/voucher/user+pass).
4. Portal sends Access-Request to FreeRADIUS.
5. FreeRADIUS validates against MySQL-backed radcheck/radreply tables.
6. On success, AP grants internet access.
7. Accounting Start/Interim/Stop records usage in `radacct`.
8. Quota logic enforces daily allowed usage (2/4/6/8h plans).

## 5. Quota & Policy Model (Daily Free Wi‑Fi)
Use RADIUS control attributes + SQL accounting checks:

### Plan Profiles
- `FREE_2H_DAILY` → 7200 seconds/day
- `FREE_4H_DAILY` → 14400 seconds/day
- `FREE_6H_DAILY` → 21600 seconds/day
- `FREE_8H_DAILY` → 28800 seconds/day

### Enforcement Strategy
- Primary: `Max-Daily-Session` (if NAS supports it)
- Robust SQL-based fallback:
  - Sum of `acctsessiontime` for user where `acctstarttime` is current day
  - If used >= plan limit, reject Access-Accept with friendly message
- Optional hard limits:
  - `Simultaneous-Use := 1`
  - Idle timeout / Session timeout

### Reset Window
- Reset at local midnight (`Asia/Kolkata`) using day-bounded SQL accounting query.
- Optional cron job for cached counters (if used).

## 6. Open-Source Management Options
## Primary (recommended)
- `daloRADIUS`
  - User CRUD
  - Voucher/batch creation
  - NAS/plan/profile management
  - Accounting and reporting

## Alternatives
- `RadiusDesk` (feature-rich ISP/community scenarios)
- `phpRADmin` / custom Laravel panel (if you need localized workflow)

Decision: Start with daloRADIUS first for fastest stable delivery.

## 7. Repo Deliverables
- `docker-compose.yml`
- `.env.example`
- `freeradius/`
  - `mods-enabled/sql`
  - `sites-enabled/default`
  - `sites-enabled/inner-tunnel`
  - custom policy snippets for daily quota
- `mysql/init/`
  - FreeRADIUS schema SQL
  - seed data (plans, admin, sample users)
- `portal/` (PHP app)
  - captive login endpoint
  - status endpoint
  - logout endpoint
- `manager/` (daloRADIUS integration and config)
- `nginx/` virtual host config
- `scripts/`
  - setup
  - health checks
  - test-auth
- `PLAN.md` (this document)
- `README.md` (operator runbook)

## 8. Execution Phases

### Phase 1: Foundation
- Initialize project structure and Docker network.
- Add compose services for MySQL, FreeRADIUS, PHP, Nginx.
- Add persistent volumes and secrets through env files.

### Phase 2: FreeRADIUS + SQL Integration
- Enable SQL module and MySQL connection.
- Load FreeRADIUS schema into MySQL.
- Validate PAP auth against SQL user.
- Enable accounting inserts into `radacct`.

### Phase 3: Captive Portal PHP App
- Build login page and auth endpoint.
- Integrate with FreeRADIUS (RADIUS client call or gateway flow).
- Show user-friendly responses for success/limit reached.

### Phase 4: Daily Quota Policies (2/4/6/8h)
- Create plan mapping table/profile attributes.
- Implement daily usage SQL check.
- Reject logins after daily cap is exhausted.
- Add admin actions to change user plan.

### Phase 5: daloRADIUS Integration
- Install and connect daloRADIUS to same MySQL.
- Configure roles/admin credentials.
- Expose user and accounting dashboards.

### Phase 6: NAS/AP Integration
- Configure client NAS entries in FreeRADIUS.
- Validate captive redirect + auth with target hardware.
- Verify accounting start/stop/interim packets.

### Phase 7: Hardening + Ops
- Disable default creds, enforce strong secrets.
- Restrict DB and RADIUS ports to private network.
- Add backups for MySQL volume.
- Add monitoring/log rotation and fail2ban-style protections.

### Phase 8: UAT + Go-Live
- Test matrix for all plans (2/4/6/8h).
- Validate midnight reset behavior.
- Pilot with limited users.
- Production rollout checklist.

## 9. Data Model (Minimal)
- `users` (if custom portal layer is used)
- `plan_profiles` (name, seconds_per_day, status)
- `user_plan_map` (user, plan, active_from, active_to)
- Standard FreeRADIUS tables:
  - `radcheck`
  - `radreply`
  - `radgroupcheck`
  - `radgroupreply`
  - `radusergroup`
  - `radacct`
  - `radpostauth`

## 10. Captive Portal Flows
- New user flow:
  - Register (mobile/ID) -> assign default plan -> login
- Existing user flow:
  - Login -> validate remaining daily time -> allow/deny
- Voucher flow (optional):
  - Enter voucher -> consume/activate -> allow until daily policy reached

## 11. Acceptance Criteria
- Docker stack boots cleanly with one command.
- FreeRADIUS authenticates SQL users.
- Accounting records session usage per login.
- Daily limits enforced exactly for 2/4/6/8-hour plans.
- Admin can create/manage users via open-source manager UI.
- Captive portal presents clear allow/deny reason messages.
- Restart/redeploy does not lose DB state.

## 12. Risks & Mitigations
- NAS vendor attribute differences:
  - Mitigation: profile per NAS type and test with actual hardware.
- Manager compatibility with latest DB/PHP:
  - Mitigation: pin known-good versions after first green run.
- Incorrect day-boundary accounting:
  - Mitigation: enforce timezone alignment and SQL tests around midnight.
- Abuse via multi-device sharing:
  - Mitigation: simultaneous-use limits + MAC/device binding policy.

## 13. Post-Plan Build Checklist
1. Scaffold repo directories and docker-compose.
2. Bring up MySQL and load FreeRADIUS schema.
3. Bring up FreeRADIUS and run `radtest` checks.
4. Install daloRADIUS and confirm DB connectivity.
5. Implement quota SQL policy and test all 4 plans.
6. Integrate captive portal endpoints with gateway/AP.
7. Perform pilot with real AP and users.
8. Freeze image versions and document production runbook.

---
Prepared for: Village Free Wi‑Fi Captive Portal project
Date: 2026-05-13
