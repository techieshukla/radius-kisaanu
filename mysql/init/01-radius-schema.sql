CREATE DATABASE IF NOT EXISTS radius;
USE radius;

CREATE TABLE IF NOT EXISTS radcheck (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL DEFAULT '',
  attribute VARCHAR(64) NOT NULL DEFAULT '',
  op CHAR(2) NOT NULL DEFAULT '==',
  value VARCHAR(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS radreply (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL DEFAULT '',
  attribute VARCHAR(64) NOT NULL DEFAULT '',
  op CHAR(2) NOT NULL DEFAULT '=',
  value VARCHAR(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS radgroupcheck (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  groupname VARCHAR(64) NOT NULL DEFAULT '',
  attribute VARCHAR(64) NOT NULL DEFAULT '',
  op CHAR(2) NOT NULL DEFAULT '==',
  value VARCHAR(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY groupname (groupname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS radgroupreply (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  groupname VARCHAR(64) NOT NULL DEFAULT '',
  attribute VARCHAR(64) NOT NULL DEFAULT '',
  op CHAR(2) NOT NULL DEFAULT '=',
  value VARCHAR(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY groupname (groupname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS radusergroup (
  username VARCHAR(64) NOT NULL DEFAULT '',
  groupname VARCHAR(64) NOT NULL DEFAULT '',
  priority INT(11) NOT NULL DEFAULT 1,
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS radacct (
  radacctid BIGINT(21) NOT NULL AUTO_INCREMENT,
  acctsessionid VARCHAR(64) NOT NULL DEFAULT '',
  acctuniqueid VARCHAR(32) NOT NULL DEFAULT '',
  username VARCHAR(64) NOT NULL DEFAULT '',
  realm VARCHAR(64) DEFAULT '',
  nasipaddress VARCHAR(15) NOT NULL DEFAULT '',
  nasportid VARCHAR(15) DEFAULT NULL,
  nasporttype VARCHAR(32) DEFAULT NULL,
  acctstarttime DATETIME NULL DEFAULT NULL,
  acctupdatetime DATETIME NULL DEFAULT NULL,
  acctstoptime DATETIME NULL DEFAULT NULL,
  acctsessiontime INT(12) UNSIGNED DEFAULT NULL,
  acctauthentic VARCHAR(32) DEFAULT NULL,
  connectinfo_start VARCHAR(50) DEFAULT NULL,
  connectinfo_stop VARCHAR(50) DEFAULT NULL,
  acctinputoctets BIGINT(20) DEFAULT NULL,
  acctoutputoctets BIGINT(20) DEFAULT NULL,
  calledstationid VARCHAR(50) NOT NULL DEFAULT '',
  callingstationid VARCHAR(50) NOT NULL DEFAULT '',
  acctterminatecause VARCHAR(32) NOT NULL DEFAULT '',
  servicetype VARCHAR(32) DEFAULT NULL,
  framedprotocol VARCHAR(32) DEFAULT NULL,
  framedipaddress VARCHAR(15) NOT NULL DEFAULT '',
  PRIMARY KEY (radacctid),
  UNIQUE KEY acctuniqueid (acctuniqueid),
  KEY username (username),
  KEY framedipaddress (framedipaddress),
  KEY acctsessionid (acctsessionid),
  KEY acctsessiontime (acctsessiontime),
  KEY acctstarttime (acctstarttime),
  KEY acctstoptime (acctstoptime),
  KEY nasipaddress (nasipaddress)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS radpostauth (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  pass VARCHAR(64) NOT NULL,
  reply VARCHAR(32) NOT NULL,
  authdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nas (
  id INT(10) NOT NULL AUTO_INCREMENT,
  nasname VARCHAR(128) NOT NULL,
  shortname VARCHAR(32),
  type VARCHAR(30) DEFAULT 'other',
  ports INT(5),
  secret VARCHAR(60) NOT NULL DEFAULT 'change_shared_secret',
  server VARCHAR(64),
  community VARCHAR(50),
  description VARCHAR(200),
  PRIMARY KEY (id),
  UNIQUE KEY nasname (nasname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plan_profiles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_code VARCHAR(32) NOT NULL,
  display_name VARCHAR(64) NOT NULL,
  seconds_per_day INT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY plan_code_unique (plan_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portal_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  father_name VARCHAR(150) DEFAULT '',
  mother_name VARCHAR(150) DEFAULT '',
  village VARCHAR(150) DEFAULT '',
  mobile_number VARCHAR(20) NOT NULL,
  aadhaar_number_masked VARCHAR(32) NOT NULL,
  address_text VARCHAR(500) NOT NULL,
  client_mac VARCHAR(32) DEFAULT '',
  ap_mac VARCHAR(32) DEFAULT '',
  ssid_name VARCHAR(128) DEFAULT '',
  plan_code VARCHAR(32) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_portal_reg_username (username),
  KEY idx_portal_reg_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plan_profiles (plan_code, display_name, seconds_per_day)
VALUES
  ('FREE_2H_DAILY', 'Free 2 Hours Daily', 7200),
  ('FREE_4H_DAILY', 'Free 4 Hours Daily', 14400),
  ('FREE_6H_DAILY', 'Free 6 Hours Daily', 21600),
  ('FREE_8H_DAILY', 'Free 8 Hours Daily', 28800)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), seconds_per_day = VALUES(seconds_per_day);

-- Group-level replies so time plans can be attached per user via radusergroup.
INSERT INTO radgroupreply (groupname, attribute, op, value)
VALUES
  ('FREE_2H_DAILY', 'Session-Timeout', ':=', '7200'),
  ('FREE_4H_DAILY', 'Session-Timeout', ':=', '14400'),
  ('FREE_6H_DAILY', 'Session-Timeout', ':=', '21600'),
  ('FREE_8H_DAILY', 'Session-Timeout', ':=', '28800')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Default users for initial setup and testing.
INSERT INTO radcheck (username, attribute, op, value)
VALUES
  ('demo-user', 'Cleartext-Password', ':=', 'demo-pass'),
  ('village-admin', 'Cleartext-Password', ':=', 'VillageAdmin@123'),
  ('village-user', 'Cleartext-Password', ':=', 'VillageUser@123')
ON DUPLICATE KEY UPDATE attribute = VALUES(attribute), op = VALUES(op), value = VALUES(value);

INSERT INTO radusergroup (username, groupname, priority)
VALUES
  ('demo-user', 'FREE_2H_DAILY', 1),
  ('village-admin', 'FREE_8H_DAILY', 1),
  ('village-user', 'FREE_4H_DAILY', 1);

-- Omada EAP225 Outdoor / Controller source registration (edit IP + secret for production).
INSERT INTO nas (nasname, shortname, type, ports, secret, description)
VALUES ('192.168.0.2', 'omada-eap225', 'other', 0, 'change_shared_secret', 'Omada EAP225 Outdoor / Controller source')
ON DUPLICATE KEY UPDATE secret = VALUES(secret), description = VALUES(description);
