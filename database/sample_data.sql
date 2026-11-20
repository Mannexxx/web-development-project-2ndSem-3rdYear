-- =====================================================================
--  BANTAYAN MARKETPLACE  -  OPTIONAL demo data
--  Import AFTER schema.sql. Import it ONCE (running it twice may duplicate rows).
--
--  Demo accounts (all use the password  Password1!  ):
--    maria.santos@example.com   seller, face verified   (4.8 rating)
--    joel.reyes@example.com     seller, face verified
--    liza.cruz@example.com      seller, face verified
--    carlo.mendoza@example.com  seller, face verified
--    rina.alcala@example.com    seller, face verified
--    ana.reyes@example.com      buyer,  face verified
--    ben.lopez@example.com      member, NOT verified (has a support request)
--  Admin (from schema.sql):  admin@bantayan.local  /  Admin@12345
-- =====================================================================

USE `bantayan_market`;
SET NAMES utf8mb4;

INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Maria', 'Santos', 'maria.santos@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2025-03-14 09:00:00', '2025-03-14 09:00:00', '2025-03-14 09:00:00' FROM towns t WHERE t.slug = 'bantayan';
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Joel', 'Reyes', 'joel.reyes@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2025-05-02 10:00:00', '2025-05-02 10:00:00', '2025-05-02 10:00:00' FROM towns t WHERE t.slug = 'santa-fe';
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Liza', 'Cruz', 'liza.cruz@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2025-06-20 08:30:00', '2025-06-20 08:30:00', '2025-06-20 08:30:00' FROM towns t WHERE t.slug = 'bantayan';
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Carlo', 'Mendoza', 'carlo.mendoza@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2025-07-11 14:00:00', '2025-07-11 14:00:00', '2025-07-11 14:00:00' FROM towns t WHERE t.slug = 'madridejos';
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Rina', 'Alcala', 'rina.alcala@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2025-08-05 11:15:00', '2025-08-05 11:15:00', '2025-08-05 11:15:00' FROM towns t WHERE t.slug = 'santa-fe';
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Ana', 'Reyes', 'ana.reyes@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2025-09-01 16:40:00', '2025-09-01 16:40:00', '2025-09-01 16:40:00' FROM towns t WHERE t.slug = 'madridejos';
INSERT IGNORE INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at, face_verified_at, created_at)
  SELECT 'Ben', 'Lopez', 'ben.lopez@example.com', '$2y$10$8XqaV7sDhuovsO97KGFumuCf5TzU6DaiEIbKKj80jTdYYSCwrCxv.', t.id, '2026-10-01 09:10:00', NULL, '2026-10-01 09:10:00' FROM towns t WHERE t.slug = 'bantayan';

INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Samsung Galaxy A54', '128 GB, graphite. Lightly used and fully working. Includes original box and charging cable.', 12500, 'like_new', 'approved', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'maria.santos@example.com' AND c.slug = 'mobile-phones' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Samsung Galaxy A54' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Wooden dining set', 'Solid wooden dining table with four chairs. Minor surface scratches on the table top. Local pickup in Santa Fe.', 4800, 'used', 'approved', NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 5 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'joel.reyes@example.com' AND c.slug = 'furniture' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Wooden dining set' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'City bicycle', 'Reliable city bicycle with new tires. Ready for a local test ride. Brakes work well; small rust spots on the frame.', 3200, 'used', 'approved', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'carlo.mendoza@example.com' AND c.slug = 'bicycles' AND t.slug = 'madridejos'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'City bicycle' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Handwoven market bag', 'Handwoven market bag made on the island from natural fibres. Sturdy handles, fits a full week of market shopping.', 450, 'new', 'approved', NOW() - INTERVAL 27 HOUR, NOW() - INTERVAL 27 HOUR, NOW() - INTERVAL 27 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'liza.cruz@example.com' AND c.slug = 'handicrafts' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Handwoven market bag' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Bluetooth speaker', 'Portable Bluetooth speaker in good working condition. Charging cable included. Battery lasts about six hours.', 850, 'like_new', 'approved', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'rina.alcala@example.com' AND c.slug = 'audio' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Bluetooth speaker' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Study desk', 'Compact study desk with one drawer. Used for two school years; a few pen marks on the surface.', 1500, 'used', 'approved', NOW() - INTERVAL 50 HOUR, NOW() - INTERVAL 50 HOUR, NOW() - INTERVAL 50 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'maria.santos@example.com' AND c.slug = 'furniture' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Study desk' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Snorkeling set', 'Snorkeling mask and fins (size 42-44), never used. Ready for your next island trip.', 650, 'new', 'approved', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'rina.alcala@example.com' AND c.slug = 'water-sports' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Snorkeling set' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Canvas sneakers', 'Canvas sneakers, size 42, worn only a few times. No holes or stains.', 900, 'like_new', 'approved', NOW() - INTERVAL 75 HOUR, NOW() - INTERVAL 75 HOUR, NOW() - INTERVAL 75 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'carlo.mendoza@example.com' AND c.slug = 'shoes' AND t.slug = 'madridejos'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Canvas sneakers' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Samsung Galaxy A34', '128 GB, black. Used for one year. Screen protector on since day one; battery health is good.', 9800, 'used', 'approved', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'liza.cruz@example.com' AND c.slug = 'mobile-phones' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Samsung Galaxy A34' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Redmi Note 12', '128 GB, grey. Works fine. Small dent on the back corner. Charger included.', 7500, 'used', 'approved', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'carlo.mendoza@example.com' AND c.slug = 'mobile-phones' AND t.slug = 'madridejos'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Redmi Note 12' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Samsung Galaxy A54', '128 GB, violet. Used for eight months. Has a hairline scratch on the back glass. No box.', 11800, 'used', 'approved', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'joel.reyes@example.com' AND c.slug = 'mobile-phones' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Samsung Galaxy A54' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'iPhone 11', '64 GB, black. Battery replaced last year. Face ID works. Comes with charger.', 14500, 'used', 'approved', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'rina.alcala@example.com' AND c.slug = 'mobile-phones' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'iPhone 11' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Dried danggit (1 kg)', 'Sun-dried danggit from Bantayan, packed in a sealed bag. Perfect pasalubong.', 380, 'new', 'approved', NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'liza.cruz@example.com' AND c.slug = 'dried-seafood' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Dried danggit (1 kg)' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Wireless earbuds', 'Wireless earbuds with charging case. Works with Android and iPhone. Includes spare ear tips.', 700, 'used', 'approved', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'rina.alcala@example.com' AND c.slug = 'audio' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Wireless earbuds' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Mountain bike', '21-speed mountain bike, 26 inch wheels. Recently serviced.', 5500, 'used', 'pending', NOW() - INTERVAL 20 HOUR, NULL, NOW() - INTERVAL 20 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'carlo.mendoza@example.com' AND c.slug = 'bicycles' AND t.slug = 'madridejos'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Mountain bike' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Handmade shell necklace', 'Necklace handmade from small sea shells collected from the shoreline. Adjustable cord.', 280, 'new', 'pending', NOW() - INTERVAL 8 HOUR, NULL, NOW() - INTERVAL 8 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'liza.cruz@example.com' AND c.slug = 'shellcraft' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Handmade shell necklace' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Kitchen blender', 'Glass jar blender, 600W. Works well; the lid has a small chip.', 1200, 'used', 'pending', NOW() - INTERVAL 3 HOUR, NULL, NOW() - INTERVAL 3 HOUR
  FROM users u, categories c, towns t
  WHERE u.email = 'joel.reyes@example.com' AND c.slug = 'kitchen-dining' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Kitchen blender' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Electric stand fan', '16-inch stand fan with three speeds. Sold to a neighbour.', 600, 'used', 'sold', NOW() - INTERVAL 40 DAY, NOW() - INTERVAL 40 DAY, NOW() - INTERVAL 40 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'maria.santos@example.com' AND c.slug = 'appliances' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Electric stand fan' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Rice cooker', '1.8L rice cooker with steaming tray. Sold.', 900, 'used', 'sold', NOW() - INTERVAL 35 DAY, NOW() - INTERVAL 35 DAY, NOW() - INTERVAL 35 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'maria.santos@example.com' AND c.slug = 'kitchen-dining' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Rice cooker' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Wall clock', 'Silent wall clock, 30 cm. Sold.', 350, 'like_new', 'sold', NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 30 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'maria.santos@example.com' AND c.slug = 'home-decor' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Wall clock' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Phone tripod', 'Adjustable phone tripod with remote. Sold.', 450, 'like_new', 'sold', NOW() - INTERVAL 25 DAY, NOW() - INTERVAL 25 DAY, NOW() - INTERVAL 25 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'maria.santos@example.com' AND c.slug = 'cameras' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Phone tripod' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Plastic chairs (set of 4)', 'Four sturdy plastic chairs. Sold.', 800, 'used', 'sold', NOW() - INTERVAL 33 DAY, NOW() - INTERVAL 33 DAY, NOW() - INTERVAL 33 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'joel.reyes@example.com' AND c.slug = 'furniture' AND t.slug = 'santa-fe'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Plastic chairs (set of 4)' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Woven basket', 'Medium woven storage basket. Sold.', 250, 'new', 'sold', NOW() - INTERVAL 28 DAY, NOW() - INTERVAL 28 DAY, NOW() - INTERVAL 28 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'liza.cruz@example.com' AND c.slug = 'handicrafts' AND t.slug = 'bantayan'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Woven basket' AND x.seller_id = u.id);
