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
$filter_category = "";
$sort = "id_desc"; // Default sorting
$page = 1; // Default page
$limit = 10; // Items per page

// Handle search, filtering, and sorting
if(isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if(isset($_GET["category"])) {
    $filter_category = trim($_GET["category"]);
}

if(isset($_GET["sort"]) && in_array($_GET["sort"], ["id_asc", "id_desc", "name_asc", "name_desc", "price_asc", "price_desc"])) {
    $sort = $_GET["sort"];
}

if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"] > 0) {
    $page = (int)$_GET["page"];
}

// Calculate offset for pagination
$offset = ($page - 1) * $limit;

// Prepare base query
$base_query = "FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE 1=1";

// Add search condition if search parameter exists
if(!empty($search)) {
    $base_query .= " AND (p.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                   OR p.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

// Add category filter if selected
if(!empty($filter_category)) {
    $base_query .= " AND p.category_id = '" . mysqli_real_escape_string($conn, $filter_category) . "'";
}

// Add sorting
$order_by = "";
switch($sort) {
    case "id_asc":
        $order_by = "p.id ASC";
        break;
    case "id_desc":
        $order_by = "p.id DESC";
        break;
    case "name_asc":
        $order_by = "p.name ASC";
        break;
    case "name_desc":
        $order_by = "p.name DESC";
        break;
    case "price_asc":
        $order_by = "p.price ASC";
        break;
    case "price_desc":
        $order_by = "p.price DESC";
        break;
    default:
        $order_by = "p.id DESC";
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

// Fetch products with pagination
$products = [];
$sql = "SELECT p.*, c.name as category_name " . $base_query . " ORDER BY " . $order_by . " LIMIT " . $offset . ", " . $limit;

if($result = mysqli_query($conn, $sql)) {
    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_free_result($result);
    }
} else {
    $db_error = "Query error: " . mysqli_error($conn);
}

// Get all categories for filter dropdown
$categories = [];
$sql = "SELECT id, name FROM categories ORDER BY name";
if($result = mysqli_query($conn, $sql)) {
    while($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_free_result($result);
}

// Handle product deletion
if(isset($_POST["delete_product"]) && !empty($_POST["delete_product"])) {
    $product_id = $_POST["delete_product"];
    
    // Check if product is in any order
    $in_order = false;
    $sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if($row = mysqli_fetch_assoc($result)) {
            $in_order = ($row["count"] > 0);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if($in_order) {
        $delete_error = "Cannot delete product because it is part of one or more orders.";
    } else {
        // Get product image path before deletion
        $image_path = "";
        $sql = "SELECT image FROM products WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if($row = mysqli_fetch_assoc($result)) {
                $image_path = $row["image"];
            }
            
            mysqli_stmt_close($stmt);
        }
        
        // Delete product from database
        $sql = "DELETE FROM products WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            
            if(mysqli_stmt_execute($stmt)) {
                // Delete product image if exists
                if(!empty($image_path) && file_exists("../" . $image_path)) {
                    unlink("../" . $image_path);
                }
                
                header("location: products.php");
                exit();
            } else {
                $delete_error = "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Create products table if it doesn't exist
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return (mysqli_num_rows($result) > 0);
}

if(!tableExists($conn, 'products')) {
    $sql = "CREATE TABLE products (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        category_id INT,
        image VARCHAR(255),
        featured TINYINT(1) DEFAULT 0,
        new_arrival TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )";
    
    if(!mysqli_query($conn, $sql)) {
        $table_error = "Error creating products table: " . mysqli_error($conn);
    }
}

// Check if category_id column exists
$column_exists = false;
if($result = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'category_id'")) {
    $column_exists = (mysqli_num_rows($result) > 0);
    mysqli_free_result($result);
}

if(!$column_exists) {
    // Check if 'category' column exists (old structure)
    $old_column_exists = false;
    if($result = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'category'")) {
        $old_column_exists = (mysqli_num_rows($result) > 0);
        mysqli_free_result($result);
    }
    
    $sql = "ALTER TABLE products ADD COLUMN category_id INT";
    if(mysqli_query($conn, $sql)) {
        // Add the foreign key constraint
        $sql = "ALTER TABLE products ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
        if(!mysqli_query($conn, $sql)) {
            $column_error = "Error adding foreign key: " . mysqli_error($conn);
        }
        
        // If the old column exists, migrate data
        if($old_column_exists) {
            $sql = "UPDATE products p 
                    JOIN categories c ON p.category = c.name 
                    SET p.category_id = c.id";
            if(!mysqli_query($conn, $sql)) {
                $migration_error = "Error migrating category data: " . mysqli_error($conn);
            }
        }
    } else {
        $column_error = "Error adding category_id column: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Elegance Jewelry</title>
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
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
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
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th, .products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .products-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .products-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .stock-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stock-badge.in-stock {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .stock-badge.low-stock {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .stock-badge.out-of-stock {
            background-color: #ffebee;
            color: #f44336;
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
        
        .empty-state a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
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
            
            .header-actions {
                width: 100%;
            }
            
            /* Switch to card view for mobile */
            .products-table thead {
                display: none;
            }
            
            .products-table, .products-table tbody, .products-table tr, .products-table td {
                display: block;
                width: 100%;
            }
            
            .products-table tr {
                margin-bottom: 15px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 10px;
            }
            
            .products-table td {
                display: flex;
                justify-content: space-between;
                padding: 10px 15px;
                text-align: right;
                border-bottom: 1px solid #eee;
            }
            
            .products-table td:last-child {
                border-bottom: none;
            }
            
            .products-table td:before {
                content: attr(data-label);
                font-weight: 600;
                float: left;
                text-align: left;
            }
            
            .product-img {
                margin: 0 auto;
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
                <a href="products.php" class="menu-item active">
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
                    <h1>Products Management</h1>
                </div>
                <div class="header-actions">
                    <a href="add-product.php" class="btn">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </a>
                </div>
            </div>
            
            <?php if(isset($delete_error)): ?>
            <div class="alert alert-danger">
                <?php echo $delete_error; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($table_error)): ?>
            <div class="alert alert-danger">
                <?php echo $table_error; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($column_error)): ?>
            <div class="alert alert-danger">
                <?php echo $column_error; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($migration_error)): ?>
            <div class="alert alert-danger">
                <?php echo $migration_error; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($db_error)): ?>
            <div class="alert alert-danger">
                <?php echo $db_error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter and Search Bar -->
            <div class="filter-bar">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <div class="filter-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($filter_category == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="id_desc" <?php echo ($sort == 'id_desc') ? 'selected' : ''; ?>>Newest</option>
                        <option value="id_asc" <?php echo ($sort == 'id_asc') ? 'selected' : ''; ?>>Oldest</option>
                        <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Price (Low-High)</option>
                        <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Price (High-Low)</option>
                    </select>
                </div>
            </div>
            
            <!-- Products Table -->
            <div class="content-card">
                <?php if(count($products) > 0): ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td data-label="ID"><?php echo $product['id']; ?></td>
                            <td data-label="Image">
                                <?php if(!empty($product['image']) && file_exists("../" . $product['image'])): ?>
                                <img src="../<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                                <?php else: ?>
                                <img src="../assets/images/no-image.jpg" alt="No Image" class="product-img">
                                <?php endif; ?>
                            </td>
                            <td data-label="Name"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td data-label="Price"><?php echo formatIndianCurrency($product['price']); ?></td>
                            <td data-label="Stock">
                                <?php
                                if($product['stock'] > 10) {
                                    echo '<span class="stock-badge in-stock">' . $product['stock'] . ' In Stock</span>';
                                } elseif($product['stock'] > 0) {
                                    echo '<span class="stock-badge low-stock">' . $product['stock'] . ' Low Stock</span>';
                                } else {
                                    echo '<span class="stock-badge out-of-stock">Out of Stock</span>';
                                }
                                ?>
                            </td>
                            <td data-label="Actions" class="action-cell">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')">
                                    <i class="fas fa-trash"></i>
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
                    <a href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort); ?>">First</a>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort); ?>">Prev</a>
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
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort); ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort); ?>">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="empty-state">
                    <p>No products found. <a href="add-product.php">Add your first product</a>.</p>
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
                <p>Are you sure you want to delete the product "<span id="deleteProductName"></span>"?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="closeModal()">Cancel</button>
                <form method="post">
                    <input type="hidden" name="delete_product" id="deleteProductId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function confirmDelete(id, name) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteProductName').textContent = name;
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
