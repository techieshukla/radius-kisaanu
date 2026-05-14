#!/usr/bin/env sh
set -eu

DOMAIN="${WIFI_DOMAIN:-wifi.kisaanu.com}"
PORTAL_UPSTREAM="${PORTAL_UPSTREAM:-http://127.0.0.1:8090}"
DALO_UPSTREAM="${DALO_UPSTREAM:-http://127.0.0.1:8091}"
PHPMYADMIN_UPSTREAM="${PHPMYADMIN_UPSTREAM:-http://127.0.0.1:8092}"
CERT_DIR="/etc/letsencrypt/live/${DOMAIN}"
SITE_AVAILABLE="/etc/nginx/sites-available/${DOMAIN}"
SITE_ENABLED="/etc/nginx/sites-enabled/${DOMAIN}"

if [ "$(id -u)" -ne 0 ]; then
  echo "ERROR: run with sudo: sudo $0"
  exit 1
fi

write_common_locations() {
  cat <<NGINX
    location = /dalo {
        return 302 /daloradius/app/operators/index.php;
    }

    location = /phpmyadmin {
        valid_referers ${DOMAIN};
        if (\$invalid_referer) {
            return 403;
        }
        return 302 /phpmyadmin/;
    }

    location ~ /\.(?!well-known).* {
        deny all;
        return 404;
    }

    location = /daloradius/ {
        return 302 /daloradius/app/operators/index.php;
    }

    location /daloradius/ {
        proxy_pass ${DALO_UPSTREAM}/daloradius/;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location /phpmyadmin/ {
        valid_referers ${DOMAIN};
        if (\$invalid_referer) {
            return 403;
        }
        proxy_pass ${PHPMYADMIN_UPSTREAM}/;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location / {
        proxy_pass ${PORTAL_UPSTREAM};
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
NGINX
}

mkdir -p /var/www/certbot/.well-known/acme-challenge

if [ -f "${CERT_DIR}/fullchain.pem" ] && [ -f "${CERT_DIR}/privkey.pem" ]; then
  {
    cat <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/certbot;
        default_type text/plain;
        try_files \$uri =404;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name ${DOMAIN};

    ssl_certificate ${CERT_DIR}/fullchain.pem;
    ssl_certificate_key ${CERT_DIR}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

NGINX
    write_common_locations
    cat <<'NGINX'
}
NGINX
  } > "${SITE_AVAILABLE}"
else
  {
    cat <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/certbot;
        default_type text/plain;
        try_files \$uri =404;
    }

NGINX
    write_common_locations
    cat <<'NGINX'
}
NGINX
  } > "${SITE_AVAILABLE}"
fi

ln -sf "${SITE_AVAILABLE}" "${SITE_ENABLED}"
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "Installed ${DOMAIN} host Nginx proxy. Validate:"
echo "  curl -sSI https://${DOMAIN}/"
echo "  curl -sSI https://${DOMAIN}/login"
echo "  curl -sSI https://${DOMAIN}/register"
