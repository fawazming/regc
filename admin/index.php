<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_admin();

if (is_post() && ($_POST['action'] ?? '') === 'logout') {
    admin_logout();
    redirect('login.php');
}

// --- Initial (server-rendered) data for fast first paint & no-JS ---
$resOrders = db()->select('orders', ['select' => 'id,order_no,name,email,total,status,payment_status,created_at', 'order' => 'created_at.desc', 'limit' => '500']);
$orders = $resOrders['data'] ?? [];
$stats = ['total' => 0, 'pending' => 0, 'processing' => 0, 'confirmed' => 0, 'cancelled' => 0, 'revenue' => 0, 'products' => count(get_products())];
foreach ($orders as $o) {
    $stats['total']++;
    $st = $o['status'] ?? '';
    if (isset($stats[$st])) $stats[$st]++;
    if ($st === 'confirmed') $stats['revenue'] += (float)($o['total'] ?? 0);
}
$recent = array_slice($orders, 0, 8);
$cfg = notif_config();
$emailOk = $cfg['smtp_host'] !== '' && $cfg['smtp_user'] !== '' && $cfg['admin_email'] !== '';
$telegramOk = $cfg['telegram_bot_token'] !== '' && $cfg['telegram_chat_id'] !== '';

$badgeClass = fn($s) => in_array($s, ['pending', 'unpaid']) ? 'pending' : (in_array($s, ['confirmed', 'paid']) ? 'confirmed' : (in_array($s, ['processing']) ? 'processing' : 'cancelled'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= e(APP_NAME) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <aside class="sidebar">
        <div class="side-brand">
            <div class="icon">R</div>
            <div><strong><?= e(APP_NAME) ?></strong><span>ADMIN</span></div>
        </div>
        <nav>
            <a href="index.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="orders.php"><i class="fa-solid fa-bag-shopping"></i> Orders</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="https://app.supabase.com" target="_blank"><i class="fa-solid fa-database"></i> Database</a>
        </nav>
        <form method="post" class="logout-form">
            <input type="hidden" name="action" value="logout">
            <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</button>
        </form>
    </aside>
    <div class="side-overlay"></div>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <h1>Dashboard</h1>
            <div class="topbar-actions">
                <span class="live-dot" id="liveDot"><i class="fa-solid fa-circle"></i> Live</span>
                <span class="updated-at" id="updatedAt">Updated <?= e(date('H:i:s')) ?></span>
                <button class="btn btn-secondary refresh-btn" id="refreshBtn" onclick="refreshNow()"><i class="fa-solid fa-rotate"></i> Refresh</button>
            </div>
            <span class="user"><?= e($_SESSION['admin_user']) ?></span>
        </header>

        <!-- STATS -->
        <section class="stat-grid" id="statGrid">
            <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-bag-shopping"></i></div><div><span>Total Orders</span><strong id="st_total"><?= (int)$stats['total'] ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div><div><span>Pending</span><strong id="st_pending"><?= (int)$stats['pending'] ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-spinner"></i></div><div><span>Processing</span><strong id="st_processing"><?= (int)$stats['processing'] ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon gold"><i class="fa-solid fa-circle-check"></i></div><div><span>Confirmed</span><strong id="st_confirmed"><?= (int)$stats['confirmed'] ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-naira-sign"></i></div><div><span>Revenue (Confirmed)</span><strong id="st_revenue">₦<?= number_format($stats['revenue']) ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-box"></i></div><div><span>Products</span><strong id="st_products"><?= (int)$stats['products'] ?></strong></div></div>
        </section>

        <!-- NOTIFICATIONS STATUS -->
        <section class="card notif-card">
            <div class="notif-head">
                <h2><i class="fa-solid fa-bell"></i> Notification Integration</h2>
                <a class="mini-btn" href="settings.php">Configure in Settings</a>
            </div>
            <div class="notif-grid">
                <div class="notif-box <?= $emailOk ? 'ok' : 'warn' ?>">
                    <div class="notif-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="notif-info">
                        <strong>Email</strong>
                        <span id="emailStatus"><?= $emailOk ? 'Configured — ' . e($cfg['admin_email']) : 'Not configured' ?></span>
                    </div>
                    <button class="btn btn-small btn-primary" onclick="testNotification('email')"><i class="fa-solid fa-paper-plane"></i> Send Test</button>
                </div>
                <div class="notif-box <?= $telegramOk ? 'ok' : 'warn' ?>">
                    <div class="notif-icon"><i class="fa-brands fa-telegram"></i></div>
                    <div class="notif-info">
                        <strong>Telegram</strong>
                        <span id="tgStatus"><?= $telegramOk ? 'Configured — admin alerts' : 'Not configured' ?></span>
                    </div>
                    <button class="btn btn-small btn-primary" onclick="testNotification('telegram')"><i class="fa-solid fa-paper-plane"></i> Send Test</button>
                </div>
            </div>
            <div id="notifResult" class="notif-result"></div>
        </section>

        <!-- RECENT ORDERS -->
        <section class="card">
            <h2>Recent Orders</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Order No</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody id="ordersBody">
                    <?php if (!$recent): ?><tr id="noOrdersRow"><td colspan="7" style="text-align:center;color:#999">No orders yet.</td></tr><?php endif; ?>
                    <?php foreach ($recent as $o): ?>
                        <tr>
                            <td><strong><?= e($o['order_no']) ?></strong></td>
                            <td><?= e($o['name']) ?></td>
                            <td>₦<?= number_format((float)$o['total']) ?></td>
                            <td><span class="badge <?= e($badgeClass($o['payment_status'])) ?>"><?= e($o['payment_status']) ?></span></td>
                            <td><span class="badge <?= e($badgeClass($o['status'])) ?>"><?= e($o['status']) ?></span></td>
                            <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                            <td><a class="mini-btn" href="orders.php?view=<?= (int)$o['id'] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const CSRF = "<?= csrf_token() ?>";
        const BADGE = function (s) {
            s = String(s).toLowerCase();
            if (["pending", "unpaid"].includes(s)) return "pending";
            if (["confirmed", "paid"].includes(s)) return "confirmed";
            if (s === "processing") return "processing";
            return "cancelled";
        };

        function render(data) {
            // stats
            const s = data.stats || {};
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
            set("st_total", s.total || 0);
            set("st_pending", s.pending || 0);
            set("st_processing", s.processing || 0);
            set("st_confirmed", s.confirmed || 0);
            set("st_revenue", "₦" + Number(s.revenue || 0).toLocaleString());
            set("st_products", s.products || 0);

            // recent orders
            const body = document.getElementById("ordersBody");
            const rows = data.recent || [];
            if (!rows.length) {
                body.innerHTML = '<tr id="noOrdersRow"><td colspan="7" style="text-align:center;color:#999">No orders yet.</td></tr>';
            } else {
                body.innerHTML = rows.map(o => `
                    <tr>
                        <td><strong>${escapeHtml(o.order_no)}</strong></td>
                        <td>${escapeHtml(o.name)}</td>
                        <td>₦${Number(o.total).toLocaleString()}</td>
                        <td><span class="badge ${BADGE(o.payment_status)}">${escapeHtml(o.payment_status)}</span></td>
                        <td><span class="badge ${BADGE(o.status)}">${escapeHtml(o.status)}</span></td>
                        <td>${escapeHtml(o.date)}</td>
                        <td><a class="mini-btn" href="orders.php?view=${o.id}">View</a></td>
                    </tr>`).join("");
            }

            // notifications status
            const n = data.notifications || {};
            const es = document.getElementById("emailStatus");
            const ts = document.getElementById("tgStatus");
            if (es) {
                es.textContent = n.email_configured ? ("Configured — " + (n.admin_email || "")) : "Not configured";
                es.closest(".notif-box").className = "notif-box " + (n.email_configured ? "ok" : "warn");
            }
            if (ts) {
                ts.textContent = n.telegram_configured ? "Configured — admin alerts" : "Not configured";
                ts.closest(".notif-box").className = "notif-box " + (n.telegram_configured ? "ok" : "warn");
            }

            const upd = document.getElementById("updatedAt");
            if (upd) upd.textContent = "Updated " + (n.last_updated || data.last_updated || new Date().toLocaleTimeString());

            // live pulse
            const dot = document.getElementById("liveDot");
            if (dot) { dot.classList.remove("pulse"); void dot.offsetWidth; dot.classList.add("pulse"); }
        }

        async function fetchData() {
            try {
                const res = await fetch("api/dashboard.php", { headers: { "X-Requested-With": "XMLHttpRequest" } });
                const data = await res.json();
                if (data.ok) render(data);
            } catch (e) { /* silent; next poll */ }
        }

        async function testNotification(type) {
            const box = document.getElementById("notifResult");
            box.className = "notif-result";
            box.innerHTML = '<span style="color:var(--light-text)">Sending test ' + type + '...</span>';
            try {
                const res = await fetch("api/notification_test.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF },
                    body: JSON.stringify({ type })
                });
                const data = await res.json();
                const r = data.result || {};
                const items = [];
                if (r.email) items.push(emailLine(r.email));
                if (r.telegram) items.push(tgLine(r.telegram));
                box.className = "notif-result " + (data.ok ? "ok" : "error");
                box.innerHTML = items.join("") || escapeHtml(data.error || "Unknown response");
                fetchData(); // refresh config status after a (potentially new) config
            } catch (e) {
                box.className = "notif-result error";
                box.innerHTML = "Network error while testing.";
            }
        }
        function emailLine(r) { return '<div class="nt-line">📧 <strong>Email:</strong> ' + (r.ok ? "Test email sent to admin." : escapeHtml(r.error || "failed")) + '</div>'; }
        function tgLine(r) { return '<div class="nt-line">✈️ <strong>Telegram:</strong> ' + (r.ok ? "Test message sent." : escapeHtml(r.error || "failed")) + '</div>'; }

        function refreshNow() { fetchData(); }

        function escapeHtml(s) {
            return String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
        }

        // Auto-refresh every 30s
        fetchData();
        setInterval(fetchData, 30000);
    </script>
    <script src="admin.js"></script>
</body>
</html>