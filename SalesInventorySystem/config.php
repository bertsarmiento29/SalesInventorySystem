<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sales_inventory_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'Sales Inventory System');

// Start session
session_start();

// Database Connection
function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Show message
function showMessage($msg, $type = 'success') {
    $_SESSION['msg'] = $msg;
    $_SESSION['msg_type'] = $type;
}

// Get message
function getMsg() {
    if (isset($_SESSION['msg'])) {
        $m = $_SESSION['msg'];
        $t = $_SESSION['msg_type'] ?? 'success';
        unset($_SESSION['msg'], $_SESSION['msg_type']);
        return ['msg' => $m, 'type' => $t];
    }
    return null;
}

// Get current user
function getUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

// Check role
function isAdmin() {
    $u = getUser();
    return $u && $u['role'] === 'admin';
}

// Require admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: login.php");
        exit;
    }
}

// Format currency
function formatPeso($amount) {
    return '₱' . number_format($amount, 2);
}

// Sanitize
function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Format datetime
function formatDateTime($date) {
    if (empty($date)) return '-';
    return date('M d, Y g:i A', strtotime($date));
}

// Time ago
function timeAgo($date) {
    if (empty($date)) return '-';
    $time = strtotime($date);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hour(s) ago';
    if ($diff < 172800) return 'Yesterday';
    return date('M d, g:i A', $time);
}