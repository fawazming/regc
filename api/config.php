<?php
/**
 * API: Public configuration for the storefront.
 * Returns only safe, public settings (bank details, delivery fee, rivo flag).
 * GET api/config.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode([
    'ok' => true,
    'config' => public_config(),
]);