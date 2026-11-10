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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both your username and password.';
    } else {
        try {
            if (Auth::attempt($username, $password)) {
                if (Auth::isAdmin() || $username === 'admin') {
                    header("Location: " . BASE_PATH . "/index.php#tab-admin");
                } else {
                    header("Location: " . BASE_PATH . "/index.php");
                }
                exit;
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } catch (\Throwable $e) {
            $error = 'Database connection error. Please ensure database services are running.';
        }
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
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/erp-desk.css?v=<?= time() ?>">
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      position: relative;
      margin: 0;
      padding: 24px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .bg-image {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: url('<?= BASE_PATH ?>/assets/images/Victoria-Dam1.jpg');
      background-size: cover;
      background-position: center;
      filter: blur(4px) brightness(0.55);
      z-index: 1;
      transform: scale(1.05);
    }
    .login-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 420px;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      padding: 40px 36px 32px;
      border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .login-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 28px;
      text-align: center;
    }
    .login-brand img {
      height: 52px;
      width: auto;
      margin-bottom: 14px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.06));
    }
    .login-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: #650b2b;
      margin: 0;
      letter-spacing: -0.02em;
    }
    .login-subtitle {
      font-size: 0.85rem;
      color: #64748b;
      margin-top: 5px;
      font-weight: 500;
    }
    .alert-error {
      background: #fef2f2;
      color: #b91c1c;
      padding: 11px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 20px;
      border: 1px solid #fecaca;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .login-form-group {
      margin-bottom: 18px;
    }
    .login-label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: #334155;
      margin-bottom: 6px;
    }
    .login-control {
      width: 100%;
      height: 42px;
      padding: 0 14px;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
      color: #0f172a;
      outline: none;
      transition: all 0.2s ease;
    }
    .login-control:focus {
      background: #ffffff;
      border-color: #0d9488;
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }
    .password-wrap {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }
    .password-wrap input {
      padding-right: 42px;
    }
    .password-toggle-btn {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      padding: 6px;
      cursor: pointer;
      color: #94a3b8;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: color 0.15s, background 0.15s;
    }
    .password-toggle-btn:hover {
      color: #0f172a;
      background: #e2e8f0;
    }
    .login-btn {
      width: 100%;
      height: 44px;
      background: #0d9488;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 14.5px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 24px;
      transition: background 0.2s ease, transform 0.1s ease;
      box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35);
    }
    .login-btn:hover {
      background: #0f766e;
    }
    .login-btn:active {
      transform: translateY(1px);
    }
    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 11.5px;
      color: #94a3b8;
      border-top: 1px solid #f1f5f9;
      padding-top: 16px;
    }
  </style>
</head>
<body>
  <div class="bg-image"></div>
  <div class="login-container">
    <div class="login-brand">
      <img src="<?= BASE_PATH ?>/assets/images/EGL-NEW.png" alt="EGL Logo">
      <h1 class="login-title">EGL FIFOERP Portal</h1>
      <div class="login-subtitle">Enterprise FIFO &amp; Power Generation Desk</div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="login-form-group">
        <label class="login-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="login-control" placeholder="Enter your username" autocomplete="username" autofocus required>
      </div>

      <div class="login-form-group">
        <label class="login-label" for="loginPassword">Password</label>
        <div class="password-wrap">
          <input type="password" id="loginPassword" name="password" class="login-control" placeholder="Enter your password" autocomplete="current-password" required>
          <button type="button" class="password-toggle-btn" title="Show password" onclick="toggleLoginPassword(this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn">
        <span>Sign In to Portal</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <div class="login-footer">
      Electricity Generation Lanka (Pvt) Ltd &bull; Secure Access
    </div>
  </div>

  <script>
    function toggleLoginPassword(btn) {
      const input = document.getElementById('loginPassword');
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.innerHTML = isPassword 
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
      btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
    }
  </script>
</body>
</html>
