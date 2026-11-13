-- =====================================================================
--  BANTAYAN MARKETPLACE  -  database schema
--  Target : MySQL 8 / MariaDB 10.4+  (XAMPP)      Engine : InnoDB
--  Charset: utf8mb4 / utf8mb4_unicode_ci
--
--  HOW TO IMPORT (XAMPP)
--    1. Start Apache + MySQL in the XAMPP Control Panel.
--    2. Open http://localhost/phpmyadmin  ->  "Import"  ->  choose this file  ->  Go.
--       (the script creates the database `bantayan_market` for you)
--    3. OPTIONAL: import database/sample_data.sql afterwards for demo content.
--
--  The script is safe to run more than once: tables use IF NOT EXISTS and
--  seed rows use INSERT IGNORE, so existing data is never overwritten.
--
--  TABLE MAP
--    People & trust : towns, users, face_verifications
--    Catalogue      : categories, listings, listing_photos, listing_moderations
--    Buying         : conversations, messages, deals, reviews, product_views
--    Safety         : guidelines, reports, report_attachments
--    Support        : support_tickets, support_messages
--    System         : notifications, admin_actions, login_attempts
--    View           : v_user_ratings (average rating per user and role)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `bantayan_market`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `bantayan_market`;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  1. PEOPLE & TRUST
-- ---------------------------------------------------------------------

