<?php
require 'config.php';
requireLogin();
$msg = getMsg();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        $db = db();
        
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO products (sku, name, description, category_id, supplier_id, price, cost_price, stock_quantity, reorder_level) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$_POST['sku'], $_POST['name'], $_POST['description'], $_POST['category_id']?:null, $_POST['supplier_id']?:null, $_POST['price'], $_POST['cost_price']?:0, $_POST['stock_quantity']?:0, $_POST['reorder_level']?:10]);
            showMessage('Product added successfully');
        }
        
        if ($action === 'edit') {
            $stmt = $db->prepare("UPDATE products SET sku=?, name=?, description=?, category_id=?, supplier_id=?, price=?, cost_price=?, reorder_level=? WHERE id=?");
            $stmt->execute([$_POST['sku'], $_POST['name'], $_POST['description'], $_POST['category_id']?:null, $_POST['supplier_id']?:null, $_POST['price'], $_POST['cost_price']?:0, $_POST['reorder_level']?:10, $_POST['id']]);
            showMessage('Product updated successfully');
        }
        
        if ($action === 'delete') {
            $db->prepare("DELETE FROM products WHERE id=?")->execute([$_POST['id']]);
            showMessage('Product deleted');
        }
        
        if ($action === 'update_stock') {
            $db->prepare("UPDATE products SET stock_quantity=? WHERE id=?")->execute([$_POST['stock_quantity'], $_POST['id']]);
            showMessage('Stock updated');
        }
        
        header("Location: products.php");
        exit;
        
    } catch (Exception $e) {
        $msg = ['msg' => 'Error: ' . $e->getMessage(), 'type' => 'error'];
    }
}

// Get data
try {
    $db = db();
    $products = $db->query("SELECT p.*, c.name as category_name, s.name as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        ORDER BY p.name")->fetchAll();
    
    $categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $suppliers = $db->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $products = $categories = $suppliers = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f5f7fa; }
        .sidebar { width: 250px; background: #1e293b; color: white; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; font-size: 18px; font-weight: 600; }
        .sidebar-nav { flex: 1; padding: 16px 0; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.7); text-decoration: none; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: #667eea; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-info { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .user-avatar { width: 40px; height: 40px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .logout { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .main { margin-left: 250px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { color: #1e293b; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { background: #5568d3; }
        .btn-danger { background: #ef4444; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-body { padding: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; }
        a { color: #667eea; text-decoration: none; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 18px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .empty { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><?= SITE_NAME ?></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="products.php" class="nav-item active"><i class="fas fa-box"></i> Products</a>
            <a href="sales.php" class="nav-item"><i class="fas fa-shopping-cart"></i> Sales</a>
            <a href="customers.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            <a href="suppliers.php" class="nav-item"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="categories.php" class="nav-item"><i class="fas fa-tags"></i> Categories</a>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="nav-item"><i class="fas fa-user-cog"></i> Users</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
            <?php endif; ?>
            <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><i class="fas fa-user"></i></div>
                <div><?= h($_SESSION['full_name']) ?></div>
            </div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main">
        <div class="header">
            <h1>Products</h1>
            <button class="btn" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Product</button>
        </div>
        
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?>"><?= h($msg['msg']) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <table>
                    <thead>
                        <tr><th>SKU</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= h($p['sku']) ?></td>
                            <td><?= h($p['name']) ?></td>
                            <td><?= h($p['category_name'] ?? '-') ?></td>
                            <td><?= formatPeso($p['price']) ?></td>
                            <td><?= $p['stock_quantity'] ?></td>
                            <td>
                                <?php if ($p['stock_quantity'] == 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif ($p['stock_quantity'] <= $p['reorder_level']): ?>
                                <span class="badge badge-warning">Low Stock</span>
                                <?php else: ?>
                                <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm" onclick='editProduct(<?= json_encode($p) ?>)'>Edit</button>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="empty">No products found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Product</h2>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group"><label>SKU *</label><input type="text" name="sku" required></div>
                <div class="form-group"><label>Name *</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                <div class="form-group"><label>Category</label>
                    <select name="category_id">
                        <option value="">Select</option>
                        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Supplier</label>
                    <select name="supplier_id">
                        <option value="">Select</option>
                        <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Price *</label><input type="number" name="price" step="0.01" required></div>
                <div class="form-group"><label>Cost Price</label><input type="number" name="cost_price" step="0.01"></div>
                <div class="form-group"><label>Stock</label><input type="number" name="stock_quantity" value="0"></div>
                <div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" value="10"></div>
                <div class="form-actions">
                    <button type="button" class="btn" style="background:#999" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group"><label>SKU *</label><input type="text" name="sku" id="edit_sku" required></div>
                <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" id="edit_description" rows="2"></textarea></div>
                <div class="form-group"><label>Category</label>
                    <select name="category_id" id="edit_category_id">
                        <option value="">Select</option>
                        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Supplier</label>
                    <select name="supplier_id" id="edit_supplier_id">
                        <option value="">Select</option>
                        <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Price *</label><input type="number" name="price" id="edit_price" step="0.01" required></div>
                <div class="form-group"><label>Cost Price</label><input type="number" name="cost_price" id="edit_cost_price" step="0.01"></div>
                <div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" id="edit_reorder_level"></div>
                <div class="form-actions">
                    <button type="button" class="btn" style="background:#999" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    function editProduct(p) {
        document.getElementById('edit_id').value = p.id;
        document.getElementById('edit_sku').value = p.sku;
        document.getElementById('edit_name').value = p.name;
        document.getElementById('edit_description').value = p.description || '';
        document.getElementById('edit_category_id').value = p.category_id || '';
        document.getElementById('edit_supplier_id').value = p.supplier_id || '';
        document.getElementById('edit_price').value = p.price;
        document.getElementById('edit_cost_price').value = p.cost_price || '';
        document.getElementById('edit_reorder_level').value = p.reorder_level;
        openModal('editModal');
    }
    </script>
</body>
</html>