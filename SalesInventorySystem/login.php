<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            $valid = false;
            if ($user) {
                if ($password === $user['password'] || password_verify($password, $user['password'])) {
                    $valid = true;
                }
            }
            
            if ($valid && $user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                redirect('dashboard.php');
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .login-box { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center; }
        
        .logo { width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 16px; display: flex; align-items: center; justify-content: center; }
        
        h1 { color: #333; margin-bottom: 8px; font-size: 24px; }
        .subtitle { color: #666; margin-bottom: 24px; font-size: 14px; }
        
        .error { background: #fee; color: #c00; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: left; }
        
        input { width: 100%; padding: 14px 16px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 16px; transition: border-color 0.3s; }
        input:focus { outline: none; border-color: #667eea; }
        
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600; transition: transform 0.2s; }
        button:hover { transform: scale(1.02); }
        
        .hint { margin-top: 24px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" fill="white"/>
                <path d="M12 22V12M12 12L3 7M12 12L21 7" stroke="#667eea" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        
        <h1><?= SITE_NAME ?></h1>
        <p class="subtitle">Sign in to continue</p>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>
        
        <!-- Credentials hidden for security -->
        
        <div style="margin-top:16px;font-size:13px;color:#666">
            <a href="forgot-password.php" style="color:#667eea;text-decoration:none">Forgot Password?</a>
        </div>
    </div>
</body>
</html>