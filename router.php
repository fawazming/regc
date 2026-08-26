<?php
/**
 * REGC — PHP built-in server router (for local testing without Apache).
 * Run:  php -S localhost:8000 router.php
 *
 * This mimics what .htaccess does on Apache: serve real public files directly
 * and route everything else through the front controller (index.php).
 * Sensitive/internal files are blocked from direct access.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Block sensitive files & internal directories
$base = basename($path);
$blockedBase = ['.env', '.env.example', '.gitignore', '.htaccess', 'composer.json', 'composer.lock'];
if (in_array($base, $blockedBase, true)) {
    http_response_code(403);
    exit('Forbidden');
}
if (preg_match('#\.(sql|log|bak)$#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
if (preg_match('#^/(includes|cache|logs|database)(/|$)#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // serve the real file (css, js, images, etc.)
}

require __DIR__ . '/index.php';