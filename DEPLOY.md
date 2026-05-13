# Deploy Guide (Wi-Fi / LAN Exposure)

This guide publishes the captive portal on your Wi-Fi network so users can open:
- `http://<SERVER_LAN_IP>/wifi.php`

Example:
- `http://192.168.0.50/wifi.php`

## 1. Server prerequisites
- Docker Engine + Docker Compose installed
- Static LAN IP on server machine (recommended)
- Ports open on server firewall:
  - `80/tcp` (portal)
  - `1812/udp` (RADIUS auth)
  - `1813/udp` (RADIUS accounting)
  - `8091/tcp` (daloRADIUS admin; optional, admin only)
  - `8092/tcp` (phpMyAdmin; optional, admin only)

## 2. Configure environment
Edit `.env`:

```env
NGINX_BIND_IP=0.0.0.0
NGINX_HTTP_PORT=80
RADIUS_AUTH_PORT=1812
RADIUS_ACCT_PORT=1813

RADIUS_SHARED_SECRET=<strong-secret>
RADIUS_CLIENT_NAME=omada-eap225-outdoor
RADIUS_CLIENT_IP=<omada-controller-ip-or-subnet>
OMADA_CONTROLLER_IP=<omada-controller-ip>
```

Notes:
- `NGINX_BIND_IP=0.0.0.0` exposes portal to all LAN interfaces.
- If port `80` is already occupied, use `NGINX_HTTP_PORT=8080` and update portal URL in Omada.

## 3. Start/update stack
```bash
docker compose up -d --build
```

Check:
```bash
docker compose ps
curl -sSI http://127.0.0.1:${NGINX_HTTP_PORT}/wifi.php
```

## 4. Verify from Wi-Fi client
From a phone/laptop connected to same Wi-Fi:
- Open `http://3.111.219.106:8090/wifi.php` (or your server LAN/private IP in local deployments)
- Confirm page loads.

## 5. Configure Omada (EAP225 Outdoor)
In Omada Controller:
1. Enable external RADIUS authentication.
2. Set RADIUS server:
   - Server IP: `<SERVER_LAN_IP>`
   - Auth Port: `1812`
   - Acct Port: `1813`
   - Shared Secret: same as `.env` `RADIUS_SHARED_SECRET`
3. Captive portal redirect URL:
   - `http://3.111.219.106:8090/wifi.php`
4. Ensure Omada passes query params (`target`, `clientMac`, `apMac`, `ssidName`, `radioId`) to portal URL.

## 6. Production hardening checklist
- Put portal behind HTTPS reverse proxy (Nginx/Caddy/Traefik) with valid TLS cert.
- Restrict admin UIs (`8091`, `8092`) to admin IP/VPN only.
- Rotate default seeded users and passwords immediately.
- Change MySQL/RADIUS secrets in `.env`.
- Backup MySQL volume regularly.

## 7. Quick troubleshooting
- Portal not reachable on Wi-Fi:
  - Check server LAN IP and firewall
  - `docker compose ps`
  - `curl http://127.0.0.1:${NGINX_HTTP_PORT}/wifi.php`
- Omada login accepted locally but internet not enabled:
  - Check `target` host reachability from `php` container
  - Check portal logs (`/tmp/portal.log` in `php` container)
- RADIUS auth failure:
  - Verify secret and NAS client IP/subnet
  - Test from server: `docker compose exec -T freeradius sh -lc "radtest demo-user demo-pass 127.0.0.1 0 ${RADIUS_SHARED_SECRET:-change_shared_secret}"`
