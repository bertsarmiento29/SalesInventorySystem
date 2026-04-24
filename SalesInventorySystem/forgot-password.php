<?php
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($username) || empty($email)) {
        $error = 'Please enter username and email';
    } else {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
            $stmt->execute([$username, $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate new password
                $newPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
                
                // Update password (plain for simplicity)
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newPassword, $user['id']]);
                
                $success = "New password: <strong>$newPassword</strong> (Change it after login)";
            } else {
                $error = 'User not found with that username and email';
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
    <title>Forgot Password - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .login-box { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center; }
        
        .logo { width: 60px; height: 60px; margin: 0 auto 20px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        
        h1 { color: #333; margin-bottom: 8px; font-size: 20px; }
        .subtitle { color: #666; margin-bottom: 24px; font-size: 14px; }
        
        .error { background: #fee; color: #c00; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: left; }
        .success { background: #d4edda; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: left; }
        
        input { width: 100%; padding: 14px 16px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 16px; transition: border-color 0.3s; }
        input:focus { outline: none; border-color: #667eea; }
        
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600; transition: transform 0.2s; }
        button:hover { transform: scale(1.02); }
        
        .links { margin-top: 20px; font-size: 14px; }
        .links a { color: #667eea; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" fill="white"/>
                <path d="M12 22V12M12 12L3 7M12 12L21 7" stroke="#667eea" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        
        <h1>Forgot Password</h1>
        <p class="subtitle">Enter your username and email to reset</p>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Reset Password</button>
        </form>
        
        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>