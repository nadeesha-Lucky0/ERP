<?php
require_once __DIR__ . '/app/config/config.php';

use App\Core\Auth;
use App\Config\Database;

if (Auth::check()) {
    if (Auth::isAdmin()) {
        header("Location: " . BASE_PATH . "/index.php#tab-admin");
    } else {
        header("Location: " . BASE_PATH . "/index.php");
    }
    exit;
}

$error = '';

// Handle Direct 1-Click Login or Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['quick_login'])) {
    $username = trim($_POST['username'] ?? 'admin');
    $password = trim($_POST['password'] ?? 'admin');

    try {
        if (Auth::attempt($username, $password)) {
            // If administrator, route directly to Admin Dashboard
            if (Auth::isAdmin() || $username === 'admin') {
                header("Location: " . BASE_PATH . "/index.php#tab-admin");
            } else {
                header("Location: " . BASE_PATH . "/index.php");
            }
            exit;
        } else {
            $error = 'Invalid credentials. Common login: admin / admin';
        }
    } catch (\Throwable $e) {
        $error = 'Database Notice: Please make sure MySQL is started in XAMPP. (' . $e->getMessage() . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | EGL FIFOERP Portal</title>
  <link rel="icon" type="image/png" href="<?= BASE_PATH ?>/assets/images/EGL-NEW.png">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/erp-desk.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      position: relative;
      margin: 0;
      padding: 20px;
    }
    .bg-image {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: url('<?= BASE_PATH ?>/assets/images/Victoria-Dam1.jpg');
      background-size: cover;
      background-position: center;
      filter: blur(4px) brightness(0.65);
      z-index: 1;
    }
    .login-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 440px;
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
      padding: 38px 34px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .login-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 24px;
      text-align: center;
    }
    .login-brand img {
      height: 48px;
      margin-bottom: 12px;
    }
    .login-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #650b2b;
      margin: 0;
    }
    .login-subtitle {
      font-size: 0.85rem;
      color: #64748b;
      margin-top: 4px;
    }
    .alert-error {
      background: #fee2e2;
      color: #b91c1c;
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 12px;
      margin-bottom: 18px;
      border: 1px solid #fecaca;
    }
    .quick-login-card {
      background: #f0fdfa;
      border: 1px dashed #0d9488;
      border-radius: 8px;
      padding: 14px;
      margin-top: 20px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="bg-image"></div>
  <div class="login-container">
    <div class="login-brand">
      <img src="<?= BASE_PATH ?>/assets/images/EGL-NEW.png" alt="EGL Logo">
      <h1 class="login-title">EGL FIFOERP Portal</h1>
      <div class="login-subtitle">Enterprise FIFO & Power Generation Desk</div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Standard Login Form with prefilled common credentials -->
    <form method="POST" action="">
      <div class="desk-form-group">
        <label class="desk-form-label">Username</label>
        <input type="text" name="username" class="desk-form-control" value="admin" required>
      </div>

      <div class="desk-form-group">
        <label class="desk-form-label">Password</label>
        <div class="password-input-wrap">
          <input type="password" id="loginPassword" name="password" class="desk-form-control" value="admin" required>
          <button type="button" class="password-toggle-btn" title="Show password" onclick="toggleLoginPassword(this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="desk-btn desk-btn-primary" style="width: 100%; justify-content: center; height: 40px; font-weight: 600; font-size: 14px; margin-top: 6px;">
        Sign In to Portal &rarr;
      </button>
    </form>

    <!-- Direct One-Click Common Access -->
    <div class="quick-login-card">
      <div style="font-size: 12px; color: #0f766e; font-weight: 600; margin-bottom: 8px;">
        ⚡ Direct Common Access (Common Account)
      </div>
      <a href="<?= BASE_PATH ?>/login.php?quick_login=1" class="desk-btn desk-btn-default" style="width: 100%; justify-content: center; font-weight: 600; color: #0d9488; border-color: #0d9488;">
        🚀 One-Click Direct Login as Administrator
      </a>
      <div style="font-size: 11px; color: #64748b; margin-top: 8px;">
        Connected DB: <strong>fifonew</strong> | Automatic schema initialization active
      </div>
    </div>
  </div>
  <script>
    function toggleLoginPassword(btn) {
      const input = document.getElementById('loginPassword');
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.innerHTML = isPassword 
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
      btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
    }
  </script>
</body>
</html>
