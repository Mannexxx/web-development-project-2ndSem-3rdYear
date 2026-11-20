ALTER TABLE users
  MODIFY status ENUM('active','restricted','deactivated') NOT NULL DEFAULT 'active';
