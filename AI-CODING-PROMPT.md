# AI CODER PROMPT — Build a Complete Mini-Ecommerce (Raw PHP + Supabase + Rivo + Cloudinary)

> Use this prompt to have an AI coding agent generate a production-ready, single-brand
> ecommerce website exactly like the reference project (FATI LABULE). Replace the
> `[BRAND]` placeholders with the target business details. All features below are
> REQUIRED unless marked (optional).

---

## 0. Goal & Constraints

Build a **fast, simple, secure, modern single-page storefront + full admin** for
`[BRAND]`, a business selling `[PRODUCTS, e.g. "natural herbs and spices"]`.

**Non-negotiable constraints:**
- **Raw PHP 8+** (no framework, no Composer) for the public site, admin, and APIs.
- Database = **Supabase** (Postgres), accessed server-side via the Supabase REST API (PostgREST) using the `service_role` key through a small cURL client. Keys NEVER exposed to the browser.
- Payment API = **Rivo** (create / verify / status / webhook per `rivo.md`). **Bank-transfer account details from the admin Store settings are ALWAYS shown at checkout** — even when Rivo is not enabled.
- Notifications = **email (customer + admin)** via a dependency-free SMTP client and **Telegram (admin)** via the Bot API. Every notification type is toggleable from the admin.
- Images = **Cloudinary** for product uploads and site assets (favicon, OG image, hero), with local fallback.
- Secrets in a **`.env`** file (gitignored). A committed `includes/config.php` reads everything from `.env` via a tiny `env()` helper (so it contains NO secrets and can be committed).
- Pretty URLs via Apache `.htaccess` front-controller (single `index.php` router).
- SEO/AEO/OG-ready: canonical, OpenGraph + Twitter cards with image dimensions, JSON-LD structured data, dynamic `sitemap.xml`/`robots.txt`, one `<h1>` per page, full favicon set + web manifest, cache-busted asset URLs.
- **SPA feel via progressive enhancement (PJAX)**: every URL is a fully server-rendered, crawlable page; JS intercepts internal links/forms, fetches the next page, swaps only `<main>` + `<head>` meta, updates URL via `history.pushState`, and supports back/forward.
- Deployable by `git pull`; auto-deploy endpoint `/wh` (GitHub webhook, HMAC-verified with a secret).

---

## 1. Brand & business settings (customize per business)

- `APP_NAME`, `APP_TAGLINE`, `APP_DESCRIPTION`, `APP_KEYWORDS`
- `APP_BASE_URL` (no trailing slash) — used for canonical, OG, sitemap, Rivo redirects, webhook
- `APP_OG_IMAGE`, `APP_TWITTER_HANDLE`, `ORG_NAME/EMAIL/PHONE/ADDRESS/FOUNDED`
- `WEBHOOK_SECRET` (GitHub auto-deploy), `DEFAULT_ADMIN_PASSWORD` (temporary)
- `SUPABASE_URL`, `SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_KEY`
- `CLOUDINARY_URL` (`cloudinary://api_key:api_secret@cloud_name`)
- `APP_DEBUG=0/1` (1 shows PHP errors on screen for diagnosing 500s; errors are ALWAYS logged to `logs/php-error.log` regardless)
- Store settings (editable in admin, stored in Supabase `settings` table): bank name/account name/account number/payment instructions, delivery fee, WhatsApp, TikTok URL, store address.

---

## 2. File structure (replicate exactly)

