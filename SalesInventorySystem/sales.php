<?php
require 'config.php';
requireLogin();
$msg = getMsg();

// Handle new sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sale') {
    try {
        $db = db();
        $db->beginTransaction();
        
        $items = json_decode($_POST['items'] ?? '[]', true);
        if (empty($items)) throw new Exception("No items in cart");
        
        // Check stock availability
        foreach ($items as $it) {
            $stmt = $db->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
            $stmt->execute([$it['id']]);
            $product = $stmt->fetch();
            if ($product['stock_quantity'] < $it['quantity']) {
                throw new Exception("Not enough stock for: " . $product['name'] . " (Available: " . $product['stock_quantity'] . ")");
            }
        }
        
        $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        $subtotal = 0;
        foreach ($items as $it) $subtotal += $it['price'] * $it['quantity'];
        $discount = floatval($_POST['discount'] ?? 0);
        $tax_rate = 10;
        $tax = ($subtotal - $discount) * ($tax_rate / 100);
        $total = $subtotal - $discount + $tax;
        
        $stmt = $db->prepare("INSERT INTO sales_orders (order_number, customer_id, user_id, subtotal, tax_amount, discount_amount, total_amount, tax_rate) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$order_number, $_POST['customer_id'] ?: null, $_SESSION['user_id'], $subtotal, $tax, $discount, $total, $tax_rate]);
        $order_id = $db->lastInsertId();
        
        foreach ($items as $it) {
            $sub = $it['price'] * $it['quantity'];
            $db->prepare("INSERT INTO sales_order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)")->execute([$order_id, $it['id'], $it['quantity'], $it['price'], $sub]);
            $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$it['quantity'], $it['id']]);
        }
        
        $db->commit();
        showMessage("Sale completed successfully! Order: $order_number");
        header("Location: sales.php?view=$order_id");
        exit;
    } catch (Exception $e) {
        $msg = ['msg' => 'Error: ' . $e->getMessage(), 'type' => 'error'];
    }
}

