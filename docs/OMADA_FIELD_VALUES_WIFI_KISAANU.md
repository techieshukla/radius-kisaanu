# Omada Field Values for `wifi.kisaanu.com`

Use these exact values in Omada Controller for EAP225 Outdoor captive portal and RADIUS.

## Assumptions
- Portal host: `wifi.kisaanu.com`
- Server Elastic IP: `13.205.154.39`
- SSID: `MALLUPUR-KISAANU-WIFI`
- External Nginx serves `wifi.kisaanu.com` on `80/443`
- Internal Docker services remain on `8090/8091/8092`
- RADIUS is exposed from same host

## 1. RADIUS Settings (Omada)
- Authentication Type: `External RADIUS Server`
- RADIUS Authentication Server: `13.205.154.39`
- Authentication Port: `1812`
- RADIUS Accounting Server: `13.205.154.39`
- Accounting Port: `1813`
- Shared Secret: `<same as .env RADIUS_SHARED_SECRET>`

Notes:
- Use the exact same shared secret on both sides.
- If controller and RADIUS are in same VPC/subnet, private IP can be used instead of Elastic IP.

## 2. External Portal Settings (Omada)
- Portal Type: `External Portal Server`
- Portal URL (final): `https://wifi.kisaanu.com/`
- Redirect/Landing URL after login: `https://kisaanu.com`

RADIUS-only mode note for cloud deployments:
- Set `OMADA_TARGET_CALLBACK_ENABLED=0` in portal `.env` so login does not depend on private `target` callback reachability.
- This is required when Omada `target` points to LAN/private IP (for example `192.168.x.x`) and portal is hosted on AWS.

## 3. Query Parameters
Ensure Omada preserves/passes portal query parameters. This backend supports:
- `target`
- `clientMac`
- `apMac` or `ap`
- `ssidName` or `ssid`
- `radioId`
- `continueUrl` or `redirect` or `origUrl` (optional)

## 4. DNS and Security Group Checklist
For `wifi.kisaanu.com`:
- DNS A record -> `13.205.154.39`

Allow inbound to EC2:
- `80/tcp`
- `443/tcp`
- `1812/udp`
- `1813/udp`
- `8091/tcp` and `8092/tcp` can be closed publicly when proxied internally via host nginx

## 5. Validation Commands
Run on server:

```bash
curl -sSI http://127.0.0.1:8090/ | head -n 5
curl -sSI http://127.0.0.1:8091/daloradius/app/operators/index.php | head -n 6
curl -sSI http://127.0.0.1:8092/ | head -n 5
./scripts/omada-cutover-precheck.sh
```

Public check:

```bash
curl -sSI https://wifi.kisaanu.com/ | head -n 5
curl -sSI https://wifi.kisaanu.com/daloradius/ | head -n 6
curl -sSI https://wifi.kisaanu.com/phpmyadmin/ | head -n 5
```

## 6. Admin URLs (same domain)
- daloRADIUS: `https://wifi.kisaanu.com/daloradius/`
- phpMyAdmin: `https://wifi.kisaanu.com/phpmyadmin/`

Recommended:
- protect both with IP allowlist and/or basic auth in host nginx.
