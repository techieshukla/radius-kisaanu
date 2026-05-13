#!/usr/bin/env sh
set -eu

ADMIN_CIDR="${1:-}"
if [ -z "$ADMIN_CIDR" ]; then
  echo "Usage: $0 <admin-cidr>"
  echo "Example: $0 49.37.12.10/32"
  exit 1
fi

echo "Applying UFW restrictions for admin ports (8091, 8092) to CIDR: $ADMIN_CIDR"

sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 1812/udp
sudo ufw allow 1813/udp
sudo ufw allow from "$ADMIN_CIDR" to any port 8091 proto tcp
sudo ufw allow from "$ADMIN_CIDR" to any port 8092 proto tcp

sudo ufw deny 8091/tcp || true
sudo ufw deny 8092/tcp || true

sudo ufw --force enable
sudo ufw status verbose
