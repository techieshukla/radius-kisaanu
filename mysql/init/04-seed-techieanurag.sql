USE radius;

DELETE FROM radcheck
WHERE username IN ('techieanurag@gmail.com', 'info@kisaanu.com')
  AND attribute = 'Cleartext-Password';

INSERT INTO radcheck (username, attribute, op, value)
VALUES
  ('techieanurag@gmail.com', 'Cleartext-Password', ':=', '1234567890');

DELETE FROM radusergroup WHERE username = 'techieanurag@gmail.com';
INSERT INTO radusergroup (username, groupname, priority)
VALUES ('techieanurag@gmail.com', 'FREE_8H_DAILY', 1);

DELETE FROM portal_registrations
WHERE username IN ('techieanurag@gmail.com', 'info@kisaanu.com');

INSERT INTO portal_registrations
  (username, full_name, father_name, mother_name, village, mobile_number, aadhaar_number_masked, address_text, client_mac, ap_mac, ssid_name, plan_code)
VALUES
  (
    'techieanurag@gmail.com',
    'Techie Anurag',
    'Sample Father',
    'Sample Mother',
    'Mallupur',
    '9876543210',
    'XXXXXXXX9012',
    'Sample Address, Mallupur, Uttar Pradesh',
    '',
    '',
    'MALLUPUR-KISAANU-WIFI',
    'FREE_8H_DAILY'
  ),
  (
    'info@kisaanu.com',
    'Kisaanu Admin',
    'Admin Father',
    'Admin Mother',
    'Mallupur',
    '9999999999',
    'XXXXXXXX0000',
    'Kisaanu Admin Office, Mallupur, Uttar Pradesh',
    '',
    '',
    'MALLUPUR-KISAANU-WIFI',
    'FREE_8H_DAILY'
  );

INSERT INTO radcheck (username, attribute, op, value)
VALUES
  ('info@kisaanu.com', 'Cleartext-Password', ':=', 'Kisaanu123765');

DELETE FROM radusergroup WHERE username = 'info@kisaanu.com';
INSERT INTO radusergroup (username, groupname, priority)
VALUES ('info@kisaanu.com', 'FREE_8H_DAILY', 1);

DELETE FROM userinfo
WHERE username IN ('techieanurag@gmail.com', 'info@kisaanu.com');

INSERT INTO userinfo
  (username, firstname, lastname, email, mobilephone, address, city, state, country, notes, creationdate, creationby, updatedate, updateby, enableportallogin)
VALUES
  (
    'techieanurag@gmail.com',
    'Techie',
    'Anurag',
    'techieanurag@gmail.com',
    '9876543210',
    'Sample Address, Mallupur, Uttar Pradesh',
    'Mallupur',
    'Uttar Pradesh',
    'India',
    'Seeded default Wi-Fi user',
    NOW(),
    'mysql/init/04-seed-techieanurag.sql',
    NOW(),
    'mysql/init/04-seed-techieanurag.sql',
    1
  ),
  (
    'info@kisaanu.com',
    'Kisaanu',
    'Admin',
    'info@kisaanu.com',
    '9999999999',
    'Kisaanu Admin Office, Mallupur, Uttar Pradesh',
    'Mallupur',
    'Uttar Pradesh',
    'India',
    'Seeded default Wi-Fi admin',
    NOW(),
    'mysql/init/04-seed-techieanurag.sql',
    NOW(),
    'mysql/init/04-seed-techieanurag.sql',
    1
  );
