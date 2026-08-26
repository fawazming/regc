<?php
/**
 * API: Rivo webhook endpoint.
 * Rivo POSTs successful payments here, signed with X-PGSP-Signature.
 *
 * Signature: X-PGSP-Signature: t=<ts>,v1=<hmac-sha256>
 * Verify by recomputing HMAC-SHA256 over timestamp + "." + body using
 * the webhook secret configured in Admin -> Settings.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/rivo.php';
require_once __DIR__ . '/../includes/notifications.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$signature = $_SERVER['HTTP_X_PGSP_SIGNATURE'] ?? '';
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

$config = public_config();
if (!$config['rivo_enabled']) {
    log_msg('Rivo webhook received but rivo disabled');
    http_response_code(200);
    echo json_encode(['ok' => true, 'ignored' => true]);
    exit;
}

$r = rivo();
if (!$r->verifyWebhook($signature, (string)$raw)) {
    log_msg('Rivo webhook signature verification failed');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
    exit;
}

// Determine reference from the payload (may be nested).
$reference = $payload['reference'] ?? ($payload['data']['reference'] ?? '');
$status = strtoupper($payload['status'] ?? '');

if ($reference === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing reference']);
    exit;
}

// Find matching order by payment_ref.
$res = db()->select('orders', [
    'select' => '*',
    'payment_ref' => 'eq.' . $reference,
    'limit' => '1',
]);
$order = $res['data'][0] ?? null;

if ($order) {
    if ($status === 'SUCCESS') {
        db()->update('orders', [
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ], ['order_no' => 'eq.' . $order['order_no']]);
        // Notify once: only if payment wasn't already marked paid (webhook + return may both fire).
        if (($order['payment_status'] ?? '') !== 'paid') {
            send_payment_notifications($order, normalize_order_items($order));
        }
        log_msg('Rivo webhook confirmed payment', ['order_no' => $order['order_no'], 'reference' => $reference]);
    } else {
        log_msg('Rivo webhook status', ['status' => $status, 'reference' => $reference]);
    }
} else {
    log_msg('Rivo webhook: no matching order for reference', ['reference' => $reference]);
}

http_response_code(200);
echo json_encode(['ok' => true]);