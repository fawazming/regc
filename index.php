<?php
/**
 * REGC — Front controller / router.
 * All pretty URLs resolve here (see .htaccess).
 *
 * Routes:
 *   /                    home (brand landing)
 *   /shop                ecommerce storefront
 *   /shop/category/<cat> category listing
 *   /product/<slug>      product detail
 *   /privacy, /terms, /cookies   legal pages
 *   /sitemap.xml         dynamic sitemap
 *   /robots.txt          dynamic robots
 *   /wh                  GitHub webhook deploy (git pull)
 *
 * Existing flat entry points (admin/, api/, assets/, products/) are served
 * directly by Apache because .htaccess only rewrites non-file requests.
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/seo.php';

// Parse the requested path (strip query string).
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rawurldecode($uri);
$path = trim($path, '/');
if ($path === 'index.php') {
    $path = '';
}

// Normalize base path: if the site lives in a subfolder, strip it.
$base = trim(parse_url(APP_BASE_URL, PHP_URL_PATH) ?? '', '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
    $path = trim($path, '/');
}

$segments = $path === '' ? [] : explode('/', $path);

/* ---------------- GitHub deploy webhook ---------------- */
if (($segments[0] ?? '') === 'wh' || ($segments[0] ?? '') === 'github-webhook') {
    require_once __DIR__ . '/includes/webhook.php';
    webhook_handle();
    exit;
}

/* ---------------- SEO / special files ---------------- */
switch ($segments[0] ?? '') {
    case 'sitemap.xml':
    case 'sitemap':
        require __DIR__ . '/pages/sitemap.php';
        exit;

    case 'robots.txt':
    case 'robots':
        require __DIR__ . '/pages/robots.php';
        exit;

    case 'privacy':
    case 'privacy-policy':
        $_GET['page'] = 'privacy';
        require __DIR__ . '/pages/legal.php';
        exit;

    case 'terms':
    case 'terms-of-service':
        $_GET['page'] = 'terms';
        require __DIR__ . '/pages/legal.php';
        exit;

    case 'cookies':
    case 'cookie-policy':
        $_GET['page'] = 'cookies';
        require __DIR__ . '/pages/legal.php';
        exit;

    case 'product':
        if (!empty($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/pages/product.php';
            exit;
        }
        break;

    case 'shop':
        // /shop or /shop/category/<cat>
        if (($segments[1] ?? '') === 'category' && !empty($segments[2])) {
            $_GET['category'] = $segments[2];
        } elseif (isset($segments[1]) && $segments[1] !== 'category') {
            $_GET['category'] = $segments[1];
        }
        require __DIR__ . '/pages/shop.php';
        exit;
}

/* ---------------- Home ---------------- */
require __DIR__ . '/pages/home.php';