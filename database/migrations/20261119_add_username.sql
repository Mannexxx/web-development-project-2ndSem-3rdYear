-- Run once on databases created before username authentication was added.
ALTER TABLE users
  ADD COLUMN username VARCHAR(40) NULL AFTER lastname,
  ADD UNIQUE KEY uq_users_username (username);

UPDATE users
SET username = CONCAT('member', id)
WHERE username IS NULL OR TRIM(username) = '';
