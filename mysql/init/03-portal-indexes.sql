USE radius;

ALTER TABLE radacct
  ADD INDEX IF NOT EXISTS idx_radacct_user_start (username, acctstarttime),
  ADD INDEX IF NOT EXISTS idx_radacct_user_session (username, acctsessiontime);
