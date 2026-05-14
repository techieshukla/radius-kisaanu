USE radius;

INSERT INTO radcheck (username, attribute, op, value)
VALUES
  ('techieanurag@gmail.com', 'Cleartext-Password', ':=', '1234567890')
ON DUPLICATE KEY UPDATE value = VALUES(value);

DELETE FROM radusergroup WHERE username = 'techieanurag@gmail.com';
INSERT INTO radusergroup (username, groupname, priority)
VALUES ('techieanurag@gmail.com', 'FREE_8H_DAILY', 1);

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
  );