```
/
├── index.php                 # front-controller / router (pretty URLs)
├── .htaccess                 # rewrite, security headers, caching, block sensitive files
├── router.php                # PHP built-in-server router (local testing, mirrors .htaccess)
├── .env / .env.example       # secrets (gitignored) / template
├── .gitignore
├── README.md
├── includes/
│   ├── config.php            # committed; defines constants from env() only (no secrets)
│   ├── dotenv.php            # minimal .env loader + env() with DEFAULT_<KEY> fallback
│   ├── bootstrap.php         # loads everything; PHP-version guard, polyfills, error display/log setup,
│   │                         #   required-extensions check, session, Supabase db(), settings cache,
│   │                         #   product cache, helpers (public_config, product_img, asset_version)
│   ├── helpers.php           # e(), e_js(), money(), json_out(), read_json_input(), csrf_*(), log_msg(), ...
│   ├── supabase.php          # Supabase REST (PostgREST) cURL client (select/insert/update/delete/rpc)
│   ├── settings.php          # settings table read + file cache (cache/settings.json) + save
│   ├── auth.php              # admin login/logout/guards (password_verify + CSRF-protected session)
│   ├── mailer.php            # dependency-free SMTP client (STARTTLS, AUTH LOGIN/PLAIN, multi-line, SNI)
│   ├── notifications.php     # email builders + senders (order/payment/status), Telegram, toggles
│   ├── rivo.php              # Rivo client (create/verify/status) + webhook signature verification
│   ├── cloudinary.php        # Cloudinary signed upload + delivery URLs + cloudinary_asset() fallback
│   └── seo.php               # seo_head(), abs_url(), abs_img(), organization_jsonld() (JSON-LD)
├── pages/
│   ├── home.php              # landing: hero, features, products, benefits, about, testimonials,
│   │                         #   TikTok/social section, contact; wrapped in <main id="app">
│   ├── shop.php              # storefront: hero, search, category sidebar, sort, product grid
│   ├── product.php           # product detail: breadcrumb, media, qty stepper, add-to-cart, related
│   ├── legal.php             # privacy / terms / cookies (pretty URLs)
│   ├── sitemap.php           # dynamic XML sitemap (home, shop, categories, products, legal)
│   ├── robots.php            # dynamic robots.txt (+ disallow /wh, /admin, /api, /includes)
│   └── partials/
│       ├── header.php        # <head> via seo_head(); fonts, favicon set, manifest, versioned style.css
│       ├── site_header.php   # shared top-bar + nav (persistent SPA chrome), logo NOT an <h1>
│       └── footer.php        # footer, cart drawer, checkout modal, cookie banner, versioned script.js/spa.js
├── assets/
│   ├── style.css             # all public styling (single file, cache-busted)
│   ├── script.js             # cart (localStorage), checkout, tracking, product filter, cookie consent
│   ├── spa.js                # PJAX engine
│   ├── og-image.jpg, favicon.svg, favicon-*.png, favicon.ico, apple-touch-icon.png, site.webmanifest
├── admin/
│   ├── login.php, index.php (dashboard), orders.php, products.php, settings.php
│   ├── admin.css             # responsive (slide-in sidebar + hamburger, tables, modals, forms)
│   ├── admin.js              # toast() + mobile menu toggle
│   └── api/                  # dashboard.php, notification_test.php, products.php, upload.php
├── api/
│   ├── config.php            # GET public settings (bank details, delivery, rivo flag)
│   ├── checkout.php          # POST create order (server-side price recompute) + notify + rivo
│   ├── order_status.php      # GET order status by order_no
│   ├── payment_return.php    # Rivo return: verify, mark paid, notify (once)
│   └── rivo_webhook.php      # HMAC-verified webhook: mark paid, notify (once)
├── database/schema.sql       # full schema + seed (run in Supabase SQL editor)
├── products/                 # local product images (fallback)
└── cache/, logs/             # auto-created, gitignored
```

---

## 3. Database schema (Supabase, `database/schema.sql`)

Run in Supabase SQL Editor. Enable RLS; the PHP backend uses the service_role key (bypasses RLS); allow public SELECT on active products.

```sql
products  (id identity pk, name, slug unique, category, price numeric, old_price,
           short_description, description, image, badge, featured bool, active bool, stock int, created_at)
orders    (id identity pk, order_no unique, name, email, phone, address,
           subtotal, delivery_fee, total, currency default 'NGN',
           payment_method default 'bank', status default 'pending',
           items jsonb, payment_ref, payment_status default 'unpaid', note, created_at, updated_at)
settings  (key text pk, value text)
admins    (id identity pk, username unique, password_hash, created_at)
```

Seed: the product catalog; default settings (bank details placeholder, delivery fee,
WhatsApp, TikTok URL, payment instructions, empty SMTP/Telegram/Rivo, and all
notification toggles defaulting to `1` except `email_include_bank=0`).

---

## 4. Routing (pretty URLs) — `index.php` front controller + `.htaccess`

`.htaccess`: rewrite non-file requests to `index.php`; add security headers
(`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`);
browser caching (1 year for assets, gzip via mod_deflate); block direct access to
`.env`, `.sql`, `.log`, `/includes/`, `/cache/`, `/logs/`, `/database/`.

