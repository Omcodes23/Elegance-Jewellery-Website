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
$sort = "id_desc"; // Default sorting
$page = 1; // Default page
$limit = 10; // Items per page

// Handle search and sorting
if(isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if(isset($_GET["sort"]) && in_array($_GET["sort"], ["id_asc", "id_desc", "name_asc", "name_desc", "date_asc", "date_desc"])) {
    $sort = $_GET["sort"];
}

if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"] > 0) {
    $page = (int)$_GET["page"];
}

// Calculate offset for pagination
$offset = ($page - 1) * $limit;

// Prepare base query
$base_query = "FROM customers WHERE 1=1";

// Add search condition if search parameter exists
if(!empty($search)) {
    $base_query .= " AND (name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                   OR email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                   OR phone LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

// Add sorting
$order_by = "";
switch($sort) {
    case "id_asc":
        $order_by = "id ASC";
        break;
    case "id_desc":
        $order_by = "id DESC";
        break;
    case "name_asc":
        $order_by = "name ASC";
        break;
    case "name_desc":
        $order_by = "name DESC";
        break;
    case "date_asc":
        $order_by = "created_at ASC";
        break;
    case "date_desc":
        $order_by = "created_at DESC";
        break;
    default:
        $order_by = "id DESC";
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

// Fetch customers with pagination
$customers = [];
$sql = "SELECT * " . $base_query . " ORDER BY " . $order_by . " LIMIT " . $offset . ", " . $limit;

if($result = mysqli_query($conn, $sql)) {
    while($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
    mysqli_free_result($result);
}

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}

// Get customer order statistics
function getCustomerStats($conn, $customer_id) {
    $stats = [
        'total_orders' => 0,
        'total_spent' => 0
    ];
    
    // Get total orders
    $sql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $stats['total_orders'] = $row['count'];
        }
        mysqli_stmt_close($stmt);
    }
    
    // Get total spent
    $sql = "SELECT SUM(total_amount) as total FROM orders WHERE customer_id = ? AND status = 'Completed'";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $stats['total_spent'] = $row['total'] ?? 0;
        }
        mysqli_stmt_close($stmt);
    }
    
    return $stats;
}

// Handle customer deletion
if(isset($_POST['delete_customer']) && !empty($_POST['customer_id'])) {
    $customer_id = $_POST['customer_id'];
    
    // Check if customer has orders
    $has_orders = false;
    $sql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $has_orders = ($row['count'] > 0);
        }
        mysqli_stmt_close($stmt);
    }
    
    if($has_orders) {
        $delete_error = "Cannot delete customer with existing orders. Please archive the customer instead.";
    } else {
        // Delete customer
        $sql = "DELETE FROM customers WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            if(mysqli_stmt_execute($stmt)) {
                header("Location: customers.php?deleted=success");
                exit;
            } else {
                $delete_error = "Error deleting customer: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Check for deletion success message
$delete_success = false;
if(isset($_GET['deleted']) && $_GET['deleted'] == 'success') {
    $delete_success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Elegance Jewelry</title>
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
        
        .customers-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .customers-table th, .customers-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .customers-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .customers-table tbody tr:hover {
            background-color: #f5f5f5;
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
            .customers-table thead {
                display: none;
            }
            
            .customers-table, .customers-table tbody, .customers-table tr, .customers-table td {
                display: block;
                width: 100%;
            }
            
            .customers-table tr {
                margin-bottom: 15px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 10px;
            }
            
            .customers-table td {
                display: flex;
                justify-content: space-between;
                padding: 10px 15px;
                text-align: right;
                border-bottom: 1px solid #eee;
            }
            
            .customers-table td:last-child {
                border-bottom: none;
            }
            
            .customers-table td:before {
                content: attr(data-label);
                font-weight: 600;
                float: left;
                text-align: left;
            }
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
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
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
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="customers.php" class="menu-item active">
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
                    <h1>Customers</h1>
                </div>
                <!-- <div class="header-actions">
                    <a href="add-customer.php" class="btn">
                        <i class="fas fa-plus-circle"></i> Add Customer
                    </a>
                </div> -->
            </div>
            
            <?php if(isset($delete_error)): ?>
            <div class="alert alert-danger">
                <?php echo $delete_error; ?>
            </div>
            <?php endif; ?>
            
            <?php if($delete_success): ?>
            <div class="alert alert-success">
                Customer deleted successfully.
            </div>
            <?php endif; ?>
            
            <!-- Filter and Search Bar -->
            <div class="filter-bar">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="id_desc" <?php echo ($sort == 'id_desc') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="id_asc" <?php echo ($sort == 'id_asc') ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                    </select>
                </div>
            </div>
            
            <!-- Customers Table -->
            <div class="content-card">
                <?php if(count($customers) > 0): ?>
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($customers as $customer): 
                            $stats = getCustomerStats($conn, $customer['id']);
                            // Fix for the undefined 'name' array key error on line 560
                            $customer_name = isset($customer['name']) ? $customer['name'] : (isset($customer['first_name']) ? $customer['first_name'] . ' ' . $customer['last_name'] : 'N/A');
                        ?>
                        <tr>
                            <td data-label="ID"><?php echo $customer['id']; ?></td>
                            <td data-label="Name"><?php echo isset($customer['name']) ? htmlspecialchars($customer['name']) : (isset($customer['first_name']) ? htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) : 'N/A'); ?></td>
                            <td data-label="Email"><?php echo isset($customer['email']) ? htmlspecialchars($customer['email']) : 'N/A'; ?></td>
                            <td data-label="Phone"><?php echo isset($customer['phone']) ? htmlspecialchars($customer['phone']) : 'N/A'; ?></td>
                            <td data-label="Registration Date"><?php echo isset($customer['created_at']) ? date("M d, Y", strtotime($customer['created_at'])) : 'N/A'; ?></td>
                            <td data-label="Orders"><?php echo $stats['total_orders']; ?></td>
                            <td data-label="Total Spent"><?php echo formatIndianCurrency($stats['total_spent']); ?></td>
                            <td data-label="Actions">
                                <!-- <a href="customer-detail.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a> -->
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo isset($customer['name']) ? addslashes($customer['name']) : 'this customer'; ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>">First</a>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>">Prev</a>
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
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="empty-state">
                    <p>No customers found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the customer "<span id="deleteCustomerName"></span>"?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="closeModal()">Cancel</button>
                <form method="post" id="deleteForm">
                    <input type="hidden" name="customer_id" id="deleteCustomerId">
                    <button type="submit" name="delete_customer" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function confirmDelete(id, name) {
            document.getElementById('deleteCustomerId').value = id;
            document.getElementById('deleteCustomerName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>