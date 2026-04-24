<?php
require 'config.php';
requireLogin();
$user = getUser();
$msg = getMsg();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; background: #f5f7fa; }
        
        .sidebar { width: 250px; background: #1e293b; color: white; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; font-size: 18px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { flex: 1; padding: 16px 0; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: #667eea; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-info { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .user-avatar { width: 40px; height: 40px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .logout { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .logout:hover { color: white; }
        
        .main { margin-left: 250px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { color: #1e293b; font-size: 24px; }
        .date { color: #666; }
        
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.blue { background: #e0e7ff; color: #667eea; }
        .stat-icon.green { background: #d1fae5; color: #10b981; }
        .stat-icon.orange { background: #fef3c7; color: #f59e0b; }
        .stat-icon.purple { background: #ede9fe; color: #8b5cf6; }
        .stat h3 { font-size: 13px; color: #666; font-weight: 500; }
        .stat p { font-size: 24px; font-weight: 700; color: #1e293b; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .card-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { font-size: 16px; color: #1e293b; }
        .btn-sm { padding: 6px 12px; background: #f1f5f9; color: #666; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; }
        .card-body { padding: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .empty { text-align: center; color: #999; padding: 40px; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #e0e7ff; color: #3730a3; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><?= SITE_NAME ?></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="products.php" class="nav-item"><i class="fas fa-box"></i> Products</a>
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
                <div>
                    <div><?= h($user['full_name']) ?></div>
                    <small style="color:rgba(255,255,255,0.6)"><?= ucfirst($user['role']) ?></small>
                </div>
            </div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main">
        <div class="header">
            <h1>Dashboard</h1>
            <span class="date"><?= date('F d, Y') ?></span>
        </div>
        
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?>"><?= h($msg['msg']) ?></div>
        <?php endif; ?>
        
        <?php
        try {
            $db = db();
            
            $products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $customers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
            $today = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_orders WHERE DATE(created_at)=CURDATE() AND status='completed'")->fetchColumn();
            $month = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_orders WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND status='completed'")->fetchColumn();
            
            $lowStock = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level")->fetchColumn();
            
            $recent = $db->query("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.id ORDER BY so.created_at DESC LIMIT 5")->fetchAll();
            
            // Recent activities (combine sales, products, customers)
            $activities = [];
            
            // Recent sales
            $sales = $db->query("SELECT so.id, 'sale' as type, so.order_number as title, COALESCE(c.name, 'Walk-in') as description, so.total_amount as amount, so.created_at, u.full_name as user_name
                FROM sales_orders so 
                LEFT JOIN customers c ON so.customer_id = c.id
                LEFT JOIN users u ON so.user_id = u.id
                ORDER BY so.created_at DESC LIMIT 5")->fetchAll();
            foreach ($sales as $s) $activities[] = $s;
            
            // Recent products added
            $productsAdded = $db->query("SELECT id, 'product' as type, name as title, description, price as amount, created_at, 'System' as user_name
                FROM products ORDER BY created_at DESC LIMIT 3")->fetchAll();
            foreach ($productsAdded as $p) $activities[] = $p;
            
            // Recent customers
            $custAdded = $db->query("SELECT id, 'customer' as type, name as title, email as description, created_at, 'System' as user_name
                FROM customers ORDER BY created_at DESC LIMIT 3")->fetchAll();
            foreach ($custAdded as $c) $activities[] = $c;
            
            // Sort all by date
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            $activities = array_slice($activities, 0, 8);
            
        } catch (Exception $e) {
            $products = $customers = $today = $month = $lowStock = 0;
            $recent = [];
            $activities = [];
        }
        ?>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                <div><h3>Products</h3><p><?= $products ?></p></div>
            </div>
            <div class="stat">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div><h3>Customers</h3><p><?= $customers ?></p></div>
            </div>
            <div class="stat">
                <div class="stat-icon orange"><i class="fas fa-money-bill"></i></div>
                <div><h3>Today's Sales</h3><p><?= formatPeso($today) ?></p></div>
            </div>
            <div class="stat">
                <div class="stat-icon purple"><i class="fas fa-calendar"></i></div>
                <div><h3>This Month</h3><p><?= formatPeso($month) ?></p></div>
            </div>
        </div>
        
        <?php if ($lowStock > 0): ?>
        <div class="alert alert-error">
            <strong>Low Stock Alert:</strong> <?= $lowStock ?> products need restocking. 
            <a href="products.php?filter=low">View</a>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="card">
                <div class="card-header">
                    <h2>Recent Sales</h2>
                    <a href="sales.php" class="btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent)): ?>
                    <p class="empty">No sales yet</p>
                    <?php else: ?>
                    <table>
                        <tr><th>Order</th><th>Customer</th><th>Total</th><th>Date</th></tr>
                        <?php foreach ($recent as $s): ?>
                        <tr>
                            <td><a href="sales.php?view=<?= $s['id'] ?>"><?= h($s['order_number']) ?></a></td>
                            <td><?= h($s['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= formatPeso($s['total_amount']) ?></td>
                            <td><?= timeAgo($s['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Recent Activities</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                    <p class="empty">No recent activities</p>
                    <?php else: ?>
                    <table>
                        <tr><th>Activity</th><th>Details</th><th>Amount</th><th>Time</th></tr>
                        <?php foreach ($activities as $a): ?>
                        <tr>
                            <td>
                                <?php if ($a['type'] === 'sale'): ?>
                                <span class="badge badge-success"><i class="fas fa-shopping-cart"></i> Sale</span>
                                <?php elseif ($a['type'] === 'product'): ?>
                                <span class="badge badge-info"><i class="fas fa-box"></i> Product</span>
                                <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-user"></i> Customer</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($a['title']) ?></td>
                            <td><?= isset($a['amount']) ? formatPeso($a['amount']) : '-' ?></td>
                            <td><?= timeAgo($a['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="grid" style="margin-top:24px">
            <div class="card">
                <div class="card-header">
                    <h2>Top Products (30 days)</h2>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $top = $db->query("SELECT p.name, SUM(soi.quantity) as sold, SUM(soi.subtotal) as revenue
                            FROM sales_order_items soi
                            JOIN products p ON soi.product_id = p.id
                            JOIN sales_orders so ON soi.order_id = so.id
                            WHERE so.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND so.status='completed'
                            GROUP BY p.id ORDER BY sold DESC LIMIT 5")->fetchAll();
                    } catch (Exception $e) { $top = []; }
                    ?>
                    <?php if (empty($top)): ?>
                    <p class="empty">No sales data</p>
                    <?php else: ?>
                    <table>
                        <tr><th>Product</th><th>Sold</th><th>Revenue</th></tr>
                        <?php foreach ($top as $t): ?>
                        <tr>
                            <td><?= h($t['name']) ?></td>
                            <td><?= $t['sold'] ?></td>
                            <td><?= formatPeso($t['revenue']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>