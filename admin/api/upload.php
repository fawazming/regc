<?php
/**
 * REGC — Admin image upload (Cloudinary).
 * POST /admin/api/upload.php  (multipart/form-data, field: file)
 * Returns { ok, url, public_id } — the URL is stored in the product's image field.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cloudinary.php';
require_admin_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!is_post()) {
    json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
}
csrf_verify();

if (empty($_FILES['file'])) {
    json_out(['ok' => false, 'error' => 'No file uploaded.'], 422);
}

$file = $_FILES['file'];
// Validate it's actually an image.
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
    json_out(['ok' => false, 'error' => 'Only JPG, PNG, WEBP or GIF images are allowed (got ' . ($mime ?: 'unknown') . ').'], 422);
}
if ($file['size'] > 5 * 1024 * 1024) {
    json_out(['ok' => false, 'error' => 'Image too large (max 5MB).'], 422);
}

$result = cloudinary_upload_from_request($file);
if (!$result['ok']) {
    json_out(['ok' => false, 'error' => $result['error']], 500);
}

json_out(['ok' => true, 'url' => $result['url'], 'public_id' => $result['public_id']]);