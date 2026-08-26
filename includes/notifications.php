<?php
/**
 * REGC — Notifications.
 *
 * 1) Email (customer + admin) via a small, dependency-free SMTP client.
 * 2) Telegram (admin) via the Bot API.
 *
 * All credentials AND toggles come from the admin Settings page (database).
 * Available customer emails (each independently togglable):
 *   - Order confirmation     (notify_customer_order)
 *   - Payment received       (notify_customer_payment)
 *   - Order status update    (notify_customer_status)
 * Admin alerts: new order (email + Telegram) and payment (email).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/mailer.php';

/**
 * Load notification settings with sensible defaults.
 */
function notif_config(): array
{
    $s = get_settings();
    $bool = function ($v, $def = '1') { return in_array(strtolower((string)($v ?? $def)), ['1', 'true', 'on', 'yes'], true); };
    return [
        // SMTP
        'smtp_host'   => $s['smtp_host'] ?? '',
        'smtp_port'   => (int)($s['smtp_port'] ?? 587),
        'smtp_user'   => $s['smtp_user'] ?? '',
        'smtp_pass'   => $s['smtp_pass'] ?? '',
        'smtp_secure' => $s['smtp_secure'] ?? 'tls',
        'mail_from'   => $s['mail_from'] ?? '',
        'mail_from_name' => $s['mail_from_name'] ?? APP_NAME,
        // Recipients
        'admin_email' => $s['admin_email'] ?? '',
        // Telegram
        'telegram_bot_token' => $s['telegram_bot_token'] ?? '',
        'telegram_chat_id'   => $s['telegram_chat_id'] ?? '',
        'telegram_api_base'  => !empty($s['telegram_api_base']) ? $s['telegram_api_base'] : 'https://api.telegram.org',
        // Notification toggles
        'notify_customer_order'   => $bool($s['notify_customer_order'] ?? '1'),
        'notify_customer_payment' => $bool($s['notify_customer_payment'] ?? '1'),
        'notify_customer_status'  => $bool($s['notify_customer_status'] ?? '1'),
        'email_include_bank'      => $bool($s['email_include_bank'] ?? '0'),
        'notify_admin_email'      => $bool($s['notify_admin_email'] ?? '1'),
        'notify_admin_telegram'   => $bool($s['notify_admin_telegram'] ?? '1'),
        'notify_admin_payment'    => $bool($s['notify_admin_payment'] ?? '1'),
        // Bank details (SAME source as the checkout: store_bank_details()).
        // These are exactly the account details set in Admin -> Store & Checkout.
        'bank_name'      => store_bank_details()['bank_name'],
        'account_name'   => store_bank_details()['account_name'],
        'account_number' => store_bank_details()['account_number'],
        'payment_instructions' => store_bank_details()['payment_instructions'],
    ];
}

/** Normalize the items array from an order row (may be JSON string or array). */
function normalize_order_items($order): array
{
    $items = $order['items'] ?? [];
    if (is_string($items)) {
        $items = json_decode($items, true);
    }
    return is_array($items) ? $items : [];
}

function status_label(string $status): string
{
    $map = ['pending' => 'Pending', 'processing' => 'Processing', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'failed' => 'Failed'];
    return $map[$status] ?? ucfirst($status);
}

/* ------------------------------------------------------------------ */
/*  Branded email shell + shared blocks                               */
/* ------------------------------------------------------------------ */

function email_shell(string $heading, string $html): string
{
    return '<div style="font-family:Inter,Arial,Helvetica,sans-serif;color:#1A1A1A;max-width:600px;margin:auto">'
        . '<div style="background:#0D1E3F;color:#fff;padding:20px;border-radius:10px 10px 0 0">'
        . '<h2 style="margin:0;font-family:Montserrat,Arial,sans-serif">' . e(APP_NAME) . '</h2>'
        . '<span style="font-size:12px;letter-spacing:2px;color:#F79A42">' . e(APP_TAGLINE) . '</span></div>'
        . '<div style="border:1px solid #e3e3e3;border-top:0;padding:22px;border-radius:0 0 10px 10px">'
        . '<h3 style="margin-top:0">' . $heading . '</h3>'
        . $html
        . '<p style="margin-top:18px;font-size:12px;color:#888">' . e(APP_NAME) . ' — We shop. We pack. We deliver. You relax.<br>'
        . 'This is an automated message; please do not reply.</p>'
        . '</div></div>';
}

