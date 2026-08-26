<?php
/**
 * API: Place an order.
 * POST api/checkout.php
 *
 * Body:
 *   name, email, phone, address,
 *   items: [{id, quantity}],
 *   payment_method: 'bank' | 'rivo',
 *   note (optional)
 *
 * On success returns:
 *   { ok, order_no, order_id, total,
 *     payment_method, authorization_url (when rivo), bank (public account details) }
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/rivo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!is_post()) {
    json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$in = read_json_input();

$name = trim($in['name'] ?? '');
$email = trim($in['email'] ?? '');
$phone = trim($in['phone'] ?? '');
$address = trim($in['address'] ?? '');
$method = $in['payment_method'] ?? 'bank';
$note = trim($in['note'] ?? '');
$itemsRaw = $in['items'] ?? [];

// --- Validate ---
if ($name === '' || $email === '' || $phone === '' || $address === '') {
    json_out(['ok' => false, 'error' => 'Please fill in your name, email, phone and delivery address.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
}
if (!is_array($itemsRaw) || count($itemsRaw) === 0) {
    json_out(['ok' => false, 'error' => 'Your cart is empty.'], 422);
}

// --- Normalize items & validate against catalog ---
$ids = [];
foreach ($itemsRaw as $it) {
    if (!empty($it['id'])) {
        $ids[] = (int)$it['id'];
    }
}
$products = get_products($ids);
$byId = [];
foreach ($products as $p) {
    $byId[(int)$p['id']] = $p;
}

$items = [];
foreach ($itemsRaw as $it) {
    $id = (int)($it['id'] ?? 0);
    $qty = max(1, (int)($it['quantity'] ?? 1));
    if (!isset($byId[$id])) {
        json_out(['ok' => false, 'error' => 'One or more products are unavailable.'], 422);
    }
    $items[] = [
        'id' => $id,
        'name' => $byId[$id]['name'],
        'price' => (float)$byId[$id]['price'],
        'quantity' => $qty,
        'image' => $byId[$id]['image'] ?? '',
    ];
}

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['price'] * $it['quantity'];
}

$config = public_config();
$deliveryFee = (float)$config['delivery_fee'];
$total = $subtotal + $deliveryFee;

$order_no = generate_order_no();

$orderRow = [
    'order_no' => $order_no,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'subtotal' => $subtotal,
    'delivery_fee' => $deliveryFee,
    'total' => $total,
    'currency' => 'NGN',
    'payment_method' => $method === 'rivo' ? 'rivo' : 'bank',
    'status' => 'pending',
    'payment_status' => 'unpaid',
    'items' => $items,
    'note' => $note !== '' ? $note : null,
];

$res = db()->insert('orders', $orderRow);
if (isset($res['error'])) {
    log_msg('Order insert failed', ['error' => $res['error']]);
    json_out(['ok' => false, 'error' => 'Could not place your order. Please try again.'], 500);
}
$created = $res['data'][0] ?? $orderRow;
$order_id = (int)($created['id'] ?? 0);

// --- Rivo flow (optional) ---
$authorization_url = null;
if ($method === 'rivo' && $config['rivo_enabled']) {
    $r = rivo();
    $redirect = APP_BASE_URL . '/api/payment_return.php?order_no=' . rawurlencode($order_no);
    $createRes = $r->create([
        'amount' => round($total),
        'email' => $email,
        'redirect_url' => $redirect,
        'idempotency_key' => $order_no,
    ]);
    if (!empty($createRes['authorization_url'])) {
        $authorization_url = $createRes['authorization_url'];
        $reference = $createRes['reference'] ?? null;
        db()->update('orders', ['payment_ref' => $reference, 'payment_status' => 'processing'], ['order_no' => 'eq.' . $order_no]);
    } else {
        log_msg('Rivo create failed', ['res' => $createRes]);
    }
}

// Send notifications (customer + admin) unless Rivo is waiting on redirect.
// For Rivo we still notify immediately; payment is confirmed via webhook.
send_order_notifications($created, $items);

json_out([
    'ok' => true,
    'order_no' => $order_no,
    'order_id' => $order_id,
    'total' => $total,
    'subtotal' => $subtotal,
    'delivery_fee' => $deliveryFee,
    'payment_method' => $method,
    'authorization_url' => $authorization_url,
    'bank' => [
        'bank_name' => $config['bank_name'],
        'account_name' => $config['account_name'],
        'account_number' => $config['account_number'],
        'instructions' => $config['payment_instructions'],
    ],
]);