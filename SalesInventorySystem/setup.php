<?php
// Auto setup database
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sales_inventory_system';

echo "<!DOCTYPE html><html><head><title>Setup - Sales Inventory</title>
<style>body{font-family:'Segoe UI';padding:40px;background:#f5f7fa;}
.box{background:white;padding:30px;border-radius:12px;max-width:600px;margin:auto;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h1{color:#1e293b;}h2{color:#667eea;margin-top:20px;}
.success{color:#10b981;padding:12px;background:#d1fae5;border-radius:6px;margin:10px 0;}
.error{color:#ef4444;padding:12px;background:#fee2e2;border-radius:6px;margin:10px 0;}
.btn{display:inline-block;background:#667eea;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;margin-top:20px;}</style></head><body>
<div class='box'><h1>Setting up Database...</h1>";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<div class='success'>✓ Database created</div>";
    
    $pdo->exec("USE $dbname");
    
    // Tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin','staff') DEFAULT 'staff',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Users table</div>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Categories table</div>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Suppliers table</div>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        category_id INT,
        supplier_id INT,
        price DECIMAL(10,2) NOT NULL,
        cost_price DECIMAL(10,2) DEFAULT 0,
        stock_quantity INT DEFAULT 0,
        reorder_level INT DEFAULT 10,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Products table</div>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Customers table</div>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        customer_id INT,
        user_id INT NOT NULL,
        subtotal DECIMAL(10,2) DEFAULT 0,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        total_amount DECIMAL(10,2) DEFAULT 0,
        tax_rate DECIMAL(5,2) DEFAULT 10,
        status ENUM('pending','completed','cancelled') DEFAULT 'completed',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Sales orders table</div>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Sales order items table</div>";
    
    // Insert default users (plain password - will work)
    $pdo->exec("INSERT IGNORE INTO users (username,email,password,full_name,role) VALUES 
        ('admin','admin@test.com','admin123','Administrator','admin'),
        ('staff','staff@test.com','admin123','Store Staff','staff')");
    echo "<div class='success'>✓ Default users</div>";
    
    // Insert sample data
    $pdo->exec("INSERT IGNORE INTO categories (name,description) VALUES 
        ('Electronics','Electronic devices'),
        ('Office Supplies','Office and school supplies'),
        ('Furniture','Office and home furniture'),
        ('Accessories','Computer accessories')");
    
    $pdo->exec("INSERT IGNORE INTO suppliers (name,contact_person,email,phone) VALUES 
        ('Tech Supplies Inc','John Smith','john@tech.com','091234567890'),
        ('Office World','Maria Garcia','maria@office.com','091234567891')");
    
    $pdo->exec("INSERT IGNORE INTO products (sku,name,category_id,supplier_id,price,cost_price,stock_quantity,reorder_level) VALUES 
        ('ELEC-001','Laptop Computer',1,1,45000,35000,15,5),
        ('ELEC-002','Wireless Mouse',1,1,450,250,50,20),
        ('OFF-001','Ballpoint Pens (Box)',2,2,150,80,100,30),
        ('OFF-002','A4 Paper (Ream)',2,2,280,200,200,50),
        ('FURN-001','Office Chair',3,1,3500,2500,20,5),
        ('ACC-001','USB Hub 4-Port',4,1,350,180,45,15),
        ('ACC-002','Laptop Bag',4,1,850,500,25,10)");
    
    $pdo->exec("INSERT IGNORE INTO customers (name,email,phone,address) VALUES 
        ('John Doe','john@email.com','091234567800','123 Main St'),
        ('Jane Smith','jane@email.com','091234567801','456 Oak Ave'),
        ('ABC Corporation','purchasing@abc.com','091234567802','789 Business Park')");
    
    echo "<div class='success'>✓ Sample data</div>";
    
    echo "<h2>Setup Complete!</h2>";
    echo "<a href='login.php' class='btn'>Go to Login</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";