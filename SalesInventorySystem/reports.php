<?php
require 'config.php';
requireLogin();

$type = $_GET['type'] ?? 'sales';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$db = db();

if ($type === 'sales') {
    $data = $db->query("SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as total 
        FROM sales_orders WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to' AND status='completed' 
        GROUP BY DATE(created_at) ORDER BY date")->fetchAll();
    $total = $db->query("SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as cnt FROM sales_orders WHERE status='completed'")->fetch();
} elseif ($type === 'inventory') {
    $data = $db->query("SELECT * FROM products ORDER BY stock_quantity ASC")->fetchAll();
    $stats = $db->query("SELECT COUNT(*) as total, SUM(stock_quantity) as stock, SUM(stock_quantity*cost_price) as value FROM products")->fetch();
} elseif ($type === 'products') {
    $data = $db->query("SELECT p.name, SUM(soi.quantity) as sold, SUM(soi.subtotal) as revenue
        FROM sales_order_items soi JOIN products p ON soi.product_id=p.id
        JOIN sales_orders so ON soi.order_id=so.id
        WHERE so.created_at BETWEEN '$date_from' AND '$date_to' AND so.status='completed'
        GROUP BY p.id ORDER BY sold DESC LIMIT 10")->fetchAll();
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_'.$type.'.csv"');
    $out = fopen('php://output', 'w');
    if ($type === 'sales') { fputcsv($out, ['Date','Orders','Total']); foreach($data as $r) fputcsv($out,[$r['date'],$r['orders'],$r['total']]); }
    elseif ($type === 'products') { fputcsv($out, ['Product','Sold','Revenue']); foreach($data as $r) fputcsv($out,[$r['name'],$r['sold'],$r['revenue']]); }
    fclose($out); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}body{display:flex;min-height:100vh;background:#f5f7fa}.sidebar{width:250px;background:#1e293b;color:white;position:fixed;height:100vh}.sidebar-header{padding:20px;font-size:18px;font-weight:600}.sidebar-nav{flex:1;padding:16px 0}.nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;color:rgba(255,255,255,0.7);text-decoration:none}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.1);color:white}.nav-item.active{background:#667eea}.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1)}.user-info{display:flex;align-items:center;gap:12px}.user-avatar{width:40px;height:40px;background:#667eea;border-radius:50%;display:flex;align-items:center;justify-content:center}.logout{color:rgba(255,255,255,0.7);text-decoration:none;display:flex;align-items:center;gap:8px}.main{margin-left:250px;flex:1;padding:30px}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.header h1{color:#1e293b}.btn{background:#667eea;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}.stat{background:white;padding:20px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08)}.stat h3{font-size:12px;color:#666;margin-bottom:8px}.stat p{font-size:24px;font-weight:700}.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08)}.card-body{padding:20px}table{width:100%;border-collapse:collapse}th,td{padding:14px;text-align:left;border-bottom:1px solid #eee}th{background:#f8fafc;font-size:12px;color:#666}.filter{display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap}.filter input,select{padding:8px;border:1px solid #ddd;border-radius:6px}
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
            <?php if (isAdmin()): ?><a href="users.php" class="nav-item"><i class="fas fa-user-cog"></i> Users</a><a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a><?php endif; ?>
            <a href="reports.php" class="nav-item active"><i class="fas fa-chart-bar"></i> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info"><div class="user-avatar"><i class="fas fa-user"></i></div><div><?= h($_SESSION['full_name']) ?></div></div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="main">
        <div class="header"><h1>Reports</h1></div>
        
        <div class="filter">
            <a href="?type=sales" class="btn" style="<?= $type=='sales'?'background:#5568d3':'' ?>">Sales</a>
            <a href="?type=inventory" class="btn" style="<?= $type=='inventory'?'background:#5568d3':'' ?>">Inventory</a>
            <a href="?type=products" class="btn" style="<?= $type=='products'?'background:#5568d3':'' ?>">Top Products</a>
            <?php if ($type !== 'inventory'): ?>
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="type" value="<?= $type ?>">
                <input type="date" name="date_from" value="<?= $date_from ?>">
                <span>to</span>
                <input type="date" name="date_to" value="<?= $date_to ?>">
                <button type="submit" class="btn">Filter</button>
            </form>
            <?php endif; ?>
            <a href="?type=<?= $type ?>&export=csv&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn">Export CSV</a>
        </div>
        
        <?php if ($type === 'sales'): ?>
        <div class="stats">
            <div class="stat"><h3>Total Sales</h3><p><?= formatPeso($total['total']) ?></p></div>
            <div class="stat"><h3>Total Orders</h3><p><?= $total['cnt'] ?></p></div>
        </div>
        <div class="card"><div class="card-body">
            <table><thead><tr><th>Date</th><th>Orders</th><th>Total</th></tr></thead>
            <tbody><?php foreach($data as $r): ?><tr><td><?= $r['date'] ?></td><td><?= $r['orders'] ?></td><td><?= formatPeso($r['total']) ?></td></tr><?php endforeach; ?></tbody></table>
        </div></div>
        <?php elseif ($type === 'inventory'): ?>
        <div class="stats">
            <div class="stat"><h3>Total Products</h3><p><?= $stats['total'] ?></p></div>
            <div class="stat"><h3>Total Stock</h3><p><?= $stats['stock'] ?></p></div>
            <div class="stat"><h3>Inventory Value</h3><p><?= formatPeso($stats['value']) ?></p></div>
        </div>
        <div class="card"><div class="card-body">
            <table><thead><tr><th>SKU</th><th>Product</th><th>Stock</th><th>Price</th><th>Status</th></tr></thead>
            <tbody><?php foreach($data as $r): ?><tr><td><?= h($r['sku']) ?></td><td><?= h($r['name']) ?></td><td><?= $r['stock_quantity'] ?></td><td><?= formatPeso($r['price']) ?></td><td><?= $r['stock_quantity']<=($r['reorder_level']??10)?'Low Stock':'OK' ?></td></tr><?php endforeach; ?></tbody></table>
        </div></div>
        <?php elseif ($type === 'products'): ?>
        <div class="card"><div class="card-body">
            <table><thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
            <tbody><?php foreach($data as $r): ?><tr><td><?= h($r['name']) ?></td><td><?= $r['sold']?:0 ?></td><td><?= formatPeso($r['revenue']?:0) ?></td></tr><?php endforeach; ?></tbody></table>
        </div></div>
        <?php endif; ?>
    </div>
</body>
</html>