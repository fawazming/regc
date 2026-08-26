<?php
/**
 * REGC — Admin dashboard data (JSON).
 * GET /admin/api/dashboard.php
 * Returns live stats, recent orders and notification config status so the
 * dashboard can refresh without a full page reload.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_admin_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// --- Stats ---
$resOrders = db()->select('orders', ['select' => 'id,order_no,name,email,total,status,payment_status,created_at', 'order' => 'created_at.desc', 'limit' => '500']);
$orders = $resOrders['data'] ?? [];

$stats = ['total' => 0, 'pending' => 0, 'processing' => 0, 'confirmed' => 0, 'cancelled' => 0, 'revenue' => 0, 'products' => 0];
foreach ($orders as $o) {
    $stats['total']++;
    $st = $o['status'] ?? '';
    if (isset($stats[$st])) $stats[$st]++;
    if ($st === 'confirmed') $stats['revenue'] += (float)($o['total'] ?? 0);
}
$stats['products'] = count(get_products());

// --- Recent orders (8) ---
$recent = array_slice($orders, 0, 8);
$recentRows = [];
foreach ($recent as $o) {
    $recentRows[] = [
        'id' => (int)$o['id'],
        'order_no' => $o['order_no'],
        'name' => $o['name'],
        'total' => (float)$o['total'],
        'payment_status' => $o['payment_status'] ?? 'unpaid',
        'status' => $o['status'] ?? 'pending',
        'date' => date('M j, Y', strtotime($o['created_at'])),
    ];
}

// --- Notification status ---
$cfg = notif_config();
$emailOk = $cfg['smtp_host'] !== '' && $cfg['admin_email'] !== '';
$telegramOk = $cfg['telegram_bot_token'] !== '' && $cfg['telegram_chat_id'] !== '';

json_out([
    'ok' => true,
    'stats' => $stats,
    'recent' => $recentRows,
    'notifications' => [
        'email_configured' => $emailOk,
        'email_host' => $cfg['smtp_host'],
        'admin_email' => $cfg['admin_email'],
        'telegram_configured' => $telegramOk,
    ],
    'last_updated' => date('H:i:s'),
]);