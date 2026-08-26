<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$alert = '';

if (is_post()) {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_general') {
        save_settings([
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_name' => trim($_POST['account_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'payment_instructions' => trim($_POST['payment_instructions'] ?? ''),
            'delivery_fee' => (float)($_POST['delivery_fee'] ?? 0),
            'site_whatsapp' => trim($_POST['site_whatsapp'] ?? ''),
            'site_address' => trim($_POST['site_address'] ?? ''),
            'site_tiktok' => trim($_POST['site_tiktok'] ?? ''),
        ]);
        $alert = ['type' => 'success', 'msg' => 'Store settings saved.'];
    }

    if ($action === 'save_notifications') {
        save_settings([
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
            'smtp_user' => trim($_POST['smtp_user'] ?? ''),
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
            'mail_from' => trim($_POST['mail_from'] ?? ''),
            'mail_from_name' => trim($_POST['mail_from_name'] ?? APP_NAME),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'telegram_bot_token' => trim($_POST['telegram_bot_token'] ?? ''),
            'telegram_chat_id' => trim($_POST['telegram_chat_id'] ?? ''),
            // Notification toggles
            'notify_customer_order' => isset($_POST['notify_customer_order']) ? '1' : '0',
            'notify_customer_payment' => isset($_POST['notify_customer_payment']) ? '1' : '0',
            'notify_customer_status' => isset($_POST['notify_customer_status']) ? '1' : '0',
            'email_include_bank' => isset($_POST['email_include_bank']) ? '1' : '0',
            'notify_admin_email' => isset($_POST['notify_admin_email']) ? '1' : '0',
            'notify_admin_telegram' => isset($_POST['notify_admin_telegram']) ? '1' : '0',
            'notify_admin_payment' => isset($_POST['notify_admin_payment']) ? '1' : '0',
        ]);
        $alert = ['type' => 'success', 'msg' => 'Notification settings saved.'];
    }

    if ($action === 'save_rivo') {
        save_settings([
            'rivo_api_key' => trim($_POST['rivo_api_key'] ?? ''),
            'rivo_webhook_secret' => trim($_POST['rivo_webhook_secret'] ?? ''),
            'rivo_enabled' => isset($_POST['rivo_enabled']) ? '1' : '0',
        ]);
        $alert = ['type' => 'success', 'msg' => 'Rivo payment settings saved.'];
    }

    if ($action === 'set_password') {
        $np = $_POST['new_password'] ?? '';
        $username = trim($_POST['username'] ?? 'admin');
        if (strlen($np) < 8) {
            $alert = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } else {
            $hash = password_hash($np, PASSWORD_DEFAULT);
            $res = db()->select('admins', ['select' => 'id', 'username' => 'eq.' . $username, 'limit' => '1']);
            $existing = $res['data'][0] ?? null;
            if ($existing) {
                db()->update('admins', ['password_hash' => $hash], ['id' => 'eq.' . (int)$existing['id']]);
            } else {
                db()->insert('admins', [['username' => $username, 'password_hash' => $hash]]);
            }
            $alert = ['type' => 'success', 'msg' => 'Admin password updated. The default password no longer applies.'];
        }
    }

    if ($action === 'test_notification') {
        $config = notif_config();
        $okMail = false; $okTg = false;
        $mailErr = '';
        if (!empty($config['smtp_host']) && !empty($config['admin_email'])) {
            $okMail = send_mail($config, $config['admin_email'], 'Test from ' . APP_NAME, '<p>This is a test notification from ' . e(APP_NAME) . '.</p>');
            if (!$okMail) {
                $mailErr = smtp_last_error() ?? 'unknown SMTP error';
            }
        }
        if (!empty($config['telegram_bot_token'])) {
            $okTg = send_telegram($config, '✅ Test notification from ' . APP_NAME . ' — notifications are working.');
        }
        $msg = 'Test email: ' . ($okMail ? 'sent ✓' : ($mailErr !== '' ? 'FAILED — ' . $mailErr : 'not configured')) . '. Test Telegram: ' . ($okTg ? 'sent ✓' : 'not configured/failed') . '.';
        $alert = ['type' => $okMail || $okTg ? 'success' : 'error', 'msg' => $msg];
    }

    // refresh for display
    get_settings(-1);
}