Routes:
- `/` → home
- `/shop`, `/shop/category/<cat>`, `/shop?q=&sort=` → shop
- `/product/<slug>` → product detail (404 page if not found)
- `/privacy`, `/terms`, `/cookies` → legal
- `/sitemap.xml`, `/robots.txt` → SEO files
- `/wh` (alias `/github-webhook`) → GitHub deploy webhook
- `index.php` normalizes the base-path when the site lives in a subfolder.

`router.php` mirrors this for `php -S localhost:8000 router.php`.

---

## 5. Public storefront features

- **Landing (`/`)**: sticky top bar, sticky nav (mobile menu), hero with CTA + stats,
  feature boxes, **product grid identical to `ui.html`** (badges, category, name,
  description, price, sale price, Add-to-cart), benefits, about, testimonials,
  a **"We sell on TikTok"** showcase section (brand sells via its TikTok profile),
  contact with WhatsApp/TikTok/address links. One `<h1>` per page (logo is a `<span>`).
- **Shop (`/shop`)**: hero with search box, category sidebar with counts, sort
  (featured / price asc / price desc / name), product grid with sale % badges,
  empty state, "browse all" CTA. Links are SPA-interactive.
- **Product detail (`/product/<slug>`)**: breadcrumb, image, name, price (+ old price,
  save amount), stock indicator, quantity stepper, Add to Cart, full description,
  trust badges, related products, contact CTA.
- **Cart**: slide-out drawer; localStorage-persisted; quantity +/-, remove, live
  count/total badge; persists across SPA navigation.
- **Checkout modal**: fields = **name, email, phone, delivery address (+ optional note)**.
  Payment options: **Bank Transfer (always shown with account details from Store
  settings)** and, if Rivo enabled, "Pay Online". On submit → `api/checkout.php`.
  On success: if Rivo → redirect to authorization URL; else show success + order no
  + bank details. Emails + Telegram are sent automatically.
- **Order tracking** section (by order number via `api/order_status.php`).
- **Cookie consent banner** (Accept All / Essential Only, persisted).
- **Legal pages** with real content.

---

## 6. Checkout & payments (seamless, secure)

`POST api/checkout.php` (JSON):
1. Validate name/email/phone/address (422 with clear messages).
2. Recompute prices **server-side** from the DB (never trust client prices). Fetch
   products by id, build items with `{id, name, price, quantity, image}`, compute
   subtotal + delivery fee (from settings) + total.
3. Insert order (status `pending`, payment_status `unpaid`).
4. If `payment_method=rivo` and Rivo enabled: create a session (`amount`, `email`,
   `redirect_url` → `api/payment_return.php?order_no=...`, idempotency key = order no);
   store `payment_ref`; return `authorization_url`. If Rivo call fails, fall back to
   bank flow (order still created).
5. Send notifications (customer confirmation + admin email/Telegram).
6. Return `{ok, order_no, total, payment_method, authorization_url?, bank:{...}}`.

**Rivo confirmation (two paths, notify ONCE):**
- `api/rivo_webhook.php`: verify `X-PGSP-Signature` (`t=<ts>,v1=<hmac-sha256>` over
  `ts + "." + rawBody` with the webhook secret). On SUCCESS: mark order paid/confirmed,
  send payment-received email **only if payment_status wasn't already paid** (webhook
  and return may both fire). Return 200.
- `api/payment_return.php`: verify by reference, mark paid/confirmed, same once-only
  notification, redirect to `/shop?status=success|payment|error`.

**Bank transfer**: order is created `unpaid`; the customer pays to the account shown;
admin marks it paid (triggers payment email) in Admin → Orders.

---

## 7. Notifications (configurable in Admin → Settings → Email & Telegram)

`includes/notifications.php` provides `notif_config()` (reads settings + toggles) and:
- `send_order_notifications()` — customer order confirmation; admin email + Telegram.
- `send_payment_notifications()` — customer + admin "payment received".
- `send_status_notification()` — customer order status update (on admin change).
- `send_telegram()` — Bot API (Markdown), empty `telegram_api_base` falls back to `https://api.telegram.org`.

**Email templates** (branded shell): item table (item/qty/price/subtotal), subtotal,
delivery, total, address/phone, and a **Payment details box** (account name/bank/
number + instructions) — included in the admin email always, and in the customer
email **only when the `email_include_bank` toggle is on**.

