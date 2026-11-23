-- Run once: adds email login for the landing page.
ALTER TABLE users
  ADD COLUMN email VARCHAR(100) NULL UNIQUE AFTER username;
