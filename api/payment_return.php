<?php
/**
 * API: Rivo payment redirect return.
 * The customer lands here after Rivo payment (redirect_url).
 * We verify the payment server-side and update the order, then
 * redirect to a human-friendly confirmation page.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/rivo.php';
require_once __DIR__ . '/../includes/notifications.php';

$order_no = $_GET['order_no'] ?? '';
$reference = $_GET['reference'] ?? ($_GET['ref'] ?? '');

$order = null;
if ($order_no !== '') {
    $res = db()->select('orders', [
        'select' => '*',
        'order_no' => 'eq.' . $order_no,
        'limit' => '1',
    ]);
    $order = $res['data'][0] ?? null;
}

if (!$order) {
    redirect(APP_BASE_URL . '/shop?status=error');
}

$config = public_config();

// If we don't have a reference yet, ask Rivo.
if ($reference === '' && !empty($order['payment_ref'])) {
    $reference = $order['payment_ref'];
}

$paid = false;
if ($reference !== '' && $config['rivo_enabled']) {
    $verify = rivo()->verify($reference);
    $paid = strtoupper($verify['status'] ?? '') === 'SUCCESS';
    if ($paid) {
        db()->update('orders', ['payment_status' => 'paid', 'status' => 'confirmed'], ['order_no' => 'eq.' . $order_no]);
        // Notify once (webhook may also fire for the same reference).
        if (($order['payment_status'] ?? '') !== 'paid') {
            send_payment_notifications($order, normalize_order_items($order));
        }
    } else {
        log_msg('Rivo verify not success at return', ['verify' => $verify, 'order_no' => $order_no]);
    }
}

if ($paid) {
    redirect(APP_BASE_URL . '/shop?status=success&order_no=' . rawurlencode($order_no));
}
redirect(APP_BASE_URL . '/shop?status=payment&order_no=' . rawurlencode($order_no));