**Toggles** (settings keys, defaults): `notify_customer_order=1`, `notify_customer_payment=1`,
`notify_customer_status=1`, `email_include_bank=0`, `notify_admin_email=1`,
`notify_admin_telegram=1`, `notify_admin_payment=1`.

**SMTP client (mailer.php)** — critical implementation details:
- Handles **multi-line** SMTP responses (EHLO `250-...` continuation).
- STARTTLS: send `STARTTLS` → expect `220` → enable crypto (re-EHLO after).
- SSL context with `peer_name`, `SNI_enabled=true`, `verify_peer=false` (mail servers
  use self-signed/hostname-mismatched certs).
- TLS version retry loop (TLS client → 1.2 → 1.1 → 1.0), reconnecting between attempts.
- `AUTH LOGIN` with `AUTH PLAIN` fallback.
- Expose the exact failure via `smtp_last_error()` and surface it in the admin "Send Test".
- Never call `curl_close()` (deprecated/no-op in PHP 8).

---

## 8. Admin panel

**Auth**: `/admin/login.php` — CSRF-protected form; `password_verify` against `admins`
table; fallback to `DEFAULT_ADMIN_PASSWORD` only until a real admin is created.
Session cookie hardened (`httponly`, `SameSite=Lax`). **Bug warning:** session flag must
use `isset()`/non-zero id so a successful login isn't treated as logged-out.

**Dashboard** (`admin/index.php` + `admin/api/dashboard.php`): stat cards (total,
pending, processing, confirmed, revenue, products), recent orders table, notification
integration status panel with **Send Test** buttons (`admin/api/notification_test.php`).
JS polls every 30s and re-renders stats/orders/status without reload.

**Orders** (`admin/orders.php`): list with badges; detail view (customer info, items,
totals, payment ref); update status + payment (triggers payment/status emails when
changed, detected by comparing the pre-update order); delete.

**Products** (`admin/products.php` + `admin/api/products.php`): seamless CRUD:
- GET lists all products; POST saves (create/update); POST `{action:delete,id}` deletes.
- Table: thumbnail (handles local path AND Cloudinary URL), name, category, price,
  featured, stock, Edit/Delete buttons; live search + All/Active/Inactive tabs with counts.
- Add/Edit modal with **image upload** (`admin/api/upload.php` → Cloudinary) + live
  preview; save via fetch, toast feedback, re-render from API (no page reload).
- Always `clear_products_cache()` after any write.

**Settings** (`admin/settings.php`): Store & Checkout (bank details, delivery fee,
WhatsApp, TikTok, address, payment instructions), Rivo (api key, webhook secret,
enabled, webhook URL display), Email & Telegram (SMTP fields + all notification
toggles + Send Test), Admin Account (set username/password, hash with `password_hash`).

**Mobile responsive**: slide-in sidebar drawer + hamburger on every page, responsive
tables (scroll), modals, buttons, forms; shared `admin/admin.js` (toast + menu).

---

## 9. SEO / AEO / OG (every page)

`seo_head()` outputs: `<meta charset>` FIRST, viewport, title, description, keywords,
robots, canonical, Google/Bing verification, OpenGraph (`site_name`, `type`, `title`,
`description`, `url`, `image` + **`image:width`/`image:height`/`image:type`**, `image:alt`,
`locale`), Twitter (`summary_large_image`, site, title, description, image + width/height),
JSON-LD blocks, product price/currency for product pages.

JSON-LD: `Organization` (+ `sameAs` socials incl. TikTok), `WebSite` (+ `SearchAction`),
`Product` (with `Offer` price/availability/seller), `BreadcrumbList` (product pages),
`ItemList` (shop).

Assets: full favicon set (`.ico`, `.svg`, 16/32/192 PNG, apple-touch-icon), `site.webmanifest`,
1200×630 OG image, single `<h1>` per page, `scroll-margin-top` for sticky-header anchors,
**cache-busted asset URLs** (`?v=<filemtime>`) to defeat 1-year CSS/JS caching.

---

## 10. Security checklist