-- Towns on Bantayan Island (used for registration, pickup location, filters)
CREATE TABLE IF NOT EXISTS towns (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(60)  NOT NULL,
  slug          VARCHAR(60)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_towns_name (name),
  UNIQUE KEY uq_towns_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every account: buyers, sellers and admins
CREATE TABLE IF NOT EXISTS users (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  firstname          VARCHAR(80)  NOT NULL,
  lastname           VARCHAR(80)  NOT NULL,
  email              VARCHAR(190) NOT NULL,
  password_hash      VARCHAR(255) NOT NULL,
  town_id            INT UNSIGNED NOT NULL,
  role               ENUM('member','admin')      NOT NULL DEFAULT 'member',
  status             ENUM('active','restricted') NOT NULL DEFAULT 'active',
  violation_count    INT UNSIGNED NOT NULL DEFAULT 0,
  profile_pic        VARCHAR(255) NULL,
  terms_accepted_at  DATETIME     NOT NULL,
  face_verified_at   DATETIME     NULL,
  last_login_at      DATETIME     NULL,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_town (town_id),
  CONSTRAINT fk_users_town FOREIGN KEY (town_id) REFERENCES towns (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per face-verification attempt (the latest success sets users.face_verified_at)
CREATE TABLE IF NOT EXISTS face_verifications (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  status           ENUM('verified','failed') NOT NULL,
  consent_at       DATETIME     NOT NULL,
  reference_image  VARCHAR(255) NULL,
  failure_reason   VARCHAR(255) NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_face_user (user_id, created_at),
  CONSTRAINT fk_face_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  2. CATALOGUE
-- ---------------------------------------------------------------------

-- Two levels: top-level (Electronics) and sub-category (Mobile phones)
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id   INT UNSIGNED NULL,
  name        VARCHAR(80)  NOT NULL,
  slug        VARCHAR(80)  NOT NULL,
  sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_parent (parent_id),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A product for sale. Lifecycle:
--   draft -> pending -> approved -> sold
--                   \-> changes_requested -> pending ...
--                   \-> rejected          (admin decision)
--   approved -> removed                   (after a community report)
CREATE TABLE IF NOT EXISTS listings (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  seller_id        INT UNSIGNED NOT NULL,
  category_id      INT UNSIGNED NULL,
  town_id          INT UNSIGNED NOT NULL,
  title            VARCHAR(150) NOT NULL,
  description      TEXT         NOT NULL,
  price            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  item_condition   ENUM('new','like_new','used') NULL,
  status           ENUM('draft','pending','changes_requested','approved','rejected','removed','sold')
                   NOT NULL DEFAULT 'draft',
  submitted_at     DATETIME NULL,
  published_at     DATETIME NULL,
  sold_at          DATETIME NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_listings_status_published (status, published_at),
  KEY idx_listings_category (category_id, status),
  KEY idx_listings_town (town_id),
  KEY idx_listings_seller (seller_id, status),
  KEY idx_listings_price (price),
  CONSTRAINT chk_listings_price CHECK (price >= 0),
  CONSTRAINT fk_listings_seller   FOREIGN KEY (seller_id)   REFERENCES users (id),
  CONSTRAINT fk_listings_category FOREIGN KEY (category_id) REFERENCES categories (id),
  CONSTRAINT fk_listings_town     FOREIGN KEY (town_id)     REFERENCES towns (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Up to 8 photos per listing; the lowest sort_order is the cover photo
CREATE TABLE IF NOT EXISTS listing_photos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id  INT UNSIGNED NOT NULL,
  file_path   VARCHAR(255) NOT NULL,
  sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_photos_listing (listing_id, sort_order),
  CONSTRAINT fk_photos_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin moderation history for each listing (the 3 checklist boxes + decision note)
CREATE TABLE IF NOT EXISTS listing_moderations (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id       INT UNSIGNED NOT NULL,
  admin_id         INT UNSIGNED NOT NULL,
  decision         ENUM('approved','changes_requested','rejected') NOT NULL,
  original_photos  TINYINT(1)   NOT NULL DEFAULT 0,
  accurate_info    TINYINT(1)   NOT NULL DEFAULT 0,
  permitted_item   TINYINT(1)   NOT NULL DEFAULT 0,
  note             VARCHAR(1000) NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_moderation_listing (listing_id, created_at),
  CONSTRAINT fk_moderation_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE,
  CONSTRAINT fk_moderation_admin   FOREIGN KEY (admin_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  3. BUYING: messages, completed deals, ratings, browsing history
-- ---------------------------------------------------------------------

-- One conversation per (listing, buyer). The seller is the listing's seller.
CREATE TABLE IF NOT EXISTS conversations (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id       INT UNSIGNED NOT NULL,
  buyer_id         INT UNSIGNED NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_message_at  DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_conversation (listing_id, buyer_id),
  KEY idx_conversations_buyer (buyer_id),
  CONSTRAINT fk_conversations_listing FOREIGN KEY (listing_id) REFERENCES listings (id),
  CONSTRAINT fk_conversations_buyer   FOREIGN KEY (buyer_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id  INT UNSIGNED NOT NULL,
  sender_id        INT UNSIGNED NOT NULL,
  body             VARCHAR(2000) NOT NULL,
  read_at          DATETIME NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_conversation (conversation_id, id),
  CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_sender       FOREIGN KEY (sender_id)       REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A completed sale (a listing can be sold only once). Ratings hang off a deal,
-- so every review is a "verified transaction".
CREATE TABLE IF NOT EXISTS deals (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id    INT UNSIGNED NOT NULL,
  buyer_id      INT UNSIGNED NOT NULL,
  seller_id     INT UNSIGNED NOT NULL,
  completed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_deals_listing (listing_id),
  KEY idx_deals_buyer (buyer_id),
  KEY idx_deals_seller (seller_id),
  CONSTRAINT chk_deals_parties CHECK (buyer_id <> seller_id),
  CONSTRAINT fk_deals_listing FOREIGN KEY (listing_id) REFERENCES listings (id),
  CONSTRAINT fk_deals_buyer   FOREIGN KEY (buyer_id)   REFERENCES users (id),
  CONSTRAINT fk_deals_seller  FOREIGN KEY (seller_id)  REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Buyer and seller rate each other once per deal
CREATE TABLE IF NOT EXISTS reviews (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deal_id        INT UNSIGNED NOT NULL,
  reviewer_id    INT UNSIGNED NOT NULL,
  reviewee_id    INT UNSIGNED NOT NULL,
  reviewer_role  ENUM('buyer','seller') NOT NULL,
  rating         TINYINT UNSIGNED NOT NULL,
  comment        VARCHAR(500) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_once (deal_id, reviewer_id),
  KEY idx_reviews_reviewee (reviewee_id, reviewer_role),
  CONSTRAINT chk_reviews_rating  CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT chk_reviews_parties CHECK (reviewer_id <> reviewee_id),
  CONSTRAINT fk_reviews_deal     FOREIGN KEY (deal_id)     REFERENCES deals (id),
  CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id),
  CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Browsing history that powers "Picked for your next find".
-- Guests are tracked by a hashed session key only; user_id is filled when signed in.
CREATE TABLE IF NOT EXISTS product_views (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id   INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NULL,
  session_key  CHAR(32)     NOT NULL,
  viewed_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_views_user (user_id, viewed_at),
  KEY idx_views_session (session_key, viewed_at),
  KEY idx_views_listing (listing_id),
  CONSTRAINT fk_views_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE,
  CONSTRAINT fk_views_user    FOREIGN KEY (user_id)    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  4. SAFETY: community guidelines and reports
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS guidelines (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code        VARCHAR(40)  NOT NULL,
  title       VARCHAR(120) NOT NULL,
  body        TEXT         NOT NULL,
  audience    ENUM('everyone','sellers') NOT NULL DEFAULT 'everyone',
  sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guidelines_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id       INT UNSIGNED NOT NULL,
  reporter_id      INT UNSIGNED NOT NULL,
  reason           ENUM('misleading_listing','prohibited_item','other') NOT NULL,
  details          VARCHAR(2000) NOT NULL,
  status           ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  guideline_id     INT UNSIGNED NULL,
  resolution_note  VARCHAR(1000) NULL,
  handled_by       INT UNSIGNED NULL,
  handled_at       DATETIME NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reports_status (status, created_at),
  KEY idx_reports_listing (listing_id),
  KEY idx_reports_reporter (reporter_id),
  CONSTRAINT fk_reports_listing   FOREIGN KEY (listing_id)   REFERENCES listings (id),
  CONSTRAINT fk_reports_reporter  FOREIGN KEY (reporter_id)  REFERENCES users (id),
  CONSTRAINT fk_reports_guideline FOREIGN KEY (guideline_id) REFERENCES guidelines (id),
  CONSTRAINT fk_reports_handler   FOREIGN KEY (handled_by)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evidence files (JPG, PNG or PDF) are stored privately and shown only to admins
CREATE TABLE IF NOT EXISTS report_attachments (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id      INT UNSIGNED NOT NULL,
  file_path      VARCHAR(255) NOT NULL,
  original_name  VARCHAR(255) NOT NULL,
  mime_type      VARCHAR(100) NOT NULL,
  file_size      INT UNSIGNED NOT NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attachments_report (report_id),
  CONSTRAINT fk_attachments_report FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  5. CUSTOMER SERVICE
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS support_tickets (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  category    ENUM('account','face_verification','listing','transaction','other') NOT NULL DEFAULT 'other',
  subject     VARCHAR(150) NOT NULL,
  status      ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tickets_status (status, updated_at),
  KEY idx_tickets_user (user_id),
  CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A message from the ticket owner, or from an admin (any sender other than the owner)
CREATE TABLE IF NOT EXISTS support_messages (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id   INT UNSIGNED NOT NULL,
  sender_id   INT UNSIGNED NOT NULL,
  body        VARCHAR(2000) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_support_messages_ticket (ticket_id, id),
  CONSTRAINT fk_support_messages_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE,
  CONSTRAINT fk_support_messages_sender FOREIGN KEY (sender_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  6. SYSTEM
-- ---------------------------------------------------------------------

-- Status updates ("Your listing was approved", "Report resolved", ...)
CREATE TABLE IF NOT EXISTS notifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  type        VARCHAR(40)  NOT NULL,
  title       VARCHAR(150) NOT NULL,
  body        VARCHAR(500) NULL,
  link        VARCHAR(255) NULL,
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, read_at, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Admin actions are recorded" (audit trail)
CREATE TABLE IF NOT EXISTS admin_actions (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id     INT UNSIGNED NOT NULL,
  action       VARCHAR(50)  NOT NULL,
  target_type  VARCHAR(30)  NOT NULL,
  target_id    INT UNSIGNED NULL,
  details      VARCHAR(500) NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_admin_actions_admin (admin_id, created_at),
  KEY idx_admin_actions_target (target_type, target_id),
  CONSTRAINT fk_admin_actions_admin FOREIGN KEY (admin_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed sign-ins, used to slow down password guessing
CREATE TABLE IF NOT EXISTS login_attempts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(190) NOT NULL,
  ip_address    VARCHAR(45)  NOT NULL,
  attempted_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_email (email, attempted_at),
  KEY idx_login_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  7. VIEW: average rating per user, split by role
--     as_role = 'seller' -> reviews the user received while selling
--     as_role = 'buyer'  -> reviews the user received while buying
-- ---------------------------------------------------------------------

CREATE OR REPLACE VIEW v_user_ratings AS
SELECT reviewee_id AS user_id,
       CASE reviewer_role WHEN 'buyer' THEN 'seller' ELSE 'buyer' END AS as_role,
       COUNT(*)            AS review_count,
       ROUND(AVG(rating), 1) AS avg_rating
FROM reviews
GROUP BY reviewee_id, reviewer_role;

-- ---------------------------------------------------------------------
--  8. REQUIRED SEED DATA
-- ---------------------------------------------------------------------

INSERT IGNORE INTO towns (name, slug) VALUES
  ('Bantayan',  'bantayan'),
  ('Santa Fe',  'santa-fe'),
  ('Madridejos','madridejos');

-- Top-level categories
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order) VALUES
  (NULL, 'Electronics',       'electronics',     1),
  (NULL, 'Home & living',     'home-living',     2),
  (NULL, 'Fashion',           'fashion',         3),
  (NULL, 'Sports & outdoors', 'sports-outdoors', 4),
  (NULL, 'Local goods',       'local-goods',     5);

-- Sub-categories (listings are posted into these)
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Mobile phones', 'mobile-phones', 1 FROM categories p WHERE p.slug = 'electronics';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Computers & laptops', 'computers-laptops', 2 FROM categories p WHERE p.slug = 'electronics';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Audio', 'audio', 3 FROM categories p WHERE p.slug = 'electronics';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Cameras', 'cameras', 4 FROM categories p WHERE p.slug = 'electronics';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Other electronics', 'other-electronics', 5 FROM categories p WHERE p.slug = 'electronics';

INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Furniture', 'furniture', 1 FROM categories p WHERE p.slug = 'home-living';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Kitchen & dining', 'kitchen-dining', 2 FROM categories p WHERE p.slug = 'home-living';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Appliances', 'appliances', 3 FROM categories p WHERE p.slug = 'home-living';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Home decor', 'home-decor', 4 FROM categories p WHERE p.slug = 'home-living';

INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Clothing', 'clothing', 1 FROM categories p WHERE p.slug = 'fashion';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Shoes', 'shoes', 2 FROM categories p WHERE p.slug = 'fashion';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Bags & accessories', 'bags-accessories', 3 FROM categories p WHERE p.slug = 'fashion';

INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Bicycles', 'bicycles', 1 FROM categories p WHERE p.slug = 'sports-outdoors';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Water sports', 'water-sports', 2 FROM categories p WHERE p.slug = 'sports-outdoors';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Fishing gear', 'fishing-gear', 3 FROM categories p WHERE p.slug = 'sports-outdoors';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Fitness', 'fitness', 4 FROM categories p WHERE p.slug = 'sports-outdoors';

INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Handicrafts', 'handicrafts', 1 FROM categories p WHERE p.slug = 'local-goods';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Shellcraft', 'shellcraft', 2 FROM categories p WHERE p.slug = 'local-goods';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Dried seafood', 'dried-seafood', 3 FROM categories p WHERE p.slug = 'local-goods';
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order)
  SELECT p.id, 'Food & delicacies', 'food-delicacies', 4 FROM categories p WHERE p.slug = 'local-goods';

-- Community guidelines (admins can edit these in the admin workspace)
INSERT IGNORE INTO guidelines (code, title, body, audience, sort_order) VALUES
  ('honest-listings',   'Describe items honestly',
   'Titles, descriptions, categories and prices must match the item. State the condition and mention any defects or missing parts.', 'everyone', 1),
  ('original-photos',   'Use your own photos',
   'Show the actual item with your own photos. Do not use pictures copied from other sellers, websites or catalogues.', 'sellers', 2),
  ('prohibited-items',  'No prohibited items',
   'Do not post illegal items, weapons, drugs, counterfeit or stolen goods, or protected wildlife and their parts.', 'everyone', 3),
  ('fair-pricing',      'Price fairly and clearly',
   'Post the real selling price. Placeholder prices, bait pricing and hidden conditions are not allowed.', 'sellers', 4),
  ('safe-meetups',      'Meet safely',
   'Meet in a public place, bring a friend if you can, and inspect the item before paying.', 'everyone', 5),
  ('no-scams',          'No scams or impersonation',
   'Do not ask for advance payments, one-time codes or personal data, and do not pretend to be another person. One person, one profile.', 'everyone', 6),
  ('respect-others',    'Be respectful',
   'Treat other members politely. Harassment, hate speech and threats lead to account restrictions.', 'everyone', 7),
  ('respect-privacy',   'Respect privacy',
   'Do not share other people''s personal information or photos without their permission.', 'everyone', 8);

-- Default administrator.  Email: admin@bantayan.local   Password: Admin@12345
-- >>> CHANGE THIS PASSWORD after your first sign-in (or replace the row). <<<
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, role, terms_accepted_at)
  SELECT 'Admin', 'Bantayan', 'admin@bantayan.local', '$2y$10$kthFLEZ1Og61A0/kFVdcxuJcEtCSpaiGIW857b4ObIy1dYmV7WUmi', t.id, 'admin', CURRENT_TIMESTAMP
  FROM towns t WHERE t.slug = 'bantayan';
