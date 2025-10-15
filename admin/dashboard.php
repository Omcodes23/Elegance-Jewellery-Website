<?php
// Start session
session_start();

// Authentication check: Ensure user is logged in as admin
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../admin-login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Include functions file
require_once "includes/functions.php";

// Page title
$page_title = "Dashboard";

// Get dashboard stats
$stats = getDashboardStats($conn);

// Extract stats
$product_count = $stats['products'];
$order_count = $stats['orders'];
$customer_count = $stats['customers'];
$revenue = $stats['revenue'];
$recent_orders = $stats['recent_orders'];
$low_stock = $stats['low_stock'];
$out_of_stock = $stats['out_of_stock'];
$best_sellers = $stats['best_sellers'];

// Function to format currency
function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2, '.', ',');
}

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Elegance Jewelry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5a1a32;
            --secondary-color: #eec34e;
            --light-color: #f0f0f0;
            --dark-color: #333;
            --accent-color: #edd6dc;
            --grey-color: #777;
            --border-color: #e1e1e1;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #f5f5f5;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            color: var(--primary-color);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu h3 {
            padding: 0 20px;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 10px;
            color: var(--grey-color);
        }
        
        .menu-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .menu-item.active, .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--primary-color);
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
        }
        
        .admin-info span {
            margin-right: 10px;
        }
        
        .logout-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .logout-btn:hover {
            background-color: var(--dark-color);
        }
        
        /* Dashboard Widgets */
        .dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .widget {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .widget-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }
        
        .widget-icon.products {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .widget-icon.orders {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .widget-icon.customers {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .widget-icon.revenue {
            background-color: #fce4ec;
            color: #e91e63;
        }
        
        .widget-info h3 {
            font-size: 0.9rem;
            color: var(--grey-color);
            margin-bottom: 5px;
        }
        
        .widget-info h2 {
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        /* Recent Orders Table */
        .recent-orders {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .recent-orders h2 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var (--dark-color);
            display: flex;
            align-items: center;
        }
        
        .recent-orders h2 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .orders-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .orders-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status.completed {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .status.processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status.shipped {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status.cancelled {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .view-btn {
            padding: 5px 10px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.8rem;
        }
        
        .view-btn:hover {
            background-color: var(--primary-color);
        }
        
        /* Quick Actions */
        .quick-actions {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .quick-actions h2 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .quick-actions h2 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: var(--dark-color);
        }
        
        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Responsive Styles */
        @media screen and (max-width: 1200px) {
            .dashboard-widgets {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media screen and (max-width: 992px) {
            .action-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h2, .sidebar-menu h3 {
                display: none;
            }
            
            .menu-item span {
                display: none;
            }
            
            .menu-item i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .dashboard-widgets {
                grid-template-columns: 1fr;
            }
            
            .orders-table thead {
                display: none;
            }
            
            .orders-table tbody tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
            }
            
            .orders-table tbody td {
                display: block;
                text-align: right;
                border-bottom: 1px solid #eee;
                padding: 10px 15px;
            }
            
            .orders-table tbody td:before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
            }
            
            .orders-table tbody td:last-child {
                border-bottom: none;
            }
        }
        
        @media screen and (max-width: 576px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <div class="sidebar-menu">
                <h3>Main</h3>
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-gem"></i>
                    <span>Products</span>
                </a>
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="customers.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                
                <h3>Settings</h3>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1>Dashboard</h1>
                </div>
                <div class="header-actions">
                    <div class="admin-info">
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <!-- Dashboard Widgets -->
            <div class="dashboard-widgets">
                <div class="widget">
                    <div class="widget-icon products">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div class="widget-info">
                        <h3>Total Products</h3>
                        <h2><?php echo $product_count; ?></h2>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="widget-info">
                        <h3>Total Orders</h3>
                        <h2><?php echo $order_count; ?></h2>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="widget-info">
                        <h3>Total Customers</h3>
                        <h2><?php echo $customer_count; ?></h2>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon revenue">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="widget-info">
                        <h3>Total Revenue</h3>
                        <h2><?php echo formatIndianCurrency($revenue); ?></h2>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="recent-orders">
                <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
                <?php if(!empty($recent_orders)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td data-label="Order ID">#<?php echo $order['id']; ?></td>
                                <td data-label="Customer"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td data-label="Date"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
                                <td data-label="Total"><?php echo formatIndianCurrency($order['total_amount']); ?></td>
                                <td data-label="Status">
                                    <span class="status <?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td data-label="Action">
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="view-btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No recent orders found.</p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add-product.php" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Product</span>
                    </a>
                    <a href="orders.php" class="action-btn">
                        <i class="fas fa-truck"></i>
                        <span>Manage Orders</span>
                    </a>
                    <a href="categories.php" class="action-btn">
                        <i class="fas fa-tags"></i>
                        <span>Manage Categories</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>