// View order
$view_order = null;
if (isset($_GET['view'])) {
    try {
        $view_order = db()->query("SELECT so.*, c.name as customer_name, u.full_name as staff_name 
            FROM sales_orders so 
            LEFT JOIN customers c ON so.customer_id = c.id 
            LEFT JOIN users u ON so.user_id = u.id 
            WHERE so.id = " . intval($_GET['view']))->fetch();
        if ($view_order) {
            $view_items = db()->query("SELECT soi.*, p.name, p.sku FROM sales_order_items soi JOIN products p ON soi.product_id = p.id WHERE soi.order_id = " . $view_order['id'])->fetchAll();
        }
    } catch (Exception $e) {}
}

// Get data
try {
    $db = db();
    $orders = $db->query("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id ORDER BY so.created_at DESC LIMIT 50")->fetchAll();
    $products = $db->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name")->fetchAll();
    $customers = $db->query("SELECT * FROM customers ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $orders = $products = $customers = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f5f7fa; }
        .sidebar { width: 250px; background: #1e293b; color: white; position: fixed; height: 100vh; }
        .sidebar-header { padding: 20px; font-size: 18px; font-weight: 600; }
        .sidebar-nav { flex: 1; padding: 16px 0; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.7); text-decoration: none; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: #667eea; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .logout { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .main { margin-left: 250px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { color: #1e293b; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #10b981; }
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
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .grid { display: grid; grid-template-columns: 1fr 400px; gap: 20px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
        .product-card { border: 1px solid #eee; border-radius: 8px; padding: 12px; cursor: pointer; transition: 0.2s; }
        .product-card:hover { border-color: #667eea; background: #f8fafc; }
        .product-card h4 { font-size: 14px; margin-bottom: 4px; }
        .product-card p { font-size: 12px; color: #666; }
        .product-card .price { font-size: 16px; font-weight: 600; color: #667eea; }
        
        .cart { position: sticky; top: 20px; }
        .cart-items { max-height: 300px; overflow-y: auto; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .cart-item button { width: 24px; height: 24px; padding: 0; }
        .cart-total { padding: 16px; background: #f8fafc; border-radius: 8px; margin-top: 12px; }
        .cart-total .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .cart-total .total { font-size: 18px; font-weight: 700; }
        
        .invoice { background: white; padding: 40px; max-width: 600px; margin: auto; border-radius: 12px; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
        .invoice h1 { color: #667eea; }
        
        @media print {
            body { background: white; }
            .sidebar, .header .btn, .main .header { display: none !important; }
            .main { margin: 0; padding: 0; }
            .invoice { box-shadow: none; padding: 20px; }
        }
        .empty { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><?= SITE_NAME ?></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products</a>
            <a href="sales.php" class="nav-item active"><i class="fas fa-shopping-cart"></i> Sales</a>
            <a href="customers.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            <a href="suppliers.php" class="nav-item"><i class="fas fa-truck"></i> Suppliers</a>
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
        <div class="header">
            <h1><?= $view_order ? 'Order Details' : 'Sales' ?></h1>
            <div style="display:flex;gap:10px">
                <?php if ($view_order): ?>
                <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print</button>
                <a href="sales.php" class="btn">Back</a>
                <?php else: ?>
                <a href="?new=1" class="btn btn-success"><i class="fas fa-plus"></i> New Sale</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($msg): ?><div class="alert alert-<?= $msg['type'] ?>"><?= h($msg['msg']) ?></div><?php endif; ?>
        
        <?php if (isset($_GET['new']) && !$view_order): ?>
        <div class="grid">
            <div>
                <h3 style="margin-bottom:16px">Select Products</h3>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                    <div class="product-card" onclick="addToCart(<?= $p['id'] ?>, '<?= h($p['name']) ?>', <?= $p['price'] ?>, <?= $p['stock_quantity'] ?>)">
                        <h4><?= h($p['name']) ?></h4>
                        <p>SKU: <?= h($p['sku']) ?> | Stock: <?= $p['stock_quantity'] ?></p>
                        <div class="price"><?= formatPeso($p['price']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="cart">
                <div class="card">
                    <div class="card-body">
                        <h3>Cart</h3>
                        <form method="POST" id="saleForm">
                            <input type="hidden" name="action" value="create_sale">
                            <input type="hidden" name="items" id="cartItems">
                            <div class="cart-items" id="cartItemsContainer">
                                <p class="empty">Click products to add</p>
                            </div>
                            <div class="cart-total">
                                <div class="row"><span>Subtotal:</span><span id="cartSub">₱0.00</span></div>
                                <div class="row"><span>Discount:</span><input type="number" name="discount" id="discount" value="0" style="width:80px" onchange="calc()"></div>
                                <div class="row"><span>Tax (10%):</span><span id="cartTax">₱0.00</span></div>
                                <div class="row total"><span>Total:</span><span id="cartTotal">₱0.00</span></div>
                            </div>
                            <div class="form-group" style="margin-top:16px">
                                <label>Customer (optional)</label>
                                <select name="customer_id" style="width:100%;padding:8px">
                                    <option value="">Walk-in Customer</option>
                                    <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success" style="width:100%;margin-top:12px" id="submitBtn" disabled>Complete Sale</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($view_order): ?>
        <div class="invoice">
            <div class="invoice-header">
                <div><h1>INVOICE</h1><p><?= SITE_NAME ?></p></div>
                <div style="text-align:right">
                    <p><strong>Order #:</strong> <?= h($view_order['order_number']) ?></p>
                    <p><strong>Date:</strong> <?= date('F d, Y g:i A', strtotime($view_order['created_at'])) ?></p>
                    <p><strong>Status:</strong> <span class="badge badge-<?= $view_order['status']=='completed'?'success':'warning' ?>"><?= ucfirst($view_order['status']) ?></span></p>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                <div><h4>Bill To:</h4><p><?= h($view_order['customer_name'] ?? 'Walk-in Customer') ?></p></div>
                <div><h4>Served By:</h4><p><?= h($view_order['staff_name']) ?></p></div>
            </div>
            <table>
                <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                    <?php foreach ($view_items as $it): ?>
                    <tr><td><?= h($it['name']) ?></td><td><?= $it['quantity'] ?></td><td><?= formatPeso($it['unit_price']) ?></td><td><?= formatPeso($it['subtotal']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:20px;text-align:right">
                <p>Subtotal: <?= formatPeso($view_order['subtotal']) ?></p>
                <p>Tax: <?= formatPeso($view_order['tax_amount']) ?></p>
                <?php if ($view_order['discount_amount'] > 0): ?><p>Discount: -<?= formatPeso($view_order['discount_amount']) ?></p><?php endif; ?>
                <p style="font-size:20px;font-weight:700">Total: <?= formatPeso($view_order['total_amount']) ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <table>
                    <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><a href="?view=<?= $o['id'] ?>"><?= h($o['order_number']) ?></a></td>
                            <td><?= h($o['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= formatPeso($o['total_amount']) ?></td>
                            <td><span class="badge badge-<?= $o['status']=='completed'?'success':'warning' ?>"><?= $o['status'] ?></span></td>
                            <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                            <td><a href="?view=<?= $o['id'] ?>" class="btn btn-sm">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?><tr><td colspan="6" class="empty">No orders</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    let cart = [];
    function addToCart(id, name, price, stock) {
        let p = cart.find(x => x.id === id);
        if (!p) {
            if (stock > 0) cart.push({id, name, price, quantity: 1, stock});
            else alert('Out of stock!');
        } else if (p.quantity < stock) {
            p.quantity++;
        } else {
            alert('Max stock reached!');
        }
        renderCart();
    }
    function updateQty(id, delta) {
        let p = cart.find(x => x.id === id);
        if (p) { p.quantity += delta; if (p.quantity <= 0) cart = cart.filter(x => x.id !== id); }
        renderCart();
    }
    function renderCart() {
        let c = document.getElementById('cartItemsContainer');
        if (cart.length === 0) { c.innerHTML = '<p class="empty">Click products to add</p>'; document.getElementById('submitBtn').disabled = true; }
        else {
            c.innerHTML = cart.map(p => `<div class="cart-item"><span>${p.name} x${p.quantity}</span><div><button onclick="updateQty(${p.id},-1)">-</button> ${p.quantity} <button onclick="updateQty(${p.id},1)">+</button></div></div>`).join('');
            document.getElementById('submitBtn').disabled = false;
        }
        calc();
    }
    function calc() {
        let sub = cart.reduce((s, p) => s + p.price * p.quantity, 0);
        let disc = parseFloat(document.getElementById('discount').value) || 0;
        let tax = (sub - disc) * 0.10;
        let total = sub - disc + tax;
        document.getElementById('cartSub').innerText = '₱' + sub.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('cartTax').innerText = '₱' + tax.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('cartTotal').innerText = '₱' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('cartItems').value = JSON.stringify(cart);
    }
    </script>
</body>
</html>