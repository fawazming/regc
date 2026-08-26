<?php
/**
 * REGC — Cloudinary helper.
 * Parses CLOUDINARY_URL (cloudinary://api_key:api_secret@cloud_name) from .env
 * and provides signed image uploads + delivery URLs.
 */

function cloudinary_config(): array
{
    $url = (string)env('CLOUDINARY_URL', '');
    $config = ['cloud_name' => '', 'api_key' => '', 'api_secret' => '', 'enabled' => false];
    if (preg_match('#^cloudinary://([^:]+):([^@]+)@([^/]+)$#i', trim($url), $m)) {
        $config = [
            'cloud_name' => $m[3],
            'api_key' => $m[1],
            'api_secret' => $m[2],
            'enabled' => true,
        ];
    }
    return $config;
}

function cloudinary_enabled(): bool
{
    return cloudinary_config()['enabled'];
}

/** Build a Cloudinary delivery URL for a public id (local or cloudinary input). */
function cloudinary_url(string $publicId, string $ext = 'jpg', string $transform = ''): string
{
    $c = cloudinary_config();
    $t = $transform !== '' ? trim($transform, '/') . '/' : '';
    return 'https://res.cloudinary.com/' . rawurlencode($c['cloud_name']) . '/image/upload/' . $t
        . ltrim($publicId, '/') . '.' . ltrim($ext, '.');
}

/** Detect whether a string is a Cloudinary (or any absolute http) URL. */
function is_cloudinary_url(string $url): bool
{
    return str_contains($url, 'res.cloudinary.com');
}

/**
 * Serve a known site asset via Cloudinary when configured, else locally.
 * @param string $publicId      e.g. 'regc/og-image'
 * @param string $ext           e.g. 'jpg'
 * @param string $localFallback root-relative local path e.g. '/assets/og-image.jpg'
 * @param string $transform     optional Cloudinary transform (e.g. 'q_auto,f_auto')
 */
function cloudinary_asset(string $publicId, string $ext, string $localFallback, string $transform = ''): string
{
    if (cloudinary_enabled()) {
        return cloudinary_url($publicId, $ext, $transform);
    }
    return abs_url($localFallback);
}

/**
 * Upload an image file to Cloudinary (signed).
 * @param string $filePath  local path (e.g. $_FILES tmp_name)
 * @param string $folder    folder in Cloudinary
 * @param string|null $publicId optional public id (without extension)
 * @return array ['ok'=>bool,'url'=>string|null,'public_id'=>string|null,'error'=>string|null]
 */
function cloudinary_upload_image(string $filePath, string $folder = 'regc', ?string $publicId = null): array
{
    $c = cloudinary_config();
    if (!$c['enabled']) {
        return ['ok' => false, 'url' => null, 'public_id' => null, 'error' => 'Cloudinary not configured (CLOUDINARY_URL missing).'];
    }
    if (!is_file($filePath)) {
        return ['ok' => false, 'url' => null, 'public_id' => null, 'error' => 'Upload file missing.'];
    }

    $timestamp = time();
    $params = ['timestamp' => $timestamp, 'folder' => $folder];
    if ($publicId !== null && $publicId !== '') {
        $params['public_id'] = $publicId;
    }

    // Signed params: sorted key=value& joined, then + secret, SHA1.
    ksort($params);
    $toSign = '';
    foreach ($params as $k => $v) {
        $toSign .= $k . '=' . $v . '&';
    }
    $toSign = rtrim($toSign, '&');
    $params['signature'] = sha1($toSign . $c['api_secret']);
    $params['api_key'] = $c['api_key'];

    $url = 'https://api.cloudinary.com/v1_1/' . rawurlencode($c['cloud_name']) . '/image/upload';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POSTFIELDS => array_merge(['file' => new CURLFile($filePath)], $params),
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $res = json_decode((string)$raw, true);
    if ($http >= 400 || !is_array($res) || empty($res['secure_url'])) {
        $msg = is_array($res) ? ($res['error']['message'] ?? json_encode($res)) : $raw;
        log_msg('Cloudinary upload failed', ['http' => $http, 'msg' => $msg]);
        return ['ok' => false, 'url' => null, 'public_id' => null, 'error' => $msg];
    }

    return [
        'ok' => true,
        'url' => $res['secure_url'],
        'public_id' => $res['public_id'] ?? null,
        'error' => null,
    ];
}

/** Upload a file from an uploaded HTTP file array. */
function cloudinary_upload_from_request(array $file, string $folder = 'regc', ?string $publicId = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'url' => null, 'public_id' => null, 'error' => 'Upload failed (code ' . ($file['error'] ?? '?') . ').'];
    }
    return cloudinary_upload_image($file['tmp_name'], $folder, $publicId);
}