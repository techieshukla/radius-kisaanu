# Expose `wifi.kisaanu.com` Without Port Using Host Nginx + Certbot

This is the final production flow to serve all UIs on one domain:
- `https://wifi.kisaanu.com/`
- `https://wifi.kisaanu.com/daloradius/`
- `https://wifi.kisaanu.com/phpmyadmin/`

while Docker app stays on:
- `http://127.0.0.1:8090/`

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

## 3. Recommended config method

Use the repo-managed installer. It supports both HTTP-only first setup and existing Let's Encrypt certificates:

```bash
cd ~/radius-kisaanu
git pull origin main
sudo ./scripts/install-wifi-domain-nginx.sh
```

This writes `/etc/nginx/sites-available/wifi.kisaanu.com`, enables it, removes the default site, validates with `nginx -t`, and reloads Nginx.

If certificates do not exist yet, issue them after the HTTP config is installed:

```bash
sudo certbot --nginx -d wifi.kisaanu.com --redirect -m admin@kisaanu.com --agree-tos -n
sudo ./scripts/install-wifi-domain-nginx.sh
```

The second installer run writes the HTTPS server block with the certificate paths.

Create challenge webroot:

```bash
sudo mkdir -p /var/www/certbot/.well-known/acme-challenge
echo ok | sudo tee /var/www/certbot/.well-known/acme-challenge/test >/dev/null
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
curl -sSI http://wifi.kisaanu.com/ | head -n 5
curl -sSI https://wifi.kisaanu.com/ | head -n 5
curl -sSI https://wifi.kisaanu.com/login | head -n 5
curl -sSI https://wifi.kisaanu.com/register | head -n 5
curl -sSI https://wifi.kisaanu.com/daloradius/ | head -n 6
curl -sSI https://wifi.kisaanu.com/phpmyadmin/ | head -n 5
```

Expected:
- HTTP redirects to HTTPS
- HTTPS returns `200 OK`

## 7. Final Omada portal URL

Set in Omada:
- `https://wifi.kisaanu.com/`

Admin URLs:
- `https://wifi.kisaanu.com/daloradius/`
- `https://wifi.kisaanu.com/phpmyadmin/`

## 8. Troubleshooting

### Certbot error: Timeout during connect (http-01)
Run this exact checklist on EC2:

1. Confirm DNS resolves to this host:
```bash
dig +short wifi.kisaanu.com
curl -4s ifconfig.me
```

2. Ensure host Nginx is bound on public port 80:
```bash
sudo ss -ltnp '( sport = :80 )'
sudo nginx -t
sudo systemctl status nginx --no-pager
```

3. Allow inbound `80/tcp` at OS firewall:
```bash
sudo ufw allow 80/tcp
sudo ufw status
```

4. Ensure AWS Security Group allows `80/tcp` from `0.0.0.0/0` temporarily for issuance.

5. Test public reachability from outside:
```bash
curl -sSI http://wifi.kisaanu.com/.well-known/acme-challenge/test | head -n 5
curl -sSI http://wifi.kisaanu.com/ | head -n 5
```

6. Retry certbot with debug:
```bash
sudo certbot --nginx -d wifi.kisaanu.com --redirect -m admin@kisaanu.com --agree-tos -n -v
```

If it still fails, inspect:
```bash
sudo tail -n 120 /var/log/letsencrypt/letsencrypt.log
```

### Deny rule blocks ACME path
If your config has `location ~ /\.` deny rules, keep ACME allow-rule above it:

```nginx
location ^~ /.well-known/acme-challenge/ {
    root /var/www/certbot;
    try_files $uri =404;
}

location ~ /\.(?!well-known).* {
    deny all;
    return 404;
}
```

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
curl -sSI http://127.0.0.1:8090/ | head -n 5
curl -sSI http://127.0.0.1:8091/daloradius/app/operators/index.php | head -n 6
curl -sSI http://127.0.0.1:8092/ | head -n 5
```

### Certbot renewal test
```bash
sudo certbot renew --dry-run
```
