#!/bin/sh
set -eu

cp /tmp/clients.conf.template /etc/freeradius/3.0/clients.conf

sed -i "s|__RADIUS_CLIENT_NAME__|${RADIUS_CLIENT_NAME}|g" /etc/freeradius/3.0/clients.conf
sed -i "s|__RADIUS_CLIENT_IP__|${RADIUS_CLIENT_IP}|g" /etc/freeradius/3.0/clients.conf
sed -i "s|__RADIUS_SHARED_SECRET__|${RADIUS_SHARED_SECRET}|g" /etc/freeradius/3.0/clients.conf

# Keep the distro default sql module (it includes all expected table/query vars),
# then patch only connection values for this deployment.
sed -i 's/^[[:space:]]*driver = .*/\tdriver = "rlm_sql_mysql"/' /etc/freeradius/3.0/mods-available/sql
sed -i 's/^[[:space:]]*dialect = .*/\tdialect = "mysql"/' /etc/freeradius/3.0/mods-available/sql
sed -i "s/^[[:space:]]*server = .*/\tserver = \"${RADIUS_DB_HOST}\"/" /etc/freeradius/3.0/mods-available/sql
sed -i "s/^[[:space:]]*port = .*/\tport = 3306/" /etc/freeradius/3.0/mods-available/sql
sed -i "s/^[[:space:]]*login = .*/\tlogin = \"${RADIUS_DB_USER}\"/" /etc/freeradius/3.0/mods-available/sql
sed -i "s/^[[:space:]]*password = .*/\tpassword = \"${RADIUS_DB_PASS}\"/" /etc/freeradius/3.0/mods-available/sql
sed -i "s/^[[:space:]]*radius_db = .*/\tradius_db = \"${RADIUS_DB_NAME}\"/" /etc/freeradius/3.0/mods-available/sql
sed -i "s|^[[:space:]]*#[[:space:]]*server = .*|\tserver = \"${RADIUS_DB_HOST}\"|" /etc/freeradius/3.0/mods-available/sql
sed -i "s|^[[:space:]]*#[[:space:]]*port = .*|\tport = 3306|" /etc/freeradius/3.0/mods-available/sql
sed -i "s|^[[:space:]]*#[[:space:]]*login = .*|\tlogin = \"${RADIUS_DB_USER}\"|" /etc/freeradius/3.0/mods-available/sql
sed -i "s|^[[:space:]]*#[[:space:]]*password = .*|\tpassword = \"${RADIUS_DB_PASS}\"|" /etc/freeradius/3.0/mods-available/sql
sed -i 's|^[[:space:]]*ca_file = .*|# ca_file disabled for local Docker mysql|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|^[[:space:]]*ca_path = .*|# ca_path disabled for local Docker mysql|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|^[[:space:]]*certificate_file = .*|# certificate_file disabled for local Docker mysql|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|^[[:space:]]*private_key_file = .*|# private_key_file disabled for local Docker mysql|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|^[[:space:]]*tls_required = .*|\ttls_required = no|' /etc/freeradius/3.0/mods-available/sql

if [ ! -L /etc/freeradius/3.0/mods-enabled/sql ]; then
  ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
fi

exec freeradius -f -l stdout
