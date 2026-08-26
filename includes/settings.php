<?php
/**
 * REGC — Settings store.
 * Settings are stored as key/value rows in the `settings` table.
 * Values are cached in a local JSON file to keep the storefront fast.
 */

function settings_file(): string
{
    return dirname(__DIR__) . '/cache/settings.json';
}

function settings_cache_dir(): string
{
    $dir = dirname(__DIR__) . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/** Read all settings (cached to disk, refreshed when cache is older than $ttl seconds). */
function get_settings(int $ttl = 30): array
{
    $file = settings_file();
    settings_cache_dir();

    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $cached = @json_decode((string)file_get_contents($file), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $res = db()->select('settings');
    $rows = $res['data'] ?? [];
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }

    @file_put_contents($file, json_encode($settings));
    return $settings;
}

/** Persist settings to Supabase and refresh the disk cache. */
function save_settings(array $pairs): void
{
    foreach ($pairs as $key => $value) {
        $res = db()->update('settings', ['value' => (string)$value], ['key' => 'eq.' . $key]);
        if (isset($res['error']) || (isset($res['data']) && count($res['data']) === 0)) {
            // Row may not exist yet; upsert it.
            db()->request('POST', 'settings', ['on_conflict' => 'key'], [
                'key' => $key,
                'value' => (string)$value,
            ], true);
        }
    }
    get_settings(-1); // force refresh
}