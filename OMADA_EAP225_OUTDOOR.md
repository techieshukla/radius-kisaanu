# Omada EAP225 Outdoor Integration (Protocols and Ports)

This project uses FreeRADIUS + MySQL + PHP portal. For **Omada EAP225 Outdoor**, configure the following network paths.

## 1. RADIUS (this stack)
- `UDP 1812` (Auth): Omada AP/Controller -> FreeRADIUS
- `UDP 1813` (Accounting): Omada AP/Controller -> FreeRADIUS
- `UDP 3799` (CoA, optional): FreeRADIUS -> Omada for session disconnect/change-of-authorization

In this repo:
- Auth is mapped with `${RADIUS_AUTH_PORT}` (default `1812`)
- Acct is mapped with `${RADIUS_ACCT_PORT}` (default `1813`)

## 2. Captive Portal
- `TCP 80` (HTTP portal)
- `TCP 443` (HTTPS portal, recommended in production)

In this repo:
- Portal is exposed via Nginx on `${NGINX_HTTP_PORT}` (default `8080`)
- For production captive portal, place reverse proxy/TLS in front and publish on `80/443`.

## 3. Omada Controller / AP Management Ports
Use these when your controller/AP are across VLANs/subnets and firewalls are present.

- `UDP 29810`: EAP discovery
- `TCP 29811`: EAP management
- `TCP 29812`: EAP adoption
- `TCP 29813`: EAP upgrade
- `TCP 8043`: Omada Software Controller HTTPS management
- `TCP 8088`: Omada Software Controller HTTP management
- `TCP 8843`: Omada portal HTTPS
- `TCP 8880`: Omada portal HTTP

## 4. Basic Network Services Required for Portal Clients
- `UDP/TCP 53`: DNS
- `UDP 67/68`: DHCP
- `UDP 123`: NTP (recommended)

## 5. Minimum Firewall Policy (Recommended)
Allow only trusted source addresses:
- Omada controller/AP subnet -> FreeRADIUS `UDP 1812/1813`
- FreeRADIUS -> Omada controller/AP `UDP 3799` (only if CoA enabled)
- Client VLAN -> Portal `TCP 80/443` (or your published ports)

## 6. Default User Seeds in This Project
The bootstrap SQL seeds these users:
- `demo-user / demo-pass` -> `FREE_2H_DAILY`
- `village-user / VillageUser@123` -> `FREE_4H_DAILY`
- `village-admin / VillageAdmin@123` -> `FREE_8H_DAILY`

Update passwords before production.

## 7. Important Notes
- EAP225 Outdoor can work in different portal modes; configure Omada Authentication type to External RADIUS where required.
- If using Omada's built-in portal redirection ports (`8880/8843`), ensure it can reach this PHP portal endpoint.
- Keep AP/controller time synced (NTP), otherwise daily quota behavior around midnight can be inconsistent.