- Secrets only in `.env` (gitignored); `config.php` committed but secret-free.
- Block `.env`, `.sql`, `.log`, `/includes/`, `/cache/`, `/logs/`, `/database/` from web.
- CSRF token on every admin form + API (`X-CSRF-Token` header / field).
- Server-side price recomputation; validate all inputs; `htmlspecialchars` output.
- HMAC verify: Rivo webhook and GitHub webhook; rate-limit webhook deploys with a lock file.
- `password_hash`/`password_verify`; hardened sessions; service_role key server-side only.
- Error handling: errors ALWAYS logged (`error_log`/`logs/php-error.log`); shown on screen
  only when `APP_DEBUG=1`; PHP-version guard + `str_starts_with/str_contains` polyfills;
  required-extensions check (curl, json, mbstring, openssl) with a clear message.
- Webhook deploy (`/wh`): verify `X-Hub-Signature-256` (HMAC-SHA256 with `WEBHOOK_SECRET`),
  respond `pong` to `ping`, run `git pull` only on `push`, lock to prevent concurrency.

---

## 11. SPA engine (`assets/spa.js`) — progressive enhancement

- Intercept internal `<a>` clicks (skip `#`, external, `_blank`, mailto/tel, `data-hard`).
- Intercept GET `[data-spa]` forms (search).
- `navigate()`: fetch full HTML with `X-Requested-With`, parse with `DOMParser`, swap
  `#app` innerHTML, swap `<head>` (title, description, canonical, OG/Twitter, JSON-LD),
  `history.pushState` (preserve hash), scroll to top or hash target, close mobile menu.
- Anchor URLs: pure `#id` → native scroll; same-path `url#id` → smooth scroll + replaceState;
  cross-page `url#id` → navigate then scroll.
- Progress bar + content fade during load; hard-fallback to full navigation on any error.
- Re-bind via event delegation for elements that swap (contact form, product filter).
- Product "Add" buttons use inline `onclick="addToCart(id, ..., ...)"` with values
  JSON-encoded and HTML-escaped (`e_js()` = `htmlspecialchars(json_encode(...))`) so
  names with apostrophes/quotes don't break the attribute.

---

## 12. Cloudinary (images + assets)

`includes/cloudinary.php`:
- Parse `CLOUDINARY_URL`; signed upload (`timestamp`, sorted-params + secret SHA1,
  multipart `CURLFile`); return `secure_url`.
- `cloudinary_url(public_id, ext, transform)` and `cloudinary_asset(public_id, ext,
  localFallback, transform)` — used for favicon set, apple-touch-icon, OG image,
  hero + benefits images (`q_auto,f_auto`), with local fallback when not configured.
- Admin product upload endpoint validates MIME + 5MB limit.
- Storefront `product_img()` renders local paths OR Cloudinary URLs.

---

## 13. Implementation gotchas (learned)

1. Logo must NOT be `<h1>` (multiple H1s hurt SEO); keep exactly one `<h1>` per page.
2. Inline `onclick` with product names: escape JSON for HTML attributes (`e_js()`), not
   `addslashes` alone.
3. Empty-string settings override `??` defaults (e.g. `telegram_api_base=''` broke the
   URL) — treat empty as "use default".
4. Admin login session must use `isset()` (not `!empty(0)`) or login loops back.
5. `.env` and `config.php` are separate: config.php committed (no secrets), `.env` not.
6. Mailers: send `STARTTLS` before enabling crypto; handle multi-line SMTP; SNI/peer_name.
7. `curl_close()` is deprecated in PHP 8.5 — don't call it.
8. Cache-bust CSS/JS with filemtime to avoid stale 1-year caches behind a CDN.
9. Webhook/Rivo confirmation paths can both fire — guard notifications with a
   pre-update payment_status check.
10. SPA-swapped content loses direct event listeners — use event delegation or re-init.

---

## 14. Deployment

1. `database/schema.sql` → Supabase SQL editor.
2. On server: `git clone` / `git pull`; create `.env` from `.env.example` with real keys.
3. Point Apache docroot at the project root (`.htaccess` included) or run
   `php -S localhost:8000 router.php`.
4. `/admin/login.php` → set real admin password in Settings.
5. Configure Store (bank details, delivery), Email/Telegram (SMTP + toggles), Rivo, Cloudinary.
6. GitHub webhook → `/wh` with the matching `WEBHOOK_SECRET` to auto-deploy on push.

**Success criteria:** customer can add to cart → checkout (bank or Rivo) → order stored
in Supabase → customer + admin notified → admin sees live stats and updates status
(triggering more emails) → site is SEO/OG/mobile ready and deployable by `git pull`.