<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_admin();

$alert = '';
$viewId = (int)($_GET['view'] ?? 0);

// Handle status update
if (is_post() && ($_POST['action'] ?? '') === 'update_status') {
    csrf_verify();
    $id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $payment = $_POST['payment_status'];
    $allowed = ['pending', 'confirmed', 'processing', 'cancelled'];
    $allowedPay = ['unpaid', 'processing', 'paid', 'failed'];
    if (in_array($status, $allowed) && in_array($payment, $allowedPay)) {
        // Fetch the order first so we can detect changes and notify once.
        $r = db()->select('orders', ['select' => '*', 'id' => 'eq.' . $id, 'limit' => '1']);
        $old = $r['data'][0] ?? null;

        db()->update('orders', ['status' => $status, 'payment_status' => $payment], ['id' => 'eq.' . $id]);

        $notified = [];
        if ($old) {
            $items = normalize_order_items($old);
            // Payment marked paid → "payment received" to customer (+ admin)
            if (($old['payment_status'] ?? '') !== 'paid' && $payment === 'paid') {
                send_payment_notifications($old, $items);
                $notified[] = 'payment';
            }
            // Order status changed → status update to customer
            if (($old['status'] ?? '') !== $status && $status !== 'pending') {
                send_status_notification($old, $items, $status);
                $notified[] = 'status';
            }
        }

        $msg = 'Order updated.' . ($notified ? ' Emails sent: ' . implode(', ', $notified) . '.' : '');
        $alert = ['type' => 'success', 'msg' => $msg];
    }
}

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)$_POST['order_id'];
    db()->delete('orders', ['id' => 'eq.' . $id]);
    redirect('orders.php');
}

// List all orders
$res = db()->select('orders', ['select' => '*', 'order' => 'created_at.desc', 'limit' => '500']);
$orders = $res['data'] ?? [];

// Detail view
$order = null;
if ($viewId) {
    $r = db()->select('orders', ['select' => '*', 'id' => 'eq.' . $viewId, 'limit' => '1']);
    $order = $r['data'][0] ?? null;
    if ($order && is_string($order['items'])) $order['items'] = json_decode($order['items'], true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | <?= e(APP_NAME) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <aside class="sidebar">
        <div class="side-brand"><div class="icon">R</div><div><strong><?= e(APP_NAME) ?></strong><span>ADMIN</span></div></div>
        <nav>
            <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="orders.php" class="active"><i class="fa-solid fa-bag-shopping"></i> Orders</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="https://app.supabase.com" target="_blank"><i class="fa-solid fa-database"></i> Database</a>
        </nav>
        <form method="post" class="logout-form"><input type="hidden" name="action" value="logout"><button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</button></form>
    </aside>
    <div class="side-overlay"></div>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <h1><?= $order ? 'Order Details' : 'Orders' ?></h1>
        </header>

        <?php if (is_array($alert)): ?><div class="alert <?= $alert['type'] ?>"><?= e($alert['msg']) ?></div><?php endif; ?>

        <?php if ($order): ?>
            <a class="back-link" href="orders.php">&larr; Back to all orders</a>
            <div class="detail-grid">
                <div class="detail-block">
                    <h3>Order <?= e($order['order_no']) ?></h3>
                    <p><strong>Customer:</strong> <?= e($order['name']) ?></p>
                    <p><strong>Email:</strong> <?= e($order['email']) ?></p>
                    <p><strong>Phone:</strong> <?= e($order['phone']) ?></p>
                    <p><strong>Address:</strong> <?= e($order['address']) ?></p>
                    <?php if (!empty($order['note'])): ?><p><strong>Note:</strong> <?= e($order['note']) ?></p><?php endif; ?>
                    <p><strong>Placed:</strong> <?= e(date('M j, Y g:i a', strtotime($order['created_at']))) ?></p>
                    <?php if (!empty($order['payment_ref'])): ?><p><strong>Payment Ref:</strong> <?= e($order['payment_ref']) ?></p><?php endif; ?>
                </div>

                <div class="detail-block">
                    <h3>Items & Totals</h3>
                    <?php if (is_array($order['items'])): ?>
                        <?php foreach ($order['items'] as $it): ?>
                            <p><?= e($it['name']) ?> × <?= (int)$it['quantity'] ?> — <strong>₦<?= number_format((float)$it['price'] * (int)$it['quantity']) ?></strong></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <p style="margin-top:10px;border-top:1px solid #eee;padding-top:10px"><strong>Subtotal:</strong> ₦<?= number_format((float)$order['subtotal']) ?></p>
                    <p><strong>Delivery:</strong> ₦<?= number_format((float)$order['delivery_fee']) ?></p>
                    <p><strong style="font-size:18px">Total:</strong> <strong style="font-size:18px">₦<?= number_format((float)$order['total']) ?></strong></p>
                </div>
            </div>

            <div class="card" style="margin-top:24px">
                <h3 style="margin-bottom:14px">Update Status</h3>
                <form method="post" class="form-row">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                    <div class="form-group">
                        <label>Order Status</label>
                        <select name="status">
                            <?php foreach (['pending','processing','confirmed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status">
                            <?php foreach (['unpaid','processing','paid','failed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Save</button>
                </form>
                <form method="post" onsubmit="return confirm('Delete this order permanently?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                    <button class="btn btn-danger" type="submit">Delete Order</button>
                </form>
            </div>

        <?php else: ?>
            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Order No</th><th>Customer</th><th>Contact</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$orders): ?><tr><td colspan="8" style="text-align:center;color:#999">No orders yet.</td></tr><?php endif; ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong><?= e($o['order_no']) ?></strong></td>
                                <td><?= e($o['name']) ?></td>
                                <td><?= e($o['phone']) ?><br><small style="color:#999"><?= e($o['email']) ?></small></td>
                                <td>₦<?= number_format((float)$o['total']) ?></td>
                                <td><span class="badge <?= e($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></td>
                                <td><span class="badge <?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
                                <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                                <td><a class="mini-btn" href="orders.php?view=<?= (int)$o['id'] ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <script src="admin.js"></script>
</body>
</html>