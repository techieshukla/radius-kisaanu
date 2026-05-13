# Expose `wifi.kisaanu.com` Without Port Using Host Nginx

Your Docker portal currently runs at:
- `http://127.0.0.1:8090/wifi.php`

If port `80` is already used on server, keep Docker on `8090` and use host Nginx reverse proxy.

## 1. Keep Docker portal on 8090

In `.env`:
```env
NGINX_HTTP_PORT=8090
```

Apply:
```bash
docker compose up -d nginx
```

## 2. Create host Nginx site config

On Ubuntu host (`/etc/nginx/sites-available/wifi.kisaanu.com`):

```nginx
server {
    listen 80;
    server_name wifi.kisaanu.com;

    location / {
        proxy_pass http://127.0.0.1:8090;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable and reload:
```bash
sudo ln -sf /etc/nginx/sites-available/wifi.kisaanu.com /etc/nginx/sites-enabled/wifi.kisaanu.com
sudo nginx -t
sudo systemctl reload nginx
```

## 3. DNS

Set A record:
- `wifi.kisaanu.com` -> `3.111.219.106`

## 4. Security group / firewall

Allow:
- `80/tcp` public
- Keep Docker `8090` not publicly required once proxy is used (can be localhost/firewalled)

## 5. Verify

```bash
curl -sSI http://wifi.kisaanu.com/wifi.php | head -n 5
```

Expected:
- `HTTP/1.1 200 OK`

## 6. Omada portal URL (no port)

Use:
- `http://wifi.kisaanu.com/wifi.php`
