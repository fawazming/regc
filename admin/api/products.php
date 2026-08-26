<?php
/**
 * REGC — Admin products API.
 *
 * GET  /admin/api/products.php                → list all products (JSON)
 * POST /admin/api/products.php                → save (create or update) product
 * POST /admin/api/products.php {action:delete,id:N} → delete product
 */
require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/* ---------------- GET: list all products ---------------- */
if (!is_post()) {
    $res = db()->select('products', ['select' => '*', 'order' => 'id.asc']);
    json_out(['ok' => true, 'products' => $res['data'] ?? []]);
}

csrf_verify();
$in = read_json_input();

/* ---------------- DELETE ---------------- */
if (($in['action'] ?? '') === 'delete') {
    $id = (int)($in['id'] ?? 0);
    if (!$id) {
        json_out(['ok' => false, 'error' => 'Product id required.'], 422);
    }
    $res = db()->delete('products', ['id' => 'eq.' . $id]);
    if (isset($res['error'])) {
        json_out(['ok' => false, 'error' => 'Delete failed', 'detail' => $res['error']], 500);
    }
    clear_products_cache();
    json_out(['ok' => true, 'id' => $id]);
}

/* ---------------- SAVE (create or update) ---------------- */
$id = !empty($in['id']) ? (int)$in['id'] : null;

$name = trim($in['name'] ?? '');
$price = (float)($in['price'] ?? 0);
if ($name === '' || $price <= 0) {
    json_out(['ok' => false, 'error' => 'Name and price are required.'], 422);
}

$row = [
    'name' => $name,
    'category' => $in['category'] ?? 'staples',
    'price' => $price,
    'old_price' => !empty($in['old_price']) ? (float)$in['old_price'] : null,
    'short_description' => $in['short_description'] ?? '',
    'description' => $in['description'] ?? '',
    'image' => $in['image'] ?? '',
    'badge' => $in['badge'] ?? '',
    'featured' => !empty($in['featured']),
    'active' => !empty($in['active']),
    'stock' => (int)($in['stock'] ?? 0),
];

if ($id) {
    $res = db()->update('products', $row, ['id' => 'eq.' . $id]);
    if (isset($res['error'])) {
        json_out(['ok' => false, 'error' => 'Update failed', 'detail' => $res['error']], 500);
    }
    clear_products_cache();
    json_out(['ok' => true, 'id' => $id]);
}

$row['slug'] = !empty($in['slug']) ? $in['slug'] : slugify($name) . '-' . random_str(4);
$res = db()->insert('products', $row);
if (isset($res['error'])) {
    json_out(['ok' => false, 'error' => 'Create failed', 'detail' => $res['error']], 500);
}
$created = $res['data'][0] ?? [];
clear_products_cache();
json_out(['ok' => true, 'id' => $created['id'] ?? null]);

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}