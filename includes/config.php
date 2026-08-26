<?php
/**
 * REGC — Configuration
 * Reads secrets from the .env file (see .env.example). You can also hard-code
 * values here, but .env is preferred so secrets are not committed.
 */

require_once __DIR__ . '/dotenv.php';
env_load(dirname(__DIR__) . '/.env');

define('APP_NAME', env('APP_NAME', 'Royale Experience Global Concept'));
define('APP_TAGLINE', env('APP_TAGLINE', 'Premium Foodstuff Delivery'));
define('APP_BASE_URL', rtrim((string)env('APP_BASE_URL', 'http://localhost/regc'), '/'));

/* -------------------------
   SUPABASE (database)
   -------------------------
   Find these in your Supabase project: Project Settings -> API.
*/
define('SUPABASE_URL', env('SUPABASE_URL', 'https://YOUR-PROJECT.supabase.co'));
define('SUPABASE_ANON_KEY', env('SUPABASE_ANON_KEY', 'YOUR-ANON-KEY'));
define('SUPABASE_SERVICE_KEY', env('SUPABASE_SERVICE_KEY', 'YOUR-SERVICE-ROLE-KEY'));

/* Temporary admin password used ONLY until you set one in Admin -> Settings. */
define('DEFAULT_ADMIN_PASSWORD', env('DEFAULT_ADMIN_PASSWORD', 'change-me-now'));

/* -------------------------
   SEO / brand
   ------------------------- */
define('APP_DESCRIPTION', env('APP_DESCRIPTION', 'Premium foodstuff delivery you can trust. Fresh produce, pantry essentials, quality oils and exclusive Ramadan food packs — we shop, we pack, we deliver, you relax.'));
define('APP_KEYWORDS', env('APP_KEYWORDS', 'foodstuff, foodstuff delivery, fresh produce, pantry essentials, rice, beans, garri, palm oil, Ramadan food packs, gift hampers, groceries, Nigeria, REGC'));
define('APP_OG_IMAGE', env('APP_OG_IMAGE', '/assets/og-image.jpg'));
define('APP_TWITTER_HANDLE', env('APP_TWITTER_HANDLE', '@regc_ng'));
define('ORG_NAME', env('ORG_NAME', 'Royale Experience Global Concept'));
define('ORG_EMAIL', env('ORG_EMAIL', 'hello@royaleexperience.com.ng'));
define('ORG_PHONE', env('ORG_PHONE', '+2348166978348'));
define('ORG_ADDRESS', env('ORG_ADDRESS', 'Lagos, Nigeria'));
define('ORG_FOUNDED', env('ORG_FOUNDED', '2024'));

define('GOOGLE_SITE_VERIFICATION', env('GOOGLE_SITE_VERIFICATION', ''));
define('BING_SITE_VERIFICATION', env('BING_SITE_VERIFICATION', ''));

/*
 * Debug mode.
 * Set APP_DEBUG=1 in .env ONLY to diagnose an error (e.g. HTTP 500).
 * When enabled, PHP errors are shown on screen and logged verbosely.
 * When disabled (production), errors are hidden but still written to
 * logs/php-error.log so you can diagnose without exposing internals.
 */
define('APP_DEBUG', in_array(strtolower((string)env('APP_DEBUG', '0')), ['1', 'true', 'on', 'yes'], true));
define('PHP_ERROR_LOG', dirname(__DIR__) . '/logs/php-error.log');

/* Timezone */
date_default_timezone_set('Africa/Lagos');