INSERT INTO listings (seller_id, category_id, town_id, title, description, price, item_condition, status, submitted_at, published_at, created_at)
  SELECT u.id, c.id, t.id, 'Fishing rod', '2.1 m fishing rod with reel. Sold.', 700, 'used', 'sold', NOW() - INTERVAL 22 DAY, NOW() - INTERVAL 22 DAY, NOW() - INTERVAL 22 DAY
  FROM users u, categories c, towns t
  WHERE u.email = 'carlo.mendoza@example.com' AND c.slug = 'fishing-gear' AND t.slug = 'madridejos'
    AND NOT EXISTS (SELECT 1 FROM listings x WHERE x.title = 'Fishing rod' AND x.seller_id = u.id);

INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 38 DAY FROM listings l, users s, users b
  WHERE l.title = 'Electric stand fan' AND s.email = 'maria.santos@example.com' AND l.seller_id = s.id AND b.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 5, 'Item matched the description. Easy pickup.', NOW() - INTERVAL 37 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Electric stand fan' AND f.email = 'ana.reyes@example.com' AND t.email = 'maria.santos@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'seller', 5, 'Quick and polite buyer.', NOW() - INTERVAL 37 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Electric stand fan' AND f.email = 'maria.santos@example.com' AND t.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 33 DAY FROM listings l, users s, users b
  WHERE l.title = 'Rice cooker' AND s.email = 'maria.santos@example.com' AND l.seller_id = s.id AND b.email = 'carlo.mendoza@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 5, 'Smooth transaction, would buy again.', NOW() - INTERVAL 32 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Rice cooker' AND f.email = 'carlo.mendoza@example.com' AND t.email = 'maria.santos@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'seller', 4, 'Paid on time.', NOW() - INTERVAL 32 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Rice cooker' AND f.email = 'maria.santos@example.com' AND t.email = 'carlo.mendoza@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 28 DAY FROM listings l, users s, users b
  WHERE l.title = 'Wall clock' AND s.email = 'maria.santos@example.com' AND l.seller_id = s.id AND b.email = 'liza.cruz@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 4, 'Good seller, item as described.', NOW() - INTERVAL 27 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Wall clock' AND f.email = 'liza.cruz@example.com' AND t.email = 'maria.santos@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'seller', 5, 'Friendly and on time.', NOW() - INTERVAL 27 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Wall clock' AND f.email = 'maria.santos@example.com' AND t.email = 'liza.cruz@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 23 DAY FROM listings l, users s, users b
  WHERE l.title = 'Phone tripod' AND s.email = 'maria.santos@example.com' AND l.seller_id = s.id AND b.email = 'joel.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 5, 'Exactly as pictured.', NOW() - INTERVAL 22 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Phone tripod' AND f.email = 'joel.reyes@example.com' AND t.email = 'maria.santos@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 31 DAY FROM listings l, users s, users b
  WHERE l.title = 'Plastic chairs (set of 4)' AND s.email = 'joel.reyes@example.com' AND l.seller_id = s.id AND b.email = 'rina.alcala@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 5, 'Great deal and easy meetup.', NOW() - INTERVAL 30 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Plastic chairs (set of 4)' AND f.email = 'rina.alcala@example.com' AND t.email = 'joel.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'seller', 5, NULL, NOW() - INTERVAL 30 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Plastic chairs (set of 4)' AND f.email = 'joel.reyes@example.com' AND t.email = 'rina.alcala@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 26 DAY FROM listings l, users s, users b
  WHERE l.title = 'Woven basket' AND s.email = 'liza.cruz@example.com' AND l.seller_id = s.id AND b.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 5, 'Beautiful basket, thank you!', NOW() - INTERVAL 25 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Woven basket' AND f.email = 'ana.reyes@example.com' AND t.email = 'liza.cruz@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'seller', 4, 'Good communication.', NOW() - INTERVAL 25 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Woven basket' AND f.email = 'liza.cruz@example.com' AND t.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);
INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at)
  SELECT l.id, b.id, s.id, NOW() - INTERVAL 20 DAY FROM listings l, users s, users b
  WHERE l.title = 'Fishing rod' AND s.email = 'carlo.mendoza@example.com' AND l.seller_id = s.id AND b.email = 'joel.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM deals d WHERE d.listing_id = l.id);
INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at)
  SELECT d.id, f.id, t.id, 'buyer', 5, 'Works perfectly.', NOW() - INTERVAL 19 DAY
  FROM deals d JOIN listings l ON l.id = d.listing_id, users f, users t
  WHERE l.title = 'Fishing rod' AND f.email = 'joel.reyes@example.com' AND t.email = 'carlo.mendoza@example.com'
    AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.deal_id = d.id AND r.reviewer_id = f.id);

INSERT INTO conversations (listing_id, buyer_id, created_at, last_message_at)
  SELECT l.id, b.id, NOW() - INTERVAL 50 MINUTE, NOW() - INTERVAL 10 MINUTE FROM listings l, users s, users b
  WHERE l.title = 'Samsung Galaxy A54' AND l.seller_id = s.id AND s.email = 'maria.santos@example.com' AND b.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM conversations x WHERE x.listing_id = l.id AND x.buyer_id = b.id);
INSERT INTO messages (conversation_id, sender_id, body, read_at, created_at)
  SELECT cv.id, u.id, 'Hi! Is the phone still available?', NOW() - INTERVAL 49 MINUTE, NOW() - INTERVAL 50 MINUTE
  FROM conversations cv JOIN listings l ON l.id = cv.listing_id JOIN users s ON s.id = l.seller_id JOIN users b ON b.id = cv.buyer_id, users u
  WHERE l.title = 'Samsung Galaxy A54' AND s.email = 'maria.santos@example.com' AND b.email = 'ana.reyes@example.com' AND u.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = cv.id AND m.body = 'Hi! Is the phone still available?');
INSERT INTO messages (conversation_id, sender_id, body, read_at, created_at)
  SELECT cv.id, u.id, 'Hi! Yes, the phone is still available. You can inspect it before buying.', NOW() - INTERVAL 29 MINUTE, NOW() - INTERVAL 30 MINUTE
  FROM conversations cv JOIN listings l ON l.id = cv.listing_id JOIN users s ON s.id = l.seller_id JOIN users b ON b.id = cv.buyer_id, users u
  WHERE l.title = 'Samsung Galaxy A54' AND s.email = 'maria.santos@example.com' AND b.email = 'ana.reyes@example.com' AND u.email = 'maria.santos@example.com'
    AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = cv.id AND m.body = 'Hi! Yes, the phone is still available. You can inspect it before buying.');
INSERT INTO messages (conversation_id, sender_id, body, read_at, created_at)
  SELECT cv.id, u.id, 'Great! Could we meet near Bantayan town plaza?', NULL, NOW() - INTERVAL 10 MINUTE
  FROM conversations cv JOIN listings l ON l.id = cv.listing_id JOIN users s ON s.id = l.seller_id JOIN users b ON b.id = cv.buyer_id, users u
  WHERE l.title = 'Samsung Galaxy A54' AND s.email = 'maria.santos@example.com' AND b.email = 'ana.reyes@example.com' AND u.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = cv.id AND m.body = 'Great! Could we meet near Bantayan town plaza?');

INSERT INTO reports (listing_id, reporter_id, reason, details, status, created_at)
  SELECT l.id, u.id, 'misleading_listing', 'The listing says original, but the packaging appears different.', 'open', NOW() - INTERVAL 6 HOUR
  FROM listings l, users u WHERE l.title = 'Wireless earbuds' AND u.email = 'ana.reyes@example.com'
    AND NOT EXISTS (SELECT 1 FROM reports x WHERE x.listing_id = l.id AND x.reporter_id = u.id);
INSERT INTO support_tickets (user_id, category, subject, status, created_at, updated_at)
  SELECT u.id, 'face_verification', 'Face verification help', 'open', NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 3 HOUR
  FROM users u WHERE u.email = 'ben.lopez@example.com'
    AND NOT EXISTS (SELECT 1 FROM support_tickets x WHERE x.user_id = u.id AND x.subject = 'Face verification help');
INSERT INTO support_messages (ticket_id, sender_id, body, created_at)
  SELECT t.id, u.id, 'I can''t complete the camera step.', NOW() - INTERVAL 3 HOUR
  FROM support_tickets t JOIN users u ON u.id = t.user_id
  WHERE u.email = 'ben.lopez@example.com' AND t.subject = 'Face verification help'
    AND NOT EXISTS (SELECT 1 FROM support_messages m WHERE m.ticket_id = t.id);
INSERT INTO notifications (user_id, type, title, body, created_at)
  SELECT u.id, 'listing', 'Your listing is live', '"Samsung Galaxy A54" was approved.', NOW() - INTERVAL 2 HOUR
  FROM users u WHERE u.email = 'maria.santos@example.com'
    AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.user_id = u.id AND n.title = 'Your listing is live');
