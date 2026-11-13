# Bantayan Marketplace

A PHP + MySQL local marketplace for Bantayan Island, built from your wireframes (desktop and mobile).
Runs on XAMPP with no Composer and no external services.

## Run it (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\marketplace`. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin` > **Import** and import, in this order:
   1. `database/schema.sql` – creates the database `bantayan_market`, all tables, categories, towns, guidelines and the admin account.
   2. `database/sample_data.sql` – *optional* demo listings, users, messages and reviews (import once).
3. Open `http://localhost/marketplace/`.

Database settings are the XAMPP defaults (`root`, empty password) in `config.php`.

## Accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@bantayan.local` | `Admin@12345` (**change it**) |
| Demo seller | `maria.santos@example.com` | `Password1!` |
| Demo buyer | `ana.reyes@example.com` | `Password1!` |
| Demo, not verified | `ben.lopez@example.com` | `Password1!` |

To change the admin password, create a hash with `php -r "echo password_hash('NewPass1!', PASSWORD_DEFAULT);"`
and run in phpMyAdmin: `UPDATE users SET password_hash = '<hash>' WHERE email = 'admin@bantayan.local';`

## Pages (matching the wireframes)

| Page | File |
|---|---|
| Home: filters, fresh finds, AI picks | `index.php` |
| Product detail, message seller, report | `listing.php` |
| Seller profile & reviews | `seller.php` |
| Sell a product (+ AI selling assistant) | `sell.php` |
| Create profile + face verification | `register.php` |
| Sign in / out | `login.php`, `logout.php` |
| Messages, mark as sold, rate | `messages.php` |
| My account, listings, notifications | `account.php` |
| Customer service | `support.php` |
| AI assistant | `assistant.php` |
| Admin workspace | `admin/index.php` |
| Guidelines, privacy, help | `guidelines.php`, `privacy.php`, `help.php` |

Code layout: `src/Repository` (all SQL), `src/Service` (auth, uploads, AI, face check), `views/` (templates), `assets/` (CSS/JS), `api/` (JSON endpoints).

## Database

21 tables + 1 view, InnoDB, utf8mb4, foreign keys and CHECK constraints.
Groups: people & trust (`towns`, `users`, `face_verifications`), catalogue (`categories`, `listings`, `listing_photos`, `listing_moderations`),
buying (`conversations`, `messages`, `deals`, `reviews`, `product_views`), safety (`guidelines`, `reports`, `report_attachments`),
support (`support_tickets`, `support_messages`), system (`notifications`, `admin_actions`, `login_attempts`), and the view `v_user_ratings`.
The script is re-runnable (`IF NOT EXISTS`, `INSERT IGNORE`).

## Honest notes

- The site asks for confirmation before logging out and consequential actions such as submitting listings or reports, capturing face-verification frames, submitting ratings, making moderation decisions, and removing listing photos or listings.
- **Face verification is a basic check, not face recognition.** It confirms three camera frames arrive, are lit, and change between frames (head movement).
  It does not match a face to a person. `src/Service/FaceVerifier.php` is the one place to swap in a real provider. The camera needs `localhost` or HTTPS.
  Admins can manually verify a member from a customer-service request if the camera step fails.
- **The "AI" runs locally, no API key.** Category detection uses keywords from the title, description and photo file names (it does not look at image pixels).
  Price ranges come from comparable listings already on the site. The assistant answers from site data and rules. All can be upgraded to a real LLM later.
- **No payments or delivery**: buyers and sellers meet locally, as in the wireframes. No emails are sent (XAMPP has no mail server); updates appear as in-site notifications.
- The Privacy page is starter text. Face images are biometric data, so have it reviewed against the Philippine Data Privacy Act before public launch.
- Set `APP_DEBUG` to `false` in `config.php` on any public server.
- Reference face images and report evidence are stored in `storage/` (blocked from the web by `.htaccess`); listing photos in `uploads/`.
