ALTER TABLE listings
  ADD COLUMN stock INT UNSIGNED NOT NULL DEFAULT 1 AFTER price,
  ADD KEY idx_listings_stock (status, stock);

UPDATE listings SET stock = 0 WHERE status = 'sold';

CREATE TABLE purchases (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id    INT UNSIGNED NOT NULL,
  buyer_id      INT UNSIGNED NOT NULL,
  seller_id     INT UNSIGNED NOT NULL,
  listing_title VARCHAR(150) NOT NULL,
  unit_price    DECIMAL(10,2) NOT NULL,
  status        ENUM('to_receive','to_review','completed','canceled') NOT NULL DEFAULT 'to_receive',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  received_at   DATETIME NULL,
  canceled_at   DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_purchases_buyer_status (buyer_id, status, created_at),
  KEY idx_purchases_seller (seller_id, created_at),
  CONSTRAINT chk_purchases_parties CHECK (buyer_id <> seller_id),
  CONSTRAINT fk_purchases_listing FOREIGN KEY (listing_id) REFERENCES listings (id),
  CONSTRAINT fk_purchases_buyer FOREIGN KEY (buyer_id) REFERENCES users (id),
  CONSTRAINT fk_purchases_seller FOREIGN KEY (seller_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE deals
  DROP INDEX uq_deals_listing,
  ADD COLUMN purchase_id INT UNSIGNED NULL AFTER id,
  ADD UNIQUE KEY uq_deals_purchase (purchase_id),
  ADD KEY idx_deals_listing (listing_id),
  ADD CONSTRAINT fk_deals_purchase FOREIGN KEY (purchase_id) REFERENCES purchases (id);