$s = get_settings(-1);
$webhookUrl = APP_BASE_URL . '/api/rivo_webhook.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | <?= e(APP_NAME) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <aside class="sidebar">
        <div class="side-brand"><div class="icon">R</div><div><strong><?= e(APP_NAME) ?></strong><span>ADMIN</span></div></div>
        <nav>
            <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="orders.php"><i class="fa-solid fa-bag-shopping"></i> Orders</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="https://app.supabase.com" target="_blank"><i class="fa-solid fa-database"></i> Database</a>
        </nav>
        <form method="post" class="logout-form"><input type="hidden" name="action" value="logout"><button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</button></form>
    </aside>
    <div class="side-overlay"></div>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <h1>Settings</h1>
        </header>

        <?php if (is_array($alert)): ?><div class="alert <?= $alert['type'] ?>"><?= e($alert['msg']) ?></div><?php endif; ?>

        <!-- GENERAL / STORE -->
        <div class="card">
            <h2>Store & Checkout</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_general">
                <div class="form-grid">
                    <div class="form-group"><label>Bank Name</label><input name="bank_name" value="<?= e($s['bank_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Account Name</label><input name="account_name" value="<?= e($s['account_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Account Number</label><input name="account_number" value="<?= e($s['account_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>Delivery Fee (₦)</label><input name="delivery_fee" type="number" step="0.01" value="<?= e($s['delivery_fee'] ?? '1000') ?>"></div>
                    <div class="form-group"><label>WhatsApp Number</label><input name="site_whatsapp" value="<?= e($s['site_whatsapp'] ?? '') ?>"></div>
                    <div class="form-group"><label>TikTok URL</label><input name="site_tiktok" value="<?= e($s['site_tiktok'] ?? '') ?>" placeholder="https://www.tiktok.com/@..."></div>
                    <div class="form-group"><label>Store Address</label><input name="site_address" value="<?= e($s['site_address'] ?? '') ?>"></div>
                </div>
                <div class="form-group"><label>Payment Instructions (shown at checkout)</label><textarea name="payment_instructions" rows="3"><?= e($s['payment_instructions'] ?? '') ?></textarea></div>
                <button class="btn btn-primary" type="submit">Save Store Settings</button>
            </form>
        </div>

        <!-- RIVO -->
        <div class="card">
            <h2>Rivo Online Payments</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_rivo">
                <div class="form-group"><label>API Key (X-API-KEY)</label><input name="rivo_api_key" value="<?= e($s['rivo_api_key'] ?? '') ?>" placeholder="pgsk_live_..."></div>
                <div class="form-group"><label>Webhook Secret</label><input name="rivo_webhook_secret" value="<?= e($s['rivo_webhook_secret'] ?? '') ?>"></div>
                <div class="toggle-row">
                    <label><input type="checkbox" name="rivo_enabled" <?= !empty($s['rivo_enabled']) ? 'checked' : '' ?> value="1"> Enable online payment at checkout (bank transfer is always shown)</label>
                </div>
                <p style="font-size:13px;color:#888;margin-bottom:14px">Webhook URL to configure in Rivo:<br><code><?= e($webhookUrl) ?></code></p>
                <button class="btn btn-primary" type="submit">Save Rivo Settings</button>
            </form>
        </div>

        <!-- NOTIFICATIONS -->
        <div class="card">
            <h2>Email & Telegram Notifications</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_notifications">
                <div class="form-grid">
                    <div class="form-group"><label>SMTP Host</label><input name="smtp_host" value="<?= e($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"></div>
                    <div class="form-group"><label>SMTP Port</label><input name="smtp_port" value="<?= e($s['smtp_port'] ?? '587') ?>"></div>
                    <div class="form-group"><label>SMTP Username</label><input name="smtp_user" value="<?= e($s['smtp_user'] ?? '') ?>"></div>
                    <div class="form-group"><label>SMTP Password</label><input name="smtp_pass" type="password" value="<?= e($s['smtp_pass'] ?? '') ?>"></div>
                    <div class="form-group"><label>Security</label>
                        <select name="smtp_secure">
                            <option value="tls" <?= ($s['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
                            <option value="ssl" <?= ($s['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>From Email</label><input name="mail_from" value="<?= e($s['mail_from'] ?? '') ?>"></div>
                    <div class="form-group"><label>From Name</label><input name="mail_from_name" value="<?= e($s['mail_from_name'] ?? APP_NAME) ?>"></div>
                    <div class="form-group"><label>Admin Email (notifications)</label><input name="admin_email" value="<?= e($s['admin_email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Telegram Bot Token</label><input name="telegram_bot_token" value="<?= e($s['telegram_bot_token'] ?? '') ?>" placeholder="123456:ABC..."></div>
                    <div class="form-group"><label>Telegram Chat ID</label><input name="telegram_chat_id" value="<?= e($s['telegram_chat_id'] ?? '') ?>"></div>
                </div>

                <div class="toggle-row" style="margin-top:18px">
                    <label><input type="checkbox" name="notify_customer_order" value="1" <?= !empty($s['notify_customer_order']) || !isset($s['notify_customer_order']) ? 'checked' : '' ?>> Send order confirmation to customer</label>
                    <label><input type="checkbox" name="notify_customer_payment" value="1" <?= !empty($s['notify_customer_payment']) || !isset($s['notify_customer_payment']) ? 'checked' : '' ?>> Send "payment received" to customer</label>
                    <label><input type="checkbox" name="notify_customer_status" value="1" <?= !empty($s['notify_customer_status']) || !isset($s['notify_customer_status']) ? 'checked' : '' ?>> Send status updates to customer</label>
                    <label><input type="checkbox" name="email_include_bank" value="1" <?= !empty($s['email_include_bank']) ? 'checked' : '' ?>> Include bank details in customer order email</label>
                    <label><input type="checkbox" name="notify_admin_email" value="1" <?= !empty($s['notify_admin_email']) || !isset($s['notify_admin_email']) ? 'checked' : '' ?>> New-order alert to admin (email)</label>
                    <label><input type="checkbox" name="notify_admin_telegram" value="1" <?= !empty($s['notify_admin_telegram']) || !isset($s['notify_admin_telegram']) ? 'checked' : '' ?>> New-order alert to admin (Telegram)</label>
                    <label><input type="checkbox" name="notify_admin_payment" value="1" <?= !empty($s['notify_admin_payment']) || !isset($s['notify_admin_payment']) ? 'checked' : '' ?>> Payment-received alert to admin (email)</label>
                </div>
                <div style="display:flex;gap:10px">
                    <button class="btn btn-primary" type="submit">Save Notification Settings</button>
                    <button class="btn btn-secondary" type="submit" name="action" value="test_notification">Send Test</button>
                </div>
            </form>
        </div>

        <!-- ADMIN PASSWORD -->
        <div class="card">
            <h2>Admin Account</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_password">
                <div class="form-grid">
                    <div class="form-group"><label>Username</label><input name="username" value="<?= e($_SESSION['admin_user']) ?>"></div>
                    <div class="form-group"><label>New Password (min 8 chars)</label><input name="new_password" type="password" required></div>
                </div>
                <button class="btn btn-primary" type="submit">Update Password</button>
            </form>
        </div>
    </main>
    <script src="admin.js"></script>
</body>
</html>