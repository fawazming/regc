<?php
require_once __DIR__ . '/../includes/auth.php';
if (admin_logged_in()) redirect('index.php');
$error = '';
if (is_post()) {
    if (!csrf_verify()) { /* handled */ }
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (admin_login($u, $p)) redirect('index.php');
    else $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", sans-serif; background: linear-gradient(135deg, #0D1E3F, #102A56); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; border-radius: 18px; padding: 40px; width: min(400px, 92%); box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .brand .icon { width: 48px; height: 48px; background: #F38321; color: white; border-radius: 12px; display: grid; place-items: center; font-size: 20px; font-weight: 800; font-family: "Montserrat", sans-serif; }
        .brand h1 { font-size: 18px; color: #0D1E3F; font-family: "Montserrat", sans-serif; }
        .brand span { font-size: 10px; letter-spacing: 2px; color: #888; font-weight: 700; }
        h2 { margin-bottom: 6px; color: #0D1E3F; font-family: "Montserrat", sans-serif; }
        .sub { color: #888; font-size: 14px; margin-bottom: 24px; }
        .error { background: #fdeaea; color: #a23b3b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600; }
        input { width: 100%; padding: 14px; border: 1px solid #e3e3e3; border-radius: 10px; margin-bottom: 16px; font-family: inherit; }
        input:focus { outline: none; border-color: #0D1E3F; }
        button { width: 100%; padding: 15px; background: #0D1E3F; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; font-family: inherit; }
        button:hover { background: #102A56; }
        .back { text-align: center; margin-top: 18px; }
        .back a { color: #888; font-size: 13px; text-decoration: none; }
    </style>
</head>
<body>
    <form class="login-card" method="post">
        <?= csrf_field() ?>
        <div class="brand">
            <div class="icon">R</div>
            <div><h1><?= e(APP_NAME) ?></h1><span>ADMIN</span></div>
        </div>
        <h2>Sign In</h2>
        <p class="sub">Manage your store, orders and settings.</p>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
        <label>Username</label>
        <input type="text" name="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign In</button>
        <div class="back"><a href="../index.php">&larr; Back to store</a></div>
    </form>
</body>
</html>