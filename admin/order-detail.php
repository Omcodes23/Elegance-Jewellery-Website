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

// Check if order ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: orders.php");
    exit;
}

$order_id = $_GET["id"];

// Get order details with customer information
$order = [];
$sql = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address, c.city, c.state, c.zip, c.country
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $order = mysqli_fetch_assoc($result);
        } else {
            // No order found with this ID, initialize with default values
            $order = [
                "id" => $order_id,
                "order_number" => $order_id,
                "status" => "Pending",
                "payment_method" => "Unknown",
                "total_amount" => 0,
                "created_at" => date("Y-m-d H:i:s"),
                "shipping_amount" => 0,
                "tax_amount" => 0
            ];
            // Set warning message
            $warning_message = "Order data could not be found in the database.";
        }
    } else {
        $warning_message = "Error executing query: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    // Query preparation failed, initialize with default values
    $order = [
        "id" => $order_id,
        "order_number" => $order_id,
        "status" => "Pending",
        "payment_method" => "Unknown",
        "total_amount" => 0,
        "created_at" => date("Y-m-d H:i:s"),
        "shipping_amount" => 0,
        "tax_amount" => 0
    ];
    $warning_message = "Error preparing database query: " . mysqli_error($conn);
}

// Get order items with product details
$order_items = [];
$sql = "SELECT oi.*, p.name as product_name, p.image as product_image, p.price as product_price, p.category
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $order_items[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// If no order items were found, check if this could be an issue with a missing order_items table
if(empty($order_items) && !isset($warning_message)) {
    // Query to check if the table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
    if(mysqli_num_rows($check_table) == 0) {
        $warning_message = "The order_items table does not exist in the database.";
    } else {
        $warning_message = "No items found for this order.";
    }
}

// Update order status if requested
if(isset($_POST["update_status"]) && isset($_POST["status"])) {
    $status = $_POST["status"];
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
        
        if(mysqli_stmt_execute($stmt)) {
            // Update order status in the order array
            $order["status"] = $status;
            $success_message = "Order status updated successfully.";
        } else {
            $update_error = "Error updating order status.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}

// Get all available order statuses
$order_statuses = ["Pending", "Processing", "Shipped", "Delivered", "Cancelled", "Refunded"];

// Format customer name
$customer_name = '';
if(isset($order['first_name']) && isset($order['last_name'])) {
    $customer_name = $order['first_name'] . ' ' . $order['last_name'];
}

// Format customer address
$customer_address = '';
if(isset($order['address'])) {
    $customer_address = $order['address'];
    if(!empty($order['city'])) {
        $customer_address .= ', ' . $order['city'];
    }
    if(!empty($order['state'])) {
        $customer_address .= ', ' . $order['state'];
    }
    if(!empty($order['zip'])) {
        $customer_address .= ', ' . $order['zip'];
    }
    if(!empty($order['country'])) {
        $customer_address .= ', ' . $order['country'];
    }
}

// Calculate order summary values
$subtotal = 0;
foreach($order_items as $item) {
    $subtotal += ($item['product_price'] ?? 0) * ($item['quantity'] ?? 1);
}

$shipping = $order['shipping_amount'] ?? 0;
$tax = $order['tax_amount'] ?? 0;
$total = $order['total_amount'] ?? ($subtotal + $shipping + $tax);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?> - Elegance Jewelry</title>
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
            z-index: 10;
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
        
        .btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn:hover {
            background-color: var(--primary-color);
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        /* New Order Detail Styles */
        .order-detail-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .order-card.full-width {
            grid-column: 1 / -1;
        }
        
        .order-card-header {
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-card-header h3 {
            font-size: 1.1rem;
            margin: 0;
            color: var(--primary-color);
        }
        
        .order-card-body {
            padding: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        
        .info-label {
            font-weight: 600;
            width: 140px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex: 1;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--grey-color);
            text-decoration: none;
            margin-top: 5px;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .order-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status-processing {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .status-shipped {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .status-refunded {
            background-color: #e0f2f1;
            color: #009688;
        }
        
        .change-status-btn {
            background: none;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
            font-size: 0.85rem;
            margin-left: 10px;
        }
        
        .change-status-btn:hover {
            color: var(--primary-color);
        }
        
        /* Order Items Table */
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items-table th, 
        .order-items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-items-table th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f9f9f9;
        }
        
        .order-items-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .order-items-table tfoot td {
            border-top: 1px solid var(--border-color);
            font-weight: 500;
        }
        
        .order-items-table tfoot tr.total-row td {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .product-category {
            font-size: 0.85rem;
            color: var(--grey-color);
        }
        
        .text-right {
            text-align: right;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--grey-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Payment and Shipping Info */
        .payment-info,
        .shipping-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .payment-info h4,
        .shipping-info h4 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--grey-color);
        }
        
        /* Timeline for order status */
        .order-timeline {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .timeline-title {
            margin-bottom: 15px;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 9px;
            height: 100%;
            width: 2px;
            background-color: #e0e0e0;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-dot {
            position: absolute;
            left: -30px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timeline-dot.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .timeline-dot i {
            font-size: 10px;
        }
        
        .timeline-content {
            margin-bottom: 5px;
        }
        
        .timeline-date {
            font-size: 0.8rem;
            color: var(--grey-color);
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .order-detail-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2, 
            .sidebar-menu h3 {
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
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                align-self: stretch;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            /* Responsive table */
            .order-items-table thead {
                display: none;
            }
            
            .order-items-table, 
            .order-items-table tbody, 
            .order-items-table tr {
                display: block;
                width: 100%;
            }
            
            .order-items-table tbody tr {
                margin-bottom: 20px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 10px;
            }
            
            .order-items-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
                padding: 10px;
                border: none;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .order-items-table td:last-child {
                border-bottom: none;
            }
            
            .order-items-table td::before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 10px;
                text-align: left;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-img {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .order-items-table tfoot {
                display: block;
                margin-top: 15px;
                border-top: 2px solid var(--border-color);
                padding-top: 15px;
            }
            
            .order-items-table tfoot tr {
                margin-bottom: 10px;
            }
            
            .order-items-table tfoot td {
                justify-content: space-between;
                padding: 5px 10px;
            }
            
            .order-items-table tfoot td:first-child {
                font-weight: 600;
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
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-gem"></i>
                    <span>Products</span>
                </a>
                <a href="orders.php" class="menu-item active">
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
                    <h1>Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></h1>
                    <a href="orders.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
                <!-- Removed print option button -->
            </div>
            
            <?php if(isset($update_error)): ?>
            <div class="alert alert-danger">
                <?php echo $update_error; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($warning_message)): ?>
            <div class="alert alert-warning">
                <?php echo $warning_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="order-detail-container">
                <!-- Order Information -->
                <div class="order-card">
                    <div class="order-card-header">
                        <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                    </div>
                    <div class="order-card-body">
                        <div class="info-row">
                            <div class="info-label">Order Number:</div>
                            <div class="info-value">#<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Order Date:</div>
                            <div class="info-value"><?php echo isset($order['created_at']) ? date("M d, Y h:i A", strtotime($order['created_at'])) : 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Status:</div>
                            <div class="info-value">
                                <?php if(isset($order['status'])): ?>
                                <?php
                                $status_class = 'status-' . strtolower($order['status']);
                                echo '<span class="order-status ' . $status_class . '">' . htmlspecialchars($order['status']) . '</span>';
                                ?>
                                <?php else: ?>
                                <span class="order-status">Unknown</span>
                                <?php endif; ?>
                                <button class="change-status-btn" onclick="openStatusModal()">
                                    <i class="fas fa-edit"></i> Change
                                </button>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Payment Method:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></div>
                        </div>
                        <?php if(isset($order['payment_id']) && !empty($order['payment_id'])): ?>
                        <div class="info-row">
                            <div class="info-label">Payment ID:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['payment_id']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="order-timeline">
                            <div class="timeline-title">Order Timeline</div>
                            <div class="timeline">
                                <?php 
                                $currentStatus = strtolower($order['status'] ?? 'pending');
                                $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                                foreach($statuses as $index => $status): 
                                    $isActive = in_array($currentStatus, array_slice($statuses, $index));
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo $isActive ? 'active' : ''; ?>">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <?php echo ucfirst($status); ?>
                                    </div>
                                    <?php if($status === $currentStatus): ?>
                                    <div class="timeline-date">
                                        <?php echo isset($order['created_at']) ? date("M d, Y", strtotime($order['created_at'])) : date("M d, Y"); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="order-card">
                    <div class="order-card-header">
                        <h3><i class="fas fa-user"></i> Customer Information</h3>
                    </div>
                    <div class="order-card-body">
                        <div class="info-row">
                            <div class="info-label">Name:</div>
                            <div class="info-value"><?php echo !empty($customer_name) ? htmlspecialchars($customer_name) : 'N/A'; ?></div>
                        </div>
                        <?php if(isset($order['email']) && !empty($order['email'])): ?>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($order['phone']) && !empty($order['phone'])): ?>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="shipping-info">
                            <h4>Shipping Address</h4>
                            <?php if(!empty($customer_address)): ?>
                            <p><?php echo htmlspecialchars($customer_address); ?></p>
                            <?php else: ?>
                            <p>No shipping address available</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(isset($order['billing_address']) && !empty($order['billing_address'])): ?>
                        <div class="payment-info">
                            <h4>Billing Address</h4>
                            <p><?php echo htmlspecialchars($order['billing_address']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="order-card full-width">
                    <div class="order-card-header">
                        <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                    </div>
                    <div class="order-card-body">
                        <?php if(count($order_items) > 0): ?>
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th width="50%">Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($order_items as $item): ?>
                                <tr>
                                    <td data-label="Product" class="product-cell">
                                        <?php if(!empty($item['product_image']) && file_exists("../" . $item['product_image'])): ?>
                                        <img src="../<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?>" class="product-img">
                                        <?php else: ?>
                                        <img src="../assets/images/no-image.jpg" alt="No Image" class="product-img">
                                        <?php endif; ?>
                                        <div class="product-info">
                                            <div class="product-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></div>
                                            <?php if(isset($item['category']) && !empty($item['category'])): ?>
                                            <div class="product-category"><?php echo htmlspecialchars($item['category']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="Price"><?php echo formatIndianCurrency($item['product_price'] ?? 0); ?></td>
                                    <td data-label="Quantity"><?php echo $item['quantity'] ?? 1; ?></td>
                                    <td data-label="Total"><?php echo formatIndianCurrency(($item['product_price'] ?? 0) * ($item['quantity'] ?? 1)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right">Subtotal:</td>
                                    <td><?php echo formatIndianCurrency($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right">Shipping:</td>
                                    <td><?php echo formatIndianCurrency($shipping); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right">Tax:</td>
                                    <td><?php echo formatIndianCurrency($tax); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right">Total:</td>
                                    <td><?php echo formatIndianCurrency($total); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <p><i class="fas fa-shopping-basket fa-3x"></i></p>
                            <p>No items found for this order.</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($order['notes']) && !empty($order['notes'])): ?>
                        <div class="order-notes" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                            <h4><i class="fas fa-sticky-note"></i> Order Notes</h4>
                            <p><?php echo htmlspecialchars($order['notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Update Order Status</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <div class="form-group">
                        <label for="statusSelect">Status:</label>
                        <select name="status" id="statusSelect" class="filter-select" style="width: 100%; margin-top: 5px; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
                            <?php foreach($order_statuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo (isset($order["status"]) && $order["status"] == $status) ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" onclick="closeModal()" style="background-color: #f0f0f0; color: var(--dark-color);">Cancel</button>
                        <button type="submit" name="update_status" class="btn">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openStatusModal() {
            document.getElementById('statusModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('statusModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>