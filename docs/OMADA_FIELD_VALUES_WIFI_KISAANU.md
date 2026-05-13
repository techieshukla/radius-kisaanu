# Omada Field Values for `wifi.kisaanu.com`

Use these exact values in Omada Controller for EAP225 Outdoor captive portal and RADIUS.

## Assumptions
- Portal host: `wifi.kisaanu.com`
- Server Elastic IP: `3.111.219.106`
- Current portal port: `8090`
- RADIUS is exposed from same host

## 1. RADIUS Settings (Omada)
- Authentication Type: `External RADIUS Server`
- RADIUS Authentication Server: `3.111.219.106`
- Authentication Port: `1812`
- RADIUS Accounting Server: `3.111.219.106`
- Accounting Port: `1813`
- Shared Secret: `<same as .env RADIUS_SHARED_SECRET>`

Notes:
- Use the exact same shared secret on both sides.
- If controller and RADIUS are in same VPC/subnet, private IP can be used instead of Elastic IP.

## 2. External Portal Settings (Omada)
- Portal Type: `External Portal Server`
- Portal URL (current): `http://wifi.kisaanu.com:8090/wifi.php`
- Redirect/Landing URL after login: `https://kisaanu.com`

Future TLS target:
- `https://wifi.kisaanu.com/wifi.php` (after 443 + certificate is enabled)

## 3. Query Parameters
Ensure Omada preserves/passes portal query parameters. This backend supports:
- `target`
- `clientMac`
- `apMac`
- `ssidName`
- `radioId`
- `continueUrl` (optional)

## 4. DNS and Security Group Checklist
For `wifi.kisaanu.com`:
- DNS A record -> `3.111.219.106`

Allow inbound to EC2:
- `8090/tcp` (or `80/tcp` once moved)
- `1812/udp`
- `1813/udp`
- `8091/tcp` admin-only
- `8092/tcp` admin-only

## 5. Validation Commands
Run on server:

```bash
curl -sSI http://127.0.0.1:8090/wifi.php | head -n 5
./scripts/omada-cutover-precheck.sh
```

Public check:

```bash
curl -sSI http://wifi.kisaanu.com:8090/wifi.php | head -n 5
```

## 6. Recommended Production Move
After first rollout is stable:
1. Put portal behind HTTPS (`443`)
2. Switch Omada portal URL to `https://wifi.kisaanu.com/wifi.php`
3. Keep `8091/8092` restricted to admin CIDR only
