<?php
require 'config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = db();
        if ($_POST['action'] === 'add') {
            $db->prepare("INSERT INTO users (username,email,password,full_name,role) VALUES (?,?,?,?,?)")->execute([$_POST['username'],$_POST['email'],$_POST['password'],$_POST['full_name'],$_POST['role']]);
            showMessage('User added');
        }
        if ($_POST['action'] === 'delete') {
            if ($_POST['id'] == $_SESSION['user_id']) showMessage('Cannot delete yourself', 'error');
            else { $db->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['id']]); showMessage('User deleted'); }
        }
        header("Location: users.php"); exit;
    } catch (Exception $e) { $msg = ['msg'=>$e->getMessage(),'type'=>'error']; }
}

$users = db()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Users - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}body{display:flex;min-height:100vh;background:#f5f7fa}.sidebar{width:250px;background:#1e293b;color:white;position:fixed;height:100vh}.sidebar-header{padding:20px;font-size:18px;font-weight:600}.sidebar-nav{flex:1;padding:16px 0}.nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;color:rgba(255,255,255,0.7);text-decoration:none}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.1);color:white}.nav-item.active{background:#667eea}.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1)}.user-info{display:flex;align-items:center;gap:12px}.user-avatar{width:40px;height:40px;background:#667eea;border-radius:50%;display:flex;align-items:center;justify-content:center}.logout{color:rgba(255,255,255,0.7);text-decoration:none;display:flex;align-items:center;gap:8px}.main{margin-left:250px;flex:1;padding:30px}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.header h1{color:#1e293b}.btn{background:#667eea;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer}.btn-danger{background:#ef4444}.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:20px}table{width:100%;border-collapse:collapse}th,td{padding:14px;text-align:left;border-bottom:1px solid #eee}th{background:#f8fafc;font-size:12px;color:#666}.alert{padding:14px;border-radius:8px;margin-bottom:20px}.alert-success{background:#d4edda;color:#155724}.alert-error{background:#f8d7da;color:#721c24}.modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center}.modal.active{display:flex}.modal-content{background:white;padding:24px;border-radius:12px;width:100%;max-width:400px}.form-group{margin-bottom:16px}.form-group label{display:block;margin-bottom:6px}.form-group input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}.form-actions{display:flex;gap:10px;margin-top:20px}.badge{padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600}.badge-success{background:#d1fae5;color:#065f46}.badge-danger{background:#fee2e2;color:#991b1b}
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
            <a href="users.php" class="nav-item active"><i class="fas fa-user-cog"></i> Users</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info"><div class="user-avatar"><i class="fas fa-user"></i></div><div><?= h($_SESSION['full_name']) ?></div></div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="main">
        <div class="header"><h1>User Management</h1><button class="btn" onclick="document.getElementById('addModal').classList.add('active')"><i class="fas fa-plus"></i> Add User</button></div>
        <?php if (isset($msg)): ?><div class="alert alert-<?= $msg['type'] ?>"><?= h($msg['msg']) ?></div><?php endif; ?>
        <div class="card">
            <table>
                <thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= h($u['username']) ?></td>
                        <td><?= h($u['full_name']) ?></td>
                        <td><?= h($u['email'] ?? '-') ?></td>
                        <td><span class="badge badge-<?= $u['role']=='admin'?'danger':'success' ?>"><?= $u['role'] ?></span></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2>Add User</h2>
            <form method="POST"><input type="hidden" name="action" value="add">
                <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <div class="form-group"><label>Role</label><select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
                <div class="form-actions"><button type="button" class="btn" style="background:#999" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button><button type="submit" class="btn">Add User</button></div>
            </form>
        </div>
    </div>
</body>
</html>