function email_items_table(array $items): string
{
    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #eee">' . e($it['name'] ?? '') . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center">x' . (int)($it['quantity'] ?? 0) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right">' . money($it['price'] ?? 0) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right">' . money(($it['price'] ?? 0) * ($it['quantity'] ?? 0)) . '</td>'
            . '</tr>';
    }
    return '<table style="width:100%;border-collapse:collapse">'
        . '<tr style="background:#f6f6f6"><th style="padding:8px;text-align:left">Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>'
        . $rows
        . '</table>';
}

function email_bank_box(array $config): string
{
    return '<div style="background:#FFF0E2;padding:14px;border-radius:8px;margin-top:14px">'
        . '<strong>Payment details</strong><br>'
        . 'Account Name: ' . e($config['account_name'] ?? '') . '<br>'
        . 'Bank: ' . e($config['bank_name'] ?? '') . '<br>'
        . 'Account No: <b>' . e($config['account_number'] ?? '') . '</b><br>'
        . e($config['payment_instructions'] ?? '')
        . '</div>';
}

function email_totals(array $order): string
{
    return '<p style="text-align:right;margin-top:10px"><strong>Subtotal:</strong> ' . money($order['subtotal'] ?? 0) . '<br>'
        . '<strong>Delivery:</strong> ' . money($order['delivery_fee'] ?? 0) . '<br>'
        . '<strong style="font-size:18px">Total: ' . money($order['total'] ?? 0) . '</strong></p>';
}

/* ------------------------------------------------------------------ */
/*  Order confirmation (customer) / new-order (admin)                 */
/* ------------------------------------------------------------------ */

function build_order_email_body(array $order, array $items, array $config, bool $forCustomer, bool $includeBank): string
{
    $html = '<p><strong>Order No:</strong> ' . e($order['order_no'] ?? '') . '</p>'
        . email_items_table($items)
        . email_totals($order)
        . '<p><strong>Delivery address:</strong> ' . e($order['address'] ?? '') . '<br>'
        . '<strong>Phone:</strong> ' . e($order['phone'] ?? '') . '</p>';

    if (!$forCustomer) {
        $html .= email_bank_box($config);
    } elseif ($includeBank) {
        $html .= email_bank_box($config);
    }

    $heading = $forCustomer
        ? 'Thank you, ' . e($order['name'] ?? '') . '! We have received your order.'
        : 'New order received from ' . e($order['name'] ?? '') . ' &lt;' . e($order['email'] ?? '') . '&gt;';
    return email_shell($heading, $html);
}

/* ------------------------------------------------------------------ */
/*  Payment received                                                  */
/* ------------------------------------------------------------------ */

function build_payment_email_body(array $order, array $items, array $config, bool $forCustomer): string
{
    $html = '<p><strong>Order No:</strong> ' . e($order['order_no'] ?? '') . '</p>'
        . '<p>Payment received successfully for <strong>' . money($order['total'] ?? 0) . '</strong>.</p>'
        . email_items_table($items)
        . email_totals($order);

    if (!$forCustomer) {
        $html .= '<p><strong>Customer:</strong> ' . e($order['name'] ?? '') . ' &lt;' . e($order['email'] ?? '') . '&gt;</p>';
    } else {
        $html .= '<p>We will begin processing your order and keep you updated.</p>';
    }

    return email_shell($forCustomer ? 'Payment received — thank you!' : 'Payment received for ' . e($order['order_no'] ?? ''), $html);
}

/* ------------------------------------------------------------------ */
/*  Order status update                                               */
/* ------------------------------------------------------------------ */

function build_status_email_body(array $order, array $items, array $config, string $status, bool $forCustomer): string
{
    $html = '<p><strong>Order No:</strong> ' . e($order['order_no'] ?? '') . '</p>'
        . '<p>Your order status is now: <strong>' . e(status_label($status)) . '</strong></p>'
        . email_items_table($items)
        . email_totals($order);

    if (!$forCustomer) {
        $html .= '<p><strong>Customer:</strong> ' . e($order['name'] ?? '') . ' &lt;' . e($order['email'] ?? '') . '&gt;</p>';
    }

    return email_shell($forCustomer ? 'Your order is now ' . e(status_label($status)) : 'Order ' . e($order['order_no'] ?? '') . ' → ' . e(status_label($status)), $html);
}

