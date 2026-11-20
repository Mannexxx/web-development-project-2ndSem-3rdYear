# Bantayan Marketplace

A PHP + MySQL local marketplace for Bantayan Island, built from your wireframes (desktop and mobile).
<<<<<<< HEAD
Runs on XAMPP; Composer installs PHPMailer for email and SMS gateway verification.
=======
Runs on XAMPP with no Composer and no external services.
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073

## Run it (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\marketplace`. Start **Apache** and **MySQL** in the XAMPP Control Panel.
<<<<<<< HEAD
2. In phpMyAdmin, create and select a database named `bantayan_market`, then import `database.sql`. The schema creates the tables, categories, towns, and guidelines in the selected database.
3. Open `http://localhost/marketplace/`.

Database settings default to the XAMPP values (`localhost`, database `bantayan_market`, user `root`, empty password). Provider-specific values belong in the ignored `config.local.php`, not in `config.php`.

## Deploy to InfinityFree

1. Create an InfinityFree account and hosting site. Enable HTTPS for the site before opening it publicly.
2. In the hosting control panel, create a MySQL database and note the exact database host, database name, username, and password. These values are assigned by the host; do not assume the host is `localhost` or that the database name is `bantayan_market`.
3. Copy `config.local.php.example` to `config.local.php`. In `config.local.php`, set `db_host`, `db_name`, `db_user`, and `db_pass` to those values, and set `app_debug` to `false`. Keep this file private. The actual `config.local.php` is excluded from Git and denied by `.htaccess`.
4. On your computer, run `composer install --no-dev --optimize-autoloader`, then upload the application files and folders to the site's `htdocs` directory, including the generated `vendor/` directory and all `.htaccess` files. Do not upload `.git/`, local development data, or a local `config.local.php` containing credentials. Upload your hosting-specific `config.local.php` securely after creating it. Preserve `storage/.htaccess`, `uploads/.htaccess`, `views/.htaccess`, `src/.htaccess`, and `database/.htaccess`.
5. In phpMyAdmin, select the database created in step 2 and import `database.sql`. The schema intentionally does not create or select a database, because shared hosting assigns database names and usually does not grant permission to create databases. For an existing installation, back up the database before applying schema changes.
6. Open the HTTPS site and test registration, login, listing/photo uploads, and admin access. Check the host's PHP selector supports PHP 8.1 or later and that PDO MySQL, mbstring, fileinfo, and cURL are enabled. Face verification works only on HTTPS; its image check is less capable when the GD extension is unavailable.
7. Set the Google OAuth authorized redirect URI to `https://YOUR-DOMAIN/google.php` and use that exact URL as `google_redirect_uri` in `config.local.php`. Replace `YOUR-DOMAIN` with the actual domain. Update any Google consent-screen test-user/publishing settings as appropriate.
8. Configure verification email only with a delivery method permitted by your host. Free shared hosts may restrict outbound SMTP connections; verify a real message can be delivered before relying on email or email-to-SMS verification. Do not assume Gmail SMTP works from shared hosting.

The app's upload validation allows files up to 5 MB, but the host's PHP upload and request-size limits may be lower. Check the hosting control panel/PHP selector and test uploads; do not raise application limits beyond the host's limits. The `storage/` directory contains face-verification images and private report evidence. Keep its access-denial file in place, and do not put real biometric data on a shared host until the privacy, retention, access-control, and backup arrangements have been reviewed.

## Email and email-to-SMS verification

Email verification and SMS gateway delivery use PHPMailer with Gmail SMTP. Install PHP dependencies with `composer install`. If `config.local.php` does not exist, copy `config.local.php.example` to create it. If it already exists, add/update the SMTP settings without replacing other local settings. The local file is excluded from Git and blocked from direct web access. SMTP settings can also be supplied as environment variables.

- **Gmail SMTP:** enable 2-Step Verification on the sending Google account and create an App Password. Use `smtp.gmail.com`, port `587`, encryption `tls`, the full Gmail address as username/from address, and the App Password. Do not use the normal Google account password.
- **Carrier email-to-SMS gateways:** phone registration and SMS sign-in send a short verification message to `<11-digit-number>@txt.globe.com.ph` for Globe/TM or `<11-digit-number>@smartemail.com.ph` for Smart/TNT. Users select their carrier when registering. Gateways are carrier-operated and delivery may be delayed or unsupported; verify end-to-end delivery with each carrier before relying on this for production authentication.

Users may register with an email address or Philippine mobile number. Email and SMS verification codes expire after 10 minutes, stop after five incorrect attempts, and can only be resent once per minute.

For databases created before carrier selection was added, run `database/migrations/20261119_add_phone_carrier.sql` once in phpMyAdmin. New databases get the carrier field from either schema SQL file.
For existing databases, run `database/migrations/20261120_add_deactivated_account_status.sql` once to enable account deactivation. Run `database/migrations/20261120_add_listing_stock_and_purchases.sql` once to add listing inventory and buyer orders. New databases get these features from either schema SQL file.

