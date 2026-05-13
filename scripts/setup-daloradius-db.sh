#!/bin/sh
set -eu

docker compose exec -T daloradius sh -lc "php -v >/dev/null"

docker compose exec -T daloradius sh -lc "cat /var/www/html/daloradius/contrib/db/mariadb-daloradius.sql" | \
  docker compose exec -T mysql sh -lc "mysql -uroot -p\"${MYSQL_ROOT_PASSWORD:-change_root_password}\" radius" || true

echo "daloRADIUS schema load attempted (mariadb-daloradius.sql)."
