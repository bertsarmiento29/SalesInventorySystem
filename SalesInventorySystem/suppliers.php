<?php
require 'config.php';
requireLogin();

$action = $_POST['action'] ?? '';
if ($action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = db();
        if ($action === 'add') $db->prepare("INSERT INTO suppliers (name,contact_person,email,phone,address) VALUES (?,?,?,?,?)")->execute([$_POST['name'],$_POST['contact_person'],$_POST['email'],$_POST['phone'],$_POST['address']]);
        if ($action === 'edit') $db->prepare("UPDATE suppliers SET name=?,contact_person=?,email=?,phone=?,address=? WHERE id=?")->execute([$_POST['name'],$_POST['contact_person'],$_POST['email'],$_POST['phone'],$_POST['address'],$_POST['id']]);
        if ($action === 'delete') $db->prepare("DELETE FROM suppliers WHERE id=?")->execute([$_POST['id']]);
        showMessage('Done'); header("Location: suppliers.php"); exit;
    } catch (Exception $e) { $msg = ['msg'=>$e->getMessage(),'type'=>'error']; }
}

$suppliers = db()->query("SELECT s.*, COUNT(p.id) as products FROM suppliers s LEFT JOIN products p ON s.id=p.supplier_id GROUP BY s.id ORDER BY s.name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Suppliers - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}body{display:flex;min-height:100vh;background:#f5f7fa}.sidebar{width:250px;background:#1e293b;color:white;position:fixed;height:100vh}.sidebar-header{padding:20px;font-size:18px;font-weight:600}.sidebar-nav{flex:1;padding:16px 0}.nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;color:rgba(255,255,255,0.7);text-decoration:none}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.1);color:white}.nav-item.active{background:#667eea}.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1)}.user-info{display:flex;align-items:center;gap:12px}.user-avatar{width:40px;height:40px;background:#667eea;border-radius:50%;display:flex;align-items:center;justify-content:center}.logout{color:rgba(255,255,255,0.7);text-decoration:none;display:flex;align-items:center;gap:8px}.main{margin-left:250px;flex:1;padding:30px}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.header h1{color:#1e293b}.btn{background:#667eea;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer}.btn-danger{background:#ef4444}.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08)}.card-body{padding:20px}table{width:100%;border-collapse:collapse}th,td{padding:14px;text-align:left;border-bottom:1px solid #eee}th{background:#f8fafc;font-size:12px;color:#666}.modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center}.modal.active{display:flex}.modal-content{background:white;padding:24px;border-radius:12px;width:100%;max-width:400px}.form-group{margin-bottom:16px}.form-group label{display:block;margin-bottom:6px}.form-group input,textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}.form-actions{display:flex;gap:10px;margin-top:20px}
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
            <a href="suppliers.php" class="nav-item active"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories</a>
            <?php if (isAdmin()): ?><a href="users.php" class="nav-item"><i class="fas fa-user-cog"></i> Users</a><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a><?php endif; ?>
            <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info"><div class="user-avatar"><i class="fas fa-user"></i></div><div><?= h($_SESSION['full_name']) ?></div></div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="main">
        <div class="header"><h1>Suppliers</h1><button class="btn" onclick="document.getElementById('addModal').classList.add('active')"><i class="fas fa-plus"></i> Add</button></div>
        <div class="card">
            <div class="card-body">
                <table>
                    <thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>Phone</th><th>Products</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?= h($s['name']) ?></td>
                            <td><?= h($s['contact_person'] ?? '-') ?></td>
                            <td><?= h($s['email'] ?? '-') ?></td>
                            <td><?= h($s['phone'] ?? '-') ?></td>
                            <td><?= $s['products'] ?></td>
                            <td>
                                <button class="btn btn-sm" onclick='editSupplier(<?= json_encode($s) ?>)'>Edit</button>
                                <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</button></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2>Add Supplier</h2>
            <form method="POST"><input type="hidden" name="action" value="add">
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                <div class="form-group"><label>Address</label><textarea name="address" rows="2"></textarea></div>
                <div class="form-actions"><button type="button" class="btn" style="background:#999" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button><button type="submit" class="btn">Add</button></div>
            </form>
        </div>
    </div>
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Supplier</h2>
            <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
                <div class="form-group"><label>Name</label><input type="text" name="name" id="edit_name" required></div>
                <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" id="edit_contact_person"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
                <div class="form-group"><label>Address</label><textarea name="address" id="edit_address" rows="2"></textarea></div>
                <div class="form-actions"><button type="button" class="btn" style="background:#999" onclick="document.getElementById('editModal').classList.remove('active')">Cancel</button><button type="submit" class="btn">Update</button></div>
            </form>
        </div>
    </div>
    <script>function editSupplier(s){document.getElementById('edit_id').value=s.id;document.getElementById('edit_name').value=s.name;document.getElementById('edit_contact_person').value=s.contact_person||'';document.getElementById('edit_email').value=s.email||'';document.getElementById('edit_phone').value=s.phone||'';document.getElementById('edit_address').value=s.address||'';document.getElementById('editModal').classList.add('active');}</script>
</body>
</html>