<?php
/**
 * REGC — Dynamic XML sitemap.
 * Pretty URL: /sitemap.xml
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/seo.php';

$products = get_products();

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$urls = [
    ['loc' => abs_url(''), 'priority' => '1.0', 'freq' => 'weekly'],
    ['loc' => abs_url('shop'), 'priority' => '0.9', 'freq' => 'daily'],
    ['loc' => abs_url('privacy'), 'priority' => '0.3', 'freq' => 'yearly'],
    ['loc' => abs_url('terms'), 'priority' => '0.3', 'freq' => 'yearly'],
    ['loc' => abs_url('cookies'), 'priority' => '0.3', 'freq' => 'yearly'],
];

// Category URLs
$cats = [];
foreach ($products as $p) {
    $cats[strtolower($p['category'] ?? 'staples')] = true;
}
foreach (array_keys($cats) as $c) {
    $urls[] = ['loc' => abs_url('shop/category/' . rawurlencode($c)), 'priority' => '0.7', 'freq' => 'weekly'];
}

// Product URLs
foreach ($products as $p) {
    $urls[] = ['loc' => abs_url('product/' . $p['slug']), 'priority' => '0.8', 'freq' => 'weekly'];
}

foreach ($urls as $u) {
    echo "\t<url>\n";
    echo "\t\t<loc>", e($u['loc']), "</loc>\n";
    echo "\t\t<changefreq>", $u['freq'], "</changefreq>\n";
    echo "\t\t<priority>", $u['priority'], "</priority>\n";
    echo "\t</url>\n";
}

echo '</urlset>' . "\n";