/* ------------------------------------------------------------------ */
/*  Senders                                                           */
/* ------------------------------------------------------------------ */

function mail_ok(array $config): bool
{
    return $config['smtp_host'] !== '' && $config['smtp_user'] !== '';
}

/** New-order notifications: customer confirmation + admin email/Telegram. */
function send_order_notifications(array $order, array $items): void
{
    $config = notif_config();
    if (!mail_ok($config)) {
        log_msg('SMTP not configured; skipping order emails');
        return;
    }

    // Customer order confirmation
    if ($config['notify_customer_order'] && !empty($order['email'])) {
        $body = build_order_email_body($order, $items, $config, true, $config['email_include_bank']);
        send_mail($config, $order['email'], 'Order Received — ' . $order['order_no'] . ' | ' . APP_NAME, $body);
    }

    // Admin email
    if ($config['notify_admin_email'] && !empty($config['admin_email'])) {
        $body = build_order_email_body($order, $items, $config, false, true);
        send_mail($config, $config['admin_email'], 'New Order ' . $order['order_no'] . ' — ' . money($order['total'] ?? 0), $body);
    }

    // Admin Telegram
    if ($config['notify_admin_telegram'] && !empty($config['telegram_bot_token']) && !empty($config['telegram_chat_id'])) {
        $lines = [
            "🛒 *New Order* " . $order['order_no'],
            "",
            "*Customer:* " . ($order['name'] ?? ''),
            "*Email:* " . ($order['email'] ?? ''),
            "*Phone:* " . ($order['phone'] ?? ''),
            "*Address:* " . ($order['address'] ?? ''),
            "",
            "*Items:*",
        ];
        foreach ($items as $it) {
            $lines[] = "• " . $it['name'] . " x" . $it['quantity'] . " = " . money(($it['price'] ?? 0) * ($it['quantity'] ?? 0));
        }
        $lines[] = "";
        $lines[] = "*Total:* " . money($order['total'] ?? 0);
        $lines[] = "*Payment:* " . ($order['payment_method'] ?? 'bank');
        send_telegram($config, implode("\n", $lines));
    }
}

/** Payment-received notifications (customer + admin). */
function send_payment_notifications(array $order, array $items): void
{
    $config = notif_config();
    if (!mail_ok($config)) {
        log_msg('SMTP not configured; skipping payment emails');
        return;
    }

    if ($config['notify_customer_payment'] && !empty($order['email'])) {
        $body = build_payment_email_body($order, $items, $config, true);
        send_mail($config, $order['email'], 'Payment Received — ' . $order['order_no'] . ' | ' . APP_NAME, $body);
    }

    if ($config['notify_admin_payment'] && !empty($config['admin_email'])) {
        $body = build_payment_email_body($order, $items, $config, false);
        send_mail($config, $config['admin_email'], 'Payment Received ' . $order['order_no'] . ' — ' . money($order['total'] ?? 0), $body);
    }
}

/** Order status-update notification (customer). */
function send_status_notification(array $order, array $items, string $status): void
{
    $config = notif_config();
    if (!mail_ok($config)) {
        log_msg('SMTP not configured; skipping status email');
        return;
    }

    if ($config['notify_customer_status'] && !empty($order['email'])) {
        $body = build_status_email_body($order, $items, $config, $status, true);
        send_mail($config, $order['email'], 'Order ' . $order['order_no'] . ' — ' . status_label($status) . ' | ' . APP_NAME, $body);
    }
}

/** Send a Telegram message. Returns ['ok'=>bool,'error'=>?string]. */
function send_telegram(array $config, string $text): bool
{
    $token = $config['telegram_bot_token'] ?? '';
    $chatId = $config['telegram_chat_id'] ?? '';
    $base = rtrim((string)($config['telegram_api_base'] ?? 'https://api.telegram.org'), '/');
    if ($base === '') {
        $base = 'https://api.telegram.org';
    }
    if ($token === '' || $chatId === '') {
        log_msg('Telegram not configured; skipping');
        return false;
    }
    $url = $base . '/bot' . $token . '/sendMessage';
    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    // PHP 8.0+ closes the handle automatically; curl_close() is a no-op/deprecated.
    if ($http >= 400 || $res === false) {
        log_msg('Telegram send failed', ['http' => $http, 'err' => $err, 'res' => $res]);
        return false;
    }
    return true;
}