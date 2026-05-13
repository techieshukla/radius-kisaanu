# Expose `wifi.kisaanu.com` Without Port Using Host Nginx + Certbot

This is the final production flow to serve captive portal as:
- `https://wifi.kisaanu.com/wifi.php`

while Docker app stays on:
- `http://127.0.0.1:8090/wifi.php`

## 0. Context

- DNS A record already points:
  - `wifi.kisaanu.com` -> `3.111.219.106`
- Docker stack runs portal on `8090`.
- Host port `80` may already be used by host Nginx (this is expected).

## 1. Keep Docker portal on 8090

In project `.env`:
```env
NGINX_HTTP_PORT=8090
```

Apply:
```bash
docker compose up -d nginx
docker compose ps nginx
```

## 2. Install host Nginx and Certbot

```bash
sudo apt-get update
sudo apt-get install -y nginx certbot python3-certbot-nginx
```

## 3. Recommended safe config method (no sed)

Create a clean site file:

```bash
sudo tee /etc/nginx/sites-available/wifi.kisaanu.com >/dev/null <<'NGINX'
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
NGINX
```

Enable and reload:

```bash
sudo ln -sf /etc/nginx/sites-available/wifi.kisaanu.com /etc/nginx/sites-enabled/wifi.kisaanu.com
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 4. Optional sed-based method (if you prefer)

Use this only if needed. The previous error you saw:
- `"location" directive is not allowed here`
means the `location {}` block was outside `server {}`.

If that happens, overwrite with step 3 clean config.

## 5. Issue SSL certificate and redirect

```bash
sudo certbot --nginx -d wifi.kisaanu.com --redirect -m admin@kisaanu.com --agree-tos -n
```

## 6. Validate end-to-end

```bash
curl -sSI http://wifi.kisaanu.com/wifi.php | head -n 5
curl -sSI https://wifi.kisaanu.com/wifi.php | head -n 5
```

Expected:
- HTTP redirects to HTTPS
- HTTPS returns `200 OK`

## 7. Final Omada portal URL

Set in Omada:
- `https://wifi.kisaanu.com/wifi.php`

## 8. Troubleshooting

### Nginx syntax failure
Check file with line numbers:
```bash
sudo nl -ba /etc/nginx/sites-enabled/wifi.kisaanu.com | sed -n '1,200p'
```

### Verify host Nginx status
```bash
sudo systemctl status nginx --no-pager
```

### Verify docker app is reachable locally
```bash
curl -sSI http://127.0.0.1:8090/wifi.php | head -n 5
```

### Certbot renewal test
```bash
sudo certbot renew --dry-run
```
