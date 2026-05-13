#!/bin/sh
set -eu

CONF_FILE="/var/www/html/daloradius/app/common/includes/daloradius.conf.php"
SAMPLE_FILE="/var/www/html/daloradius/app/common/includes/daloradius.conf.php.sample"
VAR_DIR="/var/www/html/daloradius/var"
LOG_DIR="${VAR_DIR}/log"

mkdir -p "$LOG_DIR"
chown -R www-data:www-data "$VAR_DIR"

if [ ! -f "$CONF_FILE" ] && [ -f "$SAMPLE_FILE" ]; then
  cp "$SAMPLE_FILE" "$CONF_FILE"
fi

if [ -f "$CONF_FILE" ]; then
  chown www-data:www-data "$CONF_FILE"
  chmod 664 "$CONF_FILE"
fi

chown -R www-data:www-data /var/www/html/daloradius/app/common/includes

if [ -f "$CONF_FILE" ]; then
  sed -i "s#\\(\\['CONFIG_DB_HOST'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${DB_HOST:-mysql}'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_DB_PORT'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${DB_PORT:-3306}'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_DB_USER'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${DB_USER:-radius}'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_DB_PASS'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${DB_PASS:-change_radius_password}'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_DB_NAME'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${DB_NAME:-radius}'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_PATH_DALO_VARIABLE_DATA'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${VAR_DIR}'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_PATH_DALO_TEMPLATES_DIR'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'/var/www/html/daloradius/app/common/templates'#" "$CONF_FILE"
  sed -i "s#\\(\\['CONFIG_LOG_FILE'\\][[:space:]]*=[[:space:]]*\\)'[^']*'#\\1'${LOG_DIR}/daloradius.log'#" "$CONF_FILE"
fi

exec apache2-foreground
