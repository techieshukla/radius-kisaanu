# Ubuntu 24.04 EC2 Deployment (Full Commands)

This runbook deploys `radius-kisaanu` on Ubuntu 24.04 EC2 with:
- Captive Portal (Nginx/PHP)
- FreeRADIUS
- daloRADIUS
- phpMyAdmin
- MySQL

## 1. AWS prerequisites

Create/Use an EC2 instance:
- Ubuntu Server 24.04 LTS
- At least `t3.small` (recommended `t3.medium`)
- 20+ GB EBS

Attach Security Group inbound rules:
- `22/tcp` from your admin IP
- `80/tcp` from Wi-Fi clients / required CIDR
- `8091/tcp` from admin IP only
- `8092/tcp` from admin IP only
- `1812/udp` from Omada controller/AP subnet
- `1813/udp` from Omada controller/AP subnet

## 2. SSH into EC2

```bash
ssh -i /path/to/your-key.pem ubuntu@3.111.219.106
```

## 3. Install Docker + Compose plugin

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg lsb-release git

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo $VERSION_CODENAME) stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo usermod -aG docker ubuntu
newgrp docker

docker --version
docker compose version
```

## 4. Clone repository

```bash
cd ~
git clone https://github.com/techieshukla/radius-kisaanu.git
cd radius-kisaanu
git checkout main
```

## 5. Configure environment

```bash
cp .env.example .env
nano .env
```

Set production values in `.env` (do not keep `REPLACE_ME*` placeholders):

```env
COMPOSE_PROJECT_NAME=radius_kisaanu
TZ=Asia/Kolkata

MYSQL_ROOT_PASSWORD=<strong-root-password>
MYSQL_DATABASE=radius
MYSQL_USER=radius
MYSQL_PASSWORD=<strong-radius-db-password>

RADIUS_DB_NAME=radius
RADIUS_DB_USER=radius
RADIUS_DB_PASS=<strong-radius-db-password>
RADIUS_DB_HOST=mysql

RADIUS_SHARED_SECRET=<strong-radius-shared-secret>
RADIUS_CLIENT_NAME=omada-eap225-outdoor
RADIUS_CLIENT_IP=<OMADA_CONTROLLER_OR_AP_SUBNET>
OMADA_CONTROLLER_IP=3.111.219.106

NGINX_BIND_IP=0.0.0.0
NGINX_HTTP_PORT=8090
DALORADIUS_BIND_IP=0.0.0.0
DALORADIUS_HTTP_PORT=8091
PHPMYADMIN_BIND_IP=0.0.0.0
PHPMYADMIN_HTTP_PORT=8092
RADIUS_BIND_IP=0.0.0.0
RADIUS_AUTH_PORT=1812
RADIUS_ACCT_PORT=1813
```

Use `NGINX_HTTP_PORT=8090` by default when host NGINX is serving `80/443`.

## 6. Start deployment

```bash
docker compose up -d --build
docker compose ps
```

## 7. Initialize optional DB artifacts

For existing MySQL volume or upgrades:

```bash
./scripts/migrate-portal-registration-table.sh
./scripts/setup-daloradius-db.sh
```

## 8. Validate endpoints on EC2

```bash
curl -sSI http://127.0.0.1:${NGINX_HTTP_PORT}/wifi.php | head -n 5
curl -sSI http://127.0.0.1:${DALORADIUS_HTTP_PORT}/daloradius/app/operators/index.php | head -n 6
curl -sSI http://127.0.0.1:${PHPMYADMIN_HTTP_PORT}/ | head -n 5
```

Public/LAN URLs:
- Portal (container direct): `http://3.111.219.106:8090/wifi.php`
- daloRADIUS: `http://3.111.219.106:8091/daloradius/app/operators/index.php`
- phpMyAdmin: `http://3.111.219.106:8092`

## 9. Configure Omada

In Omada Controller:
- External RADIUS server IP: `3.111.219.106`
- Auth Port: `1812`
- Acct Port: `1813`
- Shared Secret: same as `.env` `RADIUS_SHARED_SECRET`
- Captive Portal URL:
  - `http://3.111.219.106:8090/wifi.php` (or your public domain reverse-proxy URL)
  - Include Omada query params (`target`, `clientMac`, `apMac`, `ssidName`, `radioId`)

## 10. Host Nginx reverse proxy (no public 8090)

If `8090` is blocked externally (recommended), expose everything through host Nginx on `80/443`:

```bash
sudo apt-get update
sudo apt-get install -y nginx certbot python3-certbot-nginx

sudo tee /etc/nginx/sites-available/wifi.kisaanu.com >/dev/null <<'NGINX'
server {
    listen 80;
    server_name wifi.kisaanu.com;

    location = / { return 302 /wifi.php; }
    location = /dalo { return 302 /daloradius/; }
    location = /phpmyadmin { return 302 /phpmyadmin/; }

    location ~ /\.(?!well-known).* {
        deny all;
        return 404;
    }

    location / {
        proxy_pass http://127.0.0.1:8090;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /daloradius/ {
        proxy_pass http://127.0.0.1:8091/daloradius/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /phpmyadmin/ {
        proxy_pass http://127.0.0.1:8092/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
NGINX

sudo ln -sf /etc/nginx/sites-available/wifi.kisaanu.com /etc/nginx/sites-enabled/wifi.kisaanu.com
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

sudo certbot --nginx -d wifi.kisaanu.com --redirect -m admin@kisaanu.com --agree-tos -n
```

Validate:

```bash
curl -sSI http://wifi.kisaanu.com/ | head -n 5
curl -sSI https://wifi.kisaanu.com/wifi.php | head -n 5
curl -sSI https://wifi.kisaanu.com/daloradius/ | head -n 6
curl -sSI https://wifi.kisaanu.com/phpmyadmin/ | head -n 5
```

If certbot fails with `Timeout during connect`, first ensure:
- AWS Security Group has inbound `80/tcp` open (at least during certificate issuance)
- `sudo ufw allow 80/tcp`
- host Nginx is healthy: `sudo nginx -t && sudo systemctl status nginx --no-pager`
- domain resolves to this instance: `dig +short wifi.kisaanu.com`

## 11. Firewall on EC2 OS (optional if using SG only)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 8091/tcp
sudo ufw allow 8092/tcp
sudo ufw allow 1812/udp
sudo ufw allow 1813/udp
sudo ufw --force enable
sudo ufw status
```

## 12. Update deployment (future)

```bash
cd ~/radius-kisaanu
git pull origin main
docker compose up -d --build
docker compose ps
```

## 13. Logs and troubleshooting

```bash
docker compose logs -f nginx
docker compose logs -f php
docker compose logs -f freeradius
docker compose logs -f daloradius
docker compose logs -f mysql
```

Quick checks:

```bash
docker compose ps
docker compose exec -T freeradius sh -lc "radtest demo-user demo-pass 127.0.0.1 0 ${RADIUS_SHARED_SECRET}"
```
