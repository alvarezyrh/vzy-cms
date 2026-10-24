<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
initDatabase();

if (isLoggedIn()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $user = findOne('users', fn($u) => $u['username'] === $username || $u['email'] === $username);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            logActivity('login', $user['username'], $user['username']);
            redirect('index.php');
        }
        $error = 'Username atau password salah.';
    } else {
        $error = 'Harap isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f172a 0%,#0d9488 100%);padding:20px}
        .login-card{background:#fff;border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.2);padding:48px 40px;width:100%;max-width:420px;animation:floatIn .5s ease}
        @keyframes floatIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        .login-logo{width:64px;height:64px;background:linear-gradient(135deg,#0d9488,#2dd4bf);border-radius:18px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;font-weight:700;margin:0 auto 20px;box-shadow:0 8px 24px rgba(13,148,136,.4)}
        h1{text-align:center;font-size:24px;font-weight:700;color:#1e293b;margin-bottom:6px}
        .subtitle{text-align:center;color:#94a3b8;font-size:14px;margin-bottom:32px}
        .form-group{margin-bottom:18px}
        .form-label{display:block;font-size:13px;font-weight:600;color:#1e293b;margin-bottom:6px}
        .input-wrap{position:relative}
        .input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px}
        .form-control{width:100%;padding:13px 16px 13px 42px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:14px;outline:none;transition:all .25s;font-family:inherit}
        .form-control:focus{border-color:#0d9488;box-shadow:0 0 0 4px rgba(13,148,136,.15)}
        .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;border-radius:50px;font-size:15px;font-weight:600;cursor:pointer;transition:all .25s;margin-top:8px;box-shadow:0 4px 16px rgba(13,148,136,.35);font-family:inherit}
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(13,148,136,.45)}
        .error{background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
        .footer-text{text-align:center;margin-top:28px;font-size:12px;color:#94a3b8}
        .footer-text strong{color:#0d9488}
        .demo-hint{background:#ecfdf5;border:1px solid #99f6e4;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:12px;color:#115e59;text-align:center}
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">JD</div>
    <h1>PT MBG</h1>
    <p class="subtitle">Masuk ke dashboard</p>
    <?php if ($error): ?><div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="demo-hint"><strong>Demo:</strong> admin / admin123</div>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Username</label>
            <div class="input-wrap">
                <i class="fas fa-user"></i>
                <input type="text" name="username" class="form-control" placeholder="Username atau email" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
        </div>
        <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Masuk</button>
    </form>
    <p class="footer-text">Made by <strong>vzy</strong></p>
</div>
</body>
</html>
