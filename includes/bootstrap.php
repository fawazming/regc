<?php
/**
 * REGC — Bootstrap.
 * Loads configuration, helpers and the Supabase client.
 *
 * Order matters: PHP-version guards, string polyfills and error reporting are
 * set up BEFORE anything else is loaded so that even a fatal in config/dotenv
 * is displayed (when APP_DEBUG=1) and/or logged — never a silent 500.
 */

/* ----------------------------------------------------------------
   0. PHP version guard + polyfills (must run before any other code)
   ---------------------------------------------------------------- */
if (PHP_VERSION_ID < 70400) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Royale Experience Global Concept requires PHP 7.4 or newer. Current PHP: " . PHP_VERSION;
    exit;
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

/* ----------------------------------------------------------------
   1. Load .env so APP_DEBUG is known before configuring errors
   ---------------------------------------------------------------- */
require_once __DIR__ . '/dotenv.php';
$envPath = dirname(__DIR__) . '/.env';
if (!is_file($envPath)) {
    error_log('[REGC] .env file not found — using default config. Create .env from .env.example with your real keys.');
}
env_load($envPath);

/* ----------------------------------------------------------------
   2. Error reporting — configured BEFORE config.php can fail
   ---------------------------------------------------------------- */
$APP_DEBUG = in_array(strtolower((string)env('APP_DEBUG', '0')), ['1', 'true', 'on', 'yes'], true);

if ($APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
ini_set('log_errors', '1');

$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir) && !@mkdir($logDir, 0775, true) && !is_dir($logDir)) {
    $logDir = null; // logs/ not writable — fall back to the default error log
}
if ($logDir !== null) {
    @ini_set('error_log', $logDir . '/php-error.log');
}

/* ----------------------------------------------------------------
   2b. Required PHP extensions — fail loudly with a clear message
   ---------------------------------------------------------------- */
$requiredExtensions = ['curl', 'json', 'mbstring', 'openssl'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}
if ($missingExtensions) {
    $msg = 'Missing required PHP extension(s): ' . implode(', ', $missingExtensions)
        . '. Install/enable them in your PHP config (e.g. extension=curl).';
    error_log('[REGC] ' . $msg);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $APP_DEBUG ? $msg : 'Server configuration error.';
    exit;
}

/* ----------------------------------------------------------------
   3. Application configuration
   ---------------------------------------------------------------- */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/cloudinary.php';

/* ----------------------------------------------------------------
   4. Error handlers — log with request context, display in debug
   ---------------------------------------------------------------- */
function bootstrap_error_log(string $level, string $message, string $file, int $line): void
{
    // Always use error_log() so failures are recorded even if logs/ is unwritable.
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $level . ': ' . $message
        . ' in ' . $file . ':' . $line
        . ' | ' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? '')
        . ' | ' . ($_SERVER['REMOTE_ADDR'] ?? '');
    error_log($entry);
}

set_exception_handler(function ($e) {
    bootstrap_error_log('UNCAUGHT EXCEPTION', $e->getMessage(), $e->getFile(), $e->getLine());
    http_response_code(500);
    if (defined('APP_DEBUG') && APP_DEBUG) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        echo '<pre style="font:13px monospace;background:#1d1d1d;color:#f66;padding:16px;border-radius:8px;max-width:900px;margin:20px auto">'
            . e(get_class($e)) . ': ' . e($e->getMessage()) . "\n\n"
            . e($e->getTraceAsString())
            . '</pre>';
    }
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        bootstrap_error_log('FATAL', $err['message'], $err['file'], $err['line']);
    }
});

/* Server-side session with hardened cookie */
if (session_status() === PHP_SESSION_NONE) {
    session_name('regc_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ----------------------------------------------------------------
   5. Global helpers backed by Supabase
   ---------------------------------------------------------------- */

function db(): Supabase
{
    static $db = null;
    if ($db === null) {
        $db = new Supabase(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    }
    return $db;
}

/** Fetch active products (optionally just by id list). */
function get_products(array $ids = []): array
{
    $query = [
        'select' => 'id,name,slug,category,price,old_price,description,short_description,image,badge,featured,active,stock',
        'active' => 'eq.true',
        'order' => 'featured.desc,created_at.asc',
    ];
    if (!empty($ids)) {
        $query['id'] = 'in.(' . implode(',', $ids) . ')';
    }

    // Short-lived cache (60s) so the storefront doesn't hit Supabase on every
    // request. Invalidated automatically by the admin product editor.
    $cacheFile = dirname(__DIR__) . '/cache/products.json';
    if (empty($ids) && is_file($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
        $cached = @json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $res = db()->select('products', $query);
    $data = $res['data'] ?? [];

    if (empty($ids) && !isset($res['error'])) {
        @file_put_contents($cacheFile, json_encode($data));
    }
    return $data;
}

/** Clear the cached product list (call after admin product writes). */
function clear_products_cache(): void
{
    @unlink(dirname(__DIR__) . '/cache/products.json');
}

/** Resolve a product image to an absolute URL (local path or Cloudinary URL). */
function product_img(?string $image): string
{
    if ($image === null || $image === '') {
        return abs_url('products/garri.jpg');
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return abs_url(ltrim($image, '/'));
}

/**
 * Cache-busting version for an asset: the file's mtime, so browsers/CDNs
 * refetch CSS/JS whenever a deployed file changes (fixes stale 1-year cache).
 */
function asset_version(string $file): string
{
    $f = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($file, '/');
    return is_file($f) ? (string)filemtime($f) : '1';
}

/** Fetch a single active product by slug. */
function get_product(string $slug): ?array
{
    $res = db()->select('products', [
        'select' => 'id,name,slug,category,price,old_price,description,short_description,image,badge,featured,active,stock',
        'slug' => 'eq.' . $slug,
        'active' => 'eq.true',
        'limit' => '1',
    ]);
    return $res['data'][0] ?? null;
}

/** Fetch products by category (slug-safe). */
function get_products_by_category(string $category): array
{
    $res = db()->select('products', [
        'select' => 'id,name,slug,category,price,old_price,description,short_description,image,badge,featured,active,stock',
        'category' => 'eq.' . $category,
        'active' => 'eq.true',
        'order' => 'price.asc',
    ]);
    return $res['data'] ?? [];
}

/** Single source of truth for the store's bank/account details. */
function store_bank_details(): array
{
    $s = get_settings();
    return [
        'bank_name'      => $s['bank_name'] ?? '',
        'account_name'   => $s['account_name'] ?? '',
        'account_number' => $s['account_number'] ?? '',
        'payment_instructions' => $s['payment_instructions'] ?? '',
    ];
}

/** Public settings needed by the storefront (safe subset). */
function public_config(): array
{
    $s = get_settings();
    return array_merge(store_bank_details(), [
        'delivery_fee'   => (float)($s['delivery_fee'] ?? 0),
        'rivo_enabled'   => (bool)($s['rivo_enabled'] ?? false),
        'site_whatsapp'  => $s['site_whatsapp'] ?? '',
        'site_address'   => $s['site_address'] ?? '',
        'site_tiktok'    => $s['site_tiktok'] ?? 'https://www.tiktok.com/@royaleexperienceglobalconcept',
    ]);
}