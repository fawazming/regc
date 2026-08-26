<?php
/**
 * API: Query the status of an order by its order number.
 * GET api/order_status.php?order_no=REGC-xxxxx
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$order_no = trim($_GET['order_no'] ?? '');
if ($order_no === '') {
    json_out(['ok' => false, 'error' => 'Order number is required.'], 422);
}

$res = db()->select('orders', [
    'select' => 'id,order_no,name,status,payment_status,payment_method,total,created_at',
    'order_no' => 'eq.' . $order_no,
    'limit' => '1',
]);

$order = $res['data'][0] ?? null;
if (!$order) {
    json_out(['ok' => false, 'error' => 'Order not found. Check the order number and try again.'], 404);
}

json_out(['ok' => true, 'order' => $order]);