## Google sign-in

Google sign-in is implemented with Google's OpenID Connect authorization-code flow. To enable it locally:

1. In Google Cloud Console, create or select a project and configure the OAuth consent screen. While the app is in testing mode, add the Google accounts that should be allowed to sign in as test users.
2. Create an OAuth client ID with application type **Web application**.
3. Add the exact callback URI for the environment: `http://localhost/marketplace/google.php` locally, or `https://YOUR-DOMAIN/google.php` on the deployed site.
4. Copy the client ID and client secret into the ignored `config.local.php` file's `google_client_id` and `google_client_secret` entries. Set `google_redirect_uri` to the exact matching callback URI. Do not commit or share the client secret.
5. Open `http://localhost/marketplace/login.php` and try **Sign in with Google**.

The redirect URI must match exactly in Google Cloud Console and `config.local.php`. For another local hostname, port, installation path, or a production HTTPS domain, replace it in both places with the full callback URL for that deployment. The app also accepts `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` environment variables.
=======
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
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073

## Pages (matching the wireframes)

| Page | File |
|---|---|
| Home: filters, fresh finds, AI picks | `index.php` |
| Product detail, message seller, report | `listing.php` |
<<<<<<< HEAD
| My Purchases and order sections | `purchases.php` |
=======
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
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

<<<<<<< HEAD
## Code layout

- `*.php` at the project root are public page and compatibility entry points.
- `src/` contains application classes: `Repository/` for database access, `Service/` for business logic, and `helpers.php` for shared output/URL helpers.
- `views/` contains reusable page templates and partials.
- `assets/` contains the active CSS and JavaScript.
- `api/` contains JSON endpoints.
- `admin/` contains the active admin workspace.
- `database/` contains the production schema.
- `storage/` contains private evidence and face data; `uploads/` contains public listing images.

Legacy names such as `products.php`, `profile.php`, `product.php`, and `admin.php` remain as compatibility redirects. New links and code should use `index.php`, `listing.php`, `seller.php`, and `admin/index.php`.

## Database

22 tables + 1 view, InnoDB, utf8mb4, foreign keys and CHECK constraints.
Groups: people & trust (`towns`, `users`, `face_verifications`), catalogue (`categories`, `listings`, `listing_photos`, `listing_moderations`),
buying (`conversations`, `messages`, `purchases`, `deals`, `reviews`, `product_views`), safety (`guidelines`, `reports`, `report_attachments`),
=======
Code layout: `src/Repository` (all SQL), `src/Service` (auth, uploads, AI, face check), `views/` (templates), `assets/` (CSS/JS), `api/` (JSON endpoints).

## Database

21 tables + 1 view, InnoDB, utf8mb4, foreign keys and CHECK constraints.
Groups: people & trust (`towns`, `users`, `face_verifications`), catalogue (`categories`, `listings`, `listing_photos`, `listing_moderations`),
buying (`conversations`, `messages`, `deals`, `reviews`, `product_views`), safety (`guidelines`, `reports`, `report_attachments`),
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
support (`support_tickets`, `support_messages`), system (`notifications`, `admin_actions`, `login_attempts`), and the view `v_user_ratings`.
The script is re-runnable (`IF NOT EXISTS`, `INSERT IGNORE`).

## Honest notes

- The site asks for confirmation before logging out and consequential actions such as submitting listings or reports, capturing face-verification frames, submitting ratings, making moderation decisions, and removing listing photos or listings.
- **Face verification is a basic check, not face recognition.** It confirms three camera frames arrive, are lit, and change between frames (head movement).
  It does not match a face to a person. `src/Service/FaceVerifier.php` is the one place to swap in a real provider. The camera needs `localhost` or HTTPS.
  Admins can manually verify a member from a customer-service request if the camera step fails.
- **The "AI" runs locally, no API key.** Category detection uses keywords from the title, description and photo file names (it does not look at image pixels).
  Price ranges come from comparable listings already on the site. The assistant answers from site data and rules. All can be upgraded to a real LLM later.
<<<<<<< HEAD
- **No online payments or delivery**: buyers and sellers arrange local pickup directly. Buy Now reserves one stock unit, and buyers manage receipt, review, and cancellation from My Purchases. No emails are sent (XAMPP has no mail server); seller updates appear in the in-site message inbox.
- The Privacy page is starter text. Face images are biometric data, so have it reviewed against the Philippine Data Privacy Act before public launch.
- Keep `app_debug` set to `false` in `config.local.php` on any public server (this is also the default).
=======
- **No payments or delivery**: buyers and sellers meet locally, as in the wireframes. No emails are sent (XAMPP has no mail server); updates appear as in-site notifications.
- The Privacy page is starter text. Face images are biometric data, so have it reviewed against the Philippine Data Privacy Act before public launch.
- Set `APP_DEBUG` to `false` in `config.php` on any public server.
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
- Reference face images and report evidence are stored in `storage/` (blocked from the web by `.htaccess`); listing photos in `uploads/`.
