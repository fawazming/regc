<?php
/**
 * REGC — Dynamic robots.txt.
 * Pretty URL: /robots.txt
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /wh\n";
echo "\n";
echo "Sitemap: ", e(abs_url('sitemap.xml')), "\n";