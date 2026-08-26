# Royale Experience Global Concept (REGC) — Mini Ecommerce

A fast, simple, secure, modern single-page storefront for **Royale Experience Global Concept** —
premium foodstuff delivery — with a full checkout flow, order tracking, admin panel,
email + Telegram notifications and optional Rivo online payments.

Built with **raw PHP 8+** (no framework), **Supabase** (Postgres via REST), **Rivo**
(online payments), **Cloudinary** (images) and a dependency-free SMTP mailer.

## Requirements
- PHP 8.0+ (uses `str_starts_with`, `random_bytes`, `password_hash`)
- `php-curl`, `php-json`, `php-mbstring` enabled
- A Supabase project (database + REST API)
- Optional: SMTP credentials, Telegram bot, Rivo keys, Cloudinary

## 1. Database (Supabase)
1. Create a Supabase project.
2. Open **SQL Editor** and paste the contents of `database/schema.sql`, then run it.
   This creates `products`, `orders`, `settings`, `admins` tables, RLS, and seeds
   the foodstuff catalog + default settings.

## 2. Configuration (.env)
All secrets and settings live in `.env` (never committed). Copy the template and fill it in:

```
cp .env.example .env
```

Edit `.env`:
- `SUPABASE_URL` — `https://<ref>.supabase.co`
- `SUPABASE_ANON_KEY` / `SUPABASE_SERVICE_KEY` — from Supabase **Project Settings → API**
  (use the service_role key server-side; it bypasses RLS)
- `APP_BASE_URL` — your public URL (used for canonical/OG URLs, Rivo redirect & webhook)
- `DEFAULT_ADMIN_PASSWORD` — temporary admin password (set a real one in Admin → Settings)
- `APP_DESCRIPTION`, `APP_KEYWORDS`, `APP_OG_IMAGE`, `APP_TWITTER_HANDLE` — SEO/branding
- `ORG_*` — organization identity used in structured data (JSON-LD)

The `.env` and `includes/config.php` are blocked from direct web access.

## 3. Run
Pretty URLs require Apache rewrite (`.htaccess` included). Serve the project root
(document root must be the project folder) via XAMPP/Apache:

```
http://localhost/regc/
```

For local testing without Apache, use the built-in server router:

```
php -S localhost:8000 router.php
```

Open `/` (landing), `/shop` (storefront), and `/admin/login.php` (admin).

## 4. Admin setup
1. Log in at `/admin/login.php` with username `admin` and `DEFAULT_ADMIN_PASSWORD`.
2. Go to **Settings → Admin Account** and set a real password.
3. In **Settings → Store & Checkout** enter your bank details and delivery fee —
   these are shown to customers at checkout (bank transfer is always available).
4. In **Settings → Email & Telegram** add SMTP + admin email and/or Telegram bot token
   + chat id. Click **Send Test** to verify. New orders notify the customer (email) and
   admin (email + Telegram).
5. **Optional — Rivo**: paste your Rivo API key + webhook secret and enable it. The webhook
   URL to configure in Rivo is shown on the settings page
   (`/api/rivo_webhook.php`). When enabled, customers can pay online; bank details are
   still shown otherwise.

## Flow
- Customer adds products to cart (localStorage), checks out, enters name/email/phone/address.
- Order is stored in Supabase; emails + Telegram notification sent.
- Payment: Bank transfer (details shown) and/or Rivo online (authorization URL →
  return → verify → mark paid).
- Admin confirms orders and updates status/payment in **Admin → Orders**.
- Customers track order status via the **Track Order** section on the storefront.

## URLs (pretty, SEO-ready)
| Route | Page |
|---|---|
| `/` | Home (brand landing) |
| `/shop` | Ecommerce storefront (search, filter, sort) |
| `/shop/category/<cat>` | Category listing |
| `/product/<slug>` | Product detail |
| `/privacy`, `/terms`, `/cookies` | Legal pages |
| `/sitemap.xml`, `/robots.txt` | Dynamic SEO files |
| `/wh` | GitHub webhook deploy (auto `git pull`) |

## Auto-deploy via GitHub webhook (/wh)

The site can update itself on every push:

1. **Prepare the server folder as a git repo** (once):
   ```bash
   cd /path/to/regc
   git init
   git remote add origin https://github.com/<you>/<repo>.git
   git fetch origin && git checkout -t origin/main   # or your branch
   ```
2. **Set the webhook secret** in `.env` (`WEBHOOK_SECRET="rayyan"` by default — change it in production).
3. **In GitHub**: repo → Settings → Webhooks → Add webhook:
   - Payload URL: `https://your-site.com/wh`
   - Content type: `application/json`
   - Secret: the same `WEBHOOK_SECRET`
   - Events: **Just the push event**

On every push GitHub POSTs to `/wh`; the endpoint verifies the HMAC-SHA256 signature (`X-Hub-Signature-256`), responds to GitHub's `ping` test, then runs `git pull` in the web root. Invalid signatures are rejected (401), non-push events ignored, and a lock file prevents concurrent deploys. Logs go to `logs/webhook.log`.

## SEO / AEO / OG
Every page renders:
- Canonical URLs, meta description/keywords, robots directives
- OpenGraph (`og:title`, `og:description`, `og:image`, `og:url`, `og:locale`)
- Twitter cards (`summary_large_image`)
- Structured data (JSON-LD): `Organization`, `WebSite` (+ `SearchAction`), `Product`
  (with `Offer`), `BreadcrumbList`, `ItemList`
- Dynamic `sitemap.xml` and `robots.txt` built from live products
- Cookie-consent banner with Accept/Decline, plus privacy/terms/cookie policies
- Security headers + browser caching + gzip in `.htaccess` (performance/PageSpeed)

## Directory
```
api/                 Public & webhook endpoints
admin/               Admin panel (login, dashboard, orders, products, settings)
assets/              storefront style.css + script.js + spa.js + favicons
includes/            bootstrap, helpers, dotenv, supabase client, mailer, notifications, rivo, settings, auth, seo
pages/               home, shop, product, legal, sitemap, robots (+ partials)
database/schema.sql  Supabase schema + seed
products/            product images
.env / .env.example  environment secrets
.htaccess            pretty URLs + security/performance headers
router.php           built-in server router (local testing)
logs/                app log (auto-created)
cache/               settings cache (auto-created)
```