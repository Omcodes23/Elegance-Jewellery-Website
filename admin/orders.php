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

// Define variables
$search = "";
$filter_status = "";
$filter_date_from = "";
$filter_date_to = "";
$sort = "date_desc"; // Default sorting
$page = 1; // Default page
$limit = 10; // Items per page

// Handle search, filtering, and sorting
if(isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if(isset($_GET["status"]) && !empty($_GET["status"])) {
    $filter_status = trim($_GET["status"]);
}

if(isset($_GET["date_from"]) && !empty($_GET["date_from"])) {
    $filter_date_from = trim($_GET["date_from"]);
}

if(isset($_GET["date_to"]) && !empty($_GET["date_to"])) {
    $filter_date_to = trim($_GET["date_to"]);
}

if(isset($_GET["sort"]) && in_array($_GET["sort"], ["date_asc", "date_desc", "total_asc", "total_desc"])) {
    $sort = $_GET["sort"];
}

if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"] > 0) {
    $page = (int)$_GET["page"];
}

// Calculate offset for pagination
$offset = ($page - 1) * $limit;

// Prepare base query
$base_query = "FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.id 
              WHERE 1=1";

// Add search condition if search parameter exists
if(!empty($search)) {
    $base_query .= " AND (o.order_number LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                   OR o.id = '" . mysqli_real_escape_string($conn, $search) . "'
                   OR CONCAT(c.first_name, ' ', c.last_name) LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                   OR c.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

// Add status filter if selected
if(!empty($filter_status)) {
    $base_query .= " AND o.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

// Add date range filter if selected
if(!empty($filter_date_from)) {
    $base_query .= " AND DATE(o.created_at) >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}

if(!empty($filter_date_to)) {
    $base_query .= " AND DATE(o.created_at) <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}

// Add sorting
$order_by = "";
switch($sort) {
    case "date_asc":
        $order_by = "o.created_at ASC";
        break;
    case "date_desc":
        $order_by = "o.created_at DESC";
        break;
    case "total_asc":
        $order_by = "o.total_amount ASC";
        break;
    case "total_desc":
        $order_by = "o.total_amount DESC";
        break;
    default:
        $order_by = "o.created_at DESC";
}

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total " . $base_query;
$total_records = 0;
if($result = mysqli_query($conn, $count_sql)) {
    if($row = mysqli_fetch_assoc($result)) {
        $total_records = $row["total"];
    }
    mysqli_free_result($result);
}

// Calculate total pages
$total_pages = ceil($total_records / $limit);

// Fetch orders with pagination
$orders = [];
$sql = "SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.email as customer_email " 
      . $base_query . " ORDER BY " . $order_by . " LIMIT " . $offset . ", " . $limit;

if($result = mysqli_query($conn, $sql)) {
    while($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_free_result($result);
}

// Update order status if requested
if(isset($_POST["update_status"]) && isset($_POST["order_id"]) && isset($_POST["status"])) {
    $order_id = $_POST["order_id"];
    $status = $_POST["status"];
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
        
        if(mysqli_stmt_execute($stmt)) {
            // Redirect to refresh page
            header("location: orders.php");
            exit();
        } else {
            $update_error = "Error updating order status.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get all available order statuses
$order_statuses = ["Pending", "Processing", "Shipped", "Delivered", "Cancelled", "Refunded"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Elegance Jewelry</title>
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
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Filter and Search Bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-group label {
            margin-right: 10px;
            font-weight: 500;
        }
        
        .filter-select, .filter-input {
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
        }
        
        .search-form {
            display: flex;
            flex-grow: 1;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px 0 0 4px;
            font-family: inherit;
        }
        
        .search-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        /* Table Styles */
        .content-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            overflow-x: auto;
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
        
        .order-status {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
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
        
        .action-cell {
            display: flex;
            gap: 5px;
        }
        
        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--dark-color);
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .pagination span.current {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Modal Styles */
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
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .close-modal {
            font-size: 1.5rem;
            cursor: pointer;
            background: none;
            border: none;
            color: var(--grey-color);
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Alert Styles */
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
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--grey-color);
        }
        
        /* Responsive Styles */
        @media screen and (max-width: 992px) {
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-select, .filter-input, .search-form {
                width: 100%;
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
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            /* Switch to card view for mobile */
            .orders-table thead {
                display: none;
            }
            
            .orders-table, .orders-table tbody, .orders-table tr, .orders-table td {
                display: block;
                width: 100%;
            }
            
            .orders-table tr {
                margin-bottom: 15px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 10px;
            }
            
            .orders-table td {
                display: flex;
                justify-content: space-between;
                padding: 10px 15px;
                text-align: right;
                border-bottom: 1px solid #eee;
            }
            
            .orders-table td:last-child {
                border-bottom: none;
            }
            
            .orders-table td:before {
                content: attr(data-label);
                font-weight: 600;
                float: left;
                text-align: left;
            }
            
            .action-cell {
                justify-content: flex-end;
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
                    <h1>Orders Management</h1>
                </div>
            </div>
            
            <?php if(isset($update_error)): ?>
            <div class="alert alert-danger">
                <?php echo $update_error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter and Search Bar -->
            <div class="filter-bar">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search orders by order number, customer..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach($order_statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo ($filter_status == $status) ? 'selected' : ''; ?>>
                            <?php echo $status; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">From:</label>
                    <input type="date" name="date_from" id="date_from" class="filter-input" value="<?php echo $filter_date_from; ?>" onchange="this.form.submit()">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">To:</label>
                    <input type="date" name="date_to" id="date_to" class="filter-input" value="<?php echo $filter_date_to; ?>" onchange="this.form.submit()">
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="date_desc" <?php echo ($sort == 'date_desc') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="date_asc" <?php echo ($sort == 'date_asc') ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="total_desc" <?php echo ($sort == 'total_desc') ? 'selected' : ''; ?>>Amount (High-Low)</option>
                        <option value="total_asc" <?php echo ($sort == 'total_asc') ? 'selected' : ''; ?>>Amount (Low-High)</option>
                    </select>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="content-card">
                <?php if(count($orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td data-label="Order ID"><?php echo $order['order_number'] ?? $order['id']; ?></td>
                            <td data-label="Order Date"><?php echo date("M d, Y h:i A", strtotime($order['created_at'])); ?></td>
                            <td data-label="Customer">
                                <?php 
                                if(isset($order['customer_name']) && !empty($order['customer_name'])) {
                                    echo htmlspecialchars($order['customer_name']) . '<br>';
                                    echo '<small>' . htmlspecialchars($order['customer_email']) . '</small>';
                                } else {
                                    echo 'Guest Order';
                                }
                                ?>
                            </td>
                            <td data-label="Total Amount"><?php echo formatIndianCurrency($order['total_amount']); ?></td>
                            <td data-label="Payment Method"><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td data-label="Status">
                                <?php
                                $status_class = 'status-' . strtolower($order['status']);
                                echo '<span class="order-status ' . $status_class . '">' . $order['status'] . '</span>';
                                ?>
                            </td>
                            <td data-label="Actions" class="action-cell">
                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm" onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- <a href="print-invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-sm" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a> -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&sort=<?php echo urlencode($sort); ?>">First</a>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&sort=<?php echo urlencode($sort); ?>">Prev</a>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate range of pages to show
                    $range = 2; // Show 2 pages before and after current page
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    for($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&sort=<?php echo urlencode($sort); ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&sort=<?php echo urlencode($sort); ?>">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="empty-state">
                    <p>No orders found matching your criteria.</p>
                </div>
                <?php endif; ?>
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
                <form method="post" id="statusForm">
                    <input type="hidden" name="order_id" id="orderIdInput">
                    <div class="form-group">
                        <label for="statusSelect">Status:</label>
                        <select name="status" id="statusSelect" class="filter-select" style="width: 100%; margin-top: 5px;">
                            <?php foreach($order_statuses as $status): ?>
                            <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                        <button type="submit" name="update_status" class="btn">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('orderIdInput').value = orderId;
            document.getElementById('statusSelect').value = currentStatus;
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