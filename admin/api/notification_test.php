<?php
/**
 * REGC — Admin notification test.
 * POST /admin/api/notification_test.php  { type: 'email' | 'telegram' | 'both' }
 * Sends a test message and returns a detailed, actionable result.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_admin_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!is_post()) json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
csrf_verify();

$in = read_json_input();
$type = $in['type'] ?? 'both';
$cfg = notif_config();

$result = ['email' => null, 'telegram' => null];

if ($type === 'email' || $type === 'both') {
    if ($cfg['smtp_host'] === '') {
        $result['email'] = ['ok' => false, 'error' => 'SMTP host not configured. Add it in Settings → Email & Telegram.'];
    } elseif ($cfg['admin_email'] === '') {
        $result['email'] = ['ok' => false, 'error' => 'Admin email not set. Add it in Settings → Email & Telegram.'];
    } else {
        $sent = send_mail($cfg, $cfg['admin_email'], 'Test email from ' . APP_NAME, '<p>Hello! This is a test notification from <strong>' . e(APP_NAME) . '</strong>. Email notifications are working correctly.</p>');
        $result['email'] = $sent
            ? ['ok' => true, 'error' => null]
            : ['ok' => false, 'error' => 'SMTP send failed: ' . (smtp_last_error() ?? 'see logs/app.log')];
    }
}

if ($type === 'telegram' || $type === 'both') {
    if ($cfg['telegram_bot_token'] === '' || $cfg['telegram_chat_id'] === '') {
        $result['telegram'] = ['ok' => false, 'error' => 'Telegram not configured (bot token + chat id required). Add them in Settings → Email & Telegram.'];
    } else {
        $sent = send_telegram($cfg, "✅ Test notification from " . APP_NAME . " — Telegram notifications are working.");
        $result['telegram'] = $sent
            ? ['ok' => true, 'error' => null]
            : ['ok' => false, 'error' => 'Telegram send failed. Check the bot token and chat id, and see logs/app.log.'];
    }
}

$allOk = ($result['email']['ok'] ?? true) && ($result['telegram']['ok'] ?? true);
json_out(['ok' => $allOk, 'result' => $result]);