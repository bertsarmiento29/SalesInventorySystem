<?php
require 'config.php';
requireAdmin();
$msg = getMsg();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = db();
        if ($_POST['action'] === 'settings') {
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('tax_rate', ?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$_POST['tax_rate'], $_POST['tax_rate']]);
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('company_name', ?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$_POST['company_name'], $_POST['company_name']]);
            showMessage('Settings saved');
        }
        if ($_POST['action'] === 'password') {
            $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($_POST['current'] !== $user['password'] && !password_verify($_POST['current'], $user['password'])) {
                $msg = ['msg' => 'Current password incorrect', 'type' => 'error'];
            } else {
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$_POST['new_password'], $_SESSION['user_id']]);
                showMessage('Password changed');
            }
        }
        header("Location: settings.php"); exit;
    } catch (Exception $e) { $msg = ['msg' => $e->getMessage(), 'type' => 'error']; }
}

$settings = [];
foreach (db()->query("SELECT * FROM settings") as $s) $settings[$s['setting_key']] = $s['setting_value'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}body{display:flex;min-height:100vh;background:#f5f7fa}.sidebar{width:250px;background:#1e293b;color:white;position:fixed;height:100vh}.sidebar-header{padding:20px;font-size:18px;font-weight:600}.sidebar-nav{flex:1;padding:16px 0}.nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;color:rgba(255,255,255,0.7);text-decoration:none}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.1);color:white}.nav-item.active{background:#667eea}.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1)}.user-info{display:flex;align-items:center;gap:12px}.user-avatar{width:40px;height:40px;background:#667eea;border-radius:50%;display:flex;align-items:center;justify-content:center}.logout{color:rgba(255,255,255,0.7);text-decoration:none;display:flex;align-items:center;gap:8px}.main{margin-left:250px;flex:1;padding:30px}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.header h1{color:#1e293b}.btn{background:#667eea;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer}.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:24px;margin-bottom:20px}.card h2{font-size:18px;margin-bottom:20px}.form-group{margin-bottom:16px}.form-group label{display:block;margin-bottom:6px}.form-group input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}.alert{padding:14px;border-radius:8px;margin-bottom:20px}.alert-success{background:#d4edda;color:#155724}.alert-error{background:#f8d7da;color:#721c24}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><?= SITE_NAME ?></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products</a>
            <a href="sales.php" class="nav-item"><i class="fas fa-shopping-cart"></i> Sales</a>
            <a href="customers.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            <a href="suppliers.php" class="nav-item"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories</a>
            <a href="users.php" class="nav-item"><i class="fas fa-user-cog"></i> Users</a>
            <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a>
            <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info"><div class="user-avatar"><i class="fas fa-user"></i></div><div><?= h($_SESSION['full_name']) ?></div></div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="main">
        <div class="header"><h1>Settings</h1></div>
        <?php if ($msg): ?><div class="alert alert-<?= $msg['type'] ?>"><?= h($msg['msg']) ?></div><?php endif; ?>
        
        <div class="card">
            <h2>General Settings</h2>
            <form method="POST">
                <input type="hidden" name="action" value="settings">
                <div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?= h($settings['company_name'] ?? SITE_NAME) ?>"></div>
                <div class="form-group"><label>Tax Rate (%)</label><input type="number" name="tax_rate" value="<?= $settings['tax_rate'] ?? 10 ?>"></div>
                <button type="submit" class="btn">Save Settings</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="password">
                <div class="form-group"><label>Current Password</label><input type="password" name="current" required></div>
                <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
                <button type="submit" class="btn">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>