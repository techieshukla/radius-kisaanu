# Omada ER605 + EAP225 Outdoor Execution Plan (1-8)

This document turns the 1-8 plan into executable steps using the current Docker stack.

## 1. Keep Controller Separate From AAA Stack (Done)
- Keep this repository running only:
  - Captive portal (`nginx` + `php`)
  - FreeRADIUS
  - MySQL
  - daloRADIUS
  - phpMyAdmin
- Run Omada Controller outside this compose (OC200/OC300 or separate host/compose).

Reason:
- Reduces blast radius and keeps captive + RADIUS stable if controller upgrades/restarts.

## 2. Target Topology (Implemented)
- ER605 + EAP225 managed by Omada Controller.
- Omada uses this stack for:
  - RADIUS auth/accounting (`1812/1813 UDP`)
  - External portal URL (`/wifi.php`)
- Plans and quotas remain in MySQL + FreeRADIUS policy.

## 3. Port Exposure Policy (Implemented)
Current `.env` supports interface-level bind controls:
- `NGINX_BIND_IP`, `NGINX_HTTP_PORT`
- `DALORADIUS_BIND_IP`, `DALORADIUS_HTTP_PORT`
- `PHPMYADMIN_BIND_IP`, `PHPMYADMIN_HTTP_PORT`
- `RADIUS_BIND_IP`, `RADIUS_AUTH_PORT`, `RADIUS_ACCT_PORT`

Apply:
```bash
docker compose up -d
docker compose ps
```

## 4. Omada Configuration Sequence (Ready)
In Omada Controller:
1. Adopt ER605 + EAP225 Outdoor.
2. Create Guest/Public SSID.
3. Configure RADIUS:
   - Server IP: `13.205.154.39`
   - Auth: `1812`
   - Acct: `1813`
   - Secret: `.env -> RADIUS_SHARED_SECRET`
4. Configure External Portal URL:
   - `https://wifi.kisaanu.com/wifi.php`
5. Set SSID:
   - `MALLUPUR-KISAANU-WIFI`
6. For AWS/cloud deployment, use RADIUS-only mode:
   - Keep `OMADA_TARGET_CALLBACK_ENABLED=0` in portal `.env`
   - Do not rely on private `target=192.168.x.x` callback reachability from EC2

## 5. Captive Parameter Mapping (Implemented in code)
Portal endpoint supports Omada query params:
- `target`
- `clientMac`
- `apMac` or `ap`
- `ssidName` or `ssid`
- `radioId`
- `continueUrl` or `redirect` or `origUrl`

These are already handled by `portal/public/wifi.php`.

## 6. ER605 Guest Policy/Walled Garden (Operational)
Allow pre-auth:
- DNS (`53`)
- DHCP (`67/68`)
- Portal endpoint (`http://<portal-host>/wifi.php`)

Restrict admin surfaces:
- daloRADIUS (`8091`) admin CIDR only
- phpMyAdmin (`8092`) admin CIDR only

Use script:
```bash
./scripts/secure-admin-ports-ufw.sh <ADMIN_CIDR>
```
Example:
```bash
./scripts/secure-admin-ports-ufw.sh 49.37.12.10/32
```

## 7. Daily Plan/Quota Enforcement (Implemented)
- 2/4/6/8 hour plans exist in `plan_profiles`.
- Register form allows selecting plan.
- Session quota enforced from `radacct` usage.
- Registration metadata stored in `portal_registrations`.

Validation:
```bash
php portal/tests/run.php
./scripts/migrate-portal-registration-table.sh
```

## 8. Production Hardening (Actionable)
1. Rotate `.env` secrets.
2. Restrict `8091/8092` by CIDR.
3. Use HTTPS in front of portal.
4. Keep periodic backups of MySQL volume.
5. Run health checks:

```bash
./scripts/check-captive-stack.sh
```

## Fast precheck before Omada cutover
```bash
./scripts/omada-cutover-precheck.sh
```
