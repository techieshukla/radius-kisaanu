#!/bin/sh
set -eu

docker compose exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-change_root_password}" radius <<'SQL'
INSERT INTO radacct (
  acctsessionid,
  acctuniqueid,
  username,
  nasipaddress,
  acctstarttime,
  acctstoptime,
  acctsessiontime,
  calledstationid,
  callingstationid,
  acctterminatecause,
  framedipaddress
) VALUES (
  CONCAT('sess-', UNIX_TIMESTAMP()),
  MD5(CONCAT('demo-user', UNIX_TIMESTAMP())),
  'demo-user',
  '192.168.0.2',
  UTC_TIMESTAMP(),
  UTC_TIMESTAMP(),
  600,
  'KISAANU-SSID',
  'AA-BB-CC-DD-EE-FF',
  'User-Request',
  '10.0.0.10'
);
SQL

echo "Inserted sample radacct row for demo-user"
