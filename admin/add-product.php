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

// Check if products table has category_id column
function columnExists($conn, $tableName, $columnName) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return (mysqli_num_rows($result) > 0);
}

// Create uploads directory if it doesn't exist
$uploads_dir = "../uploads/products/";
if(!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Add category_id column if it doesn't exist
if(!columnExists($conn, "products", "category_id")) {
    $sql = "ALTER TABLE products ADD COLUMN category_id INT";
    mysqli_query($conn, $sql);
    
    // Try to add foreign key if categories table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
    if(mysqli_num_rows($result) > 0) {
        $sql = "ALTER TABLE products ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
        mysqli_query($conn, $sql);
    }
}

// Check if categories table exists, create if it doesn't
$result = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
if(mysqli_num_rows($result) == 0) {
    // Create categories table
    $sql = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
    // Add a default category
    $sql = "INSERT INTO categories (name, description) VALUES ('Uncategorized', 'Default category')";
    mysqli_query($conn, $sql);
}

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}

// Define variables
$name = $description = $price = $stock = $category_id = "";
$featured = $new = 0;
$image = "";
$errors = [];
$success_message = "";
$db_error = "";

// Get all categories
$categories = [];
$sql = "SELECT * FROM categories ORDER BY name ASC";
if($result = mysqli_query($conn, $sql)) {
    while($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_free_result($result);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_product"])) {
    // Validate name
    if(empty(trim($_POST["name"]))) {
        $errors["name"] = "Please enter product name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))) {
        $errors["description"] = "Please enter product description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate price
    if(empty(trim($_POST["price"]))) {
        $errors["price"] = "Please enter product price.";
    } elseif(!is_numeric($_POST["price"]) || $_POST["price"] <= 0) {
        $errors["price"] = "Please enter a valid price.";
    } else {
        $price = trim($_POST["price"]);
    }
    
    // Validate old price (optional)
    if(!empty(trim($_POST["old_price"]))) {
        if(!is_numeric($_POST["old_price"]) || $_POST["old_price"] <= 0) {
            $errors["old_price"] = "Please enter a valid old price.";
        } else {
            $old_price = trim($_POST["old_price"]);
        }
    } else {
        $old_price = null;
    }
    
    // Validate stock
    if(!isset($_POST["stock"]) || $_POST["stock"] == "") {
        $errors["stock"] = "Please enter stock quantity.";
    } elseif(!is_numeric($_POST["stock"]) || $_POST["stock"] < 0) {
        $errors["stock"] = "Please enter a valid stock quantity.";
    } else {
        $stock = trim($_POST["stock"]);
    }
    
    // Validate SKU (optional)
    if(!empty(trim($_POST["sku"]))) {
        $sku = trim($_POST["sku"]);
    }
    
    // Validate category
    if(empty($_POST["category_id"])) {
        $errors["category_id"] = "Please select a category.";
    } else {
        // Get the category name based on the category ID
        $category_id = $_POST["category_id"];
        $check_sql = "SELECT name FROM categories WHERE id = ?";
        if($check_stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $category_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $category_name);
            
            if(mysqli_stmt_fetch($check_stmt)) {
                $category = $category_name;
            } else {
                $errors["category_id"] = "Selected category does not exist.";
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $errors["category_id"] = "Failed to verify category.";
        }
    }
    
    // Get featured and new status
    $featured = isset($_POST["featured"]) ? 1 : 0;
    $new = isset($_POST["new"]) ? 1 : 0;
    
    // Handle image upload
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if(!in_array($_FILES["image"]["type"], $allowed_types)) {
            $errors["image"] = "Only JPG, PNG, and GIF files are allowed.";
        } elseif($_FILES["image"]["size"] > $max_size) {
            $errors["image"] = "Image size should be less than 5MB.";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = "../uploads/products/";
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $file_name = uniqid() . "." . $file_ext;
            $target_file = $upload_dir . $file_name;
            
            // Upload the file
            if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image = "uploads/products/" . $file_name;
            } else {
                $errors["image"] = "Failed to upload image. Error: " . $_FILES["image"]["error"];
            }
        }
    }
    
    // If no errors, insert product into database
    if(empty($errors)) {
        // Check if we need to include old_price and SKU in the query
        $columns = "name, description, price, category, image, stock, featured, new, category_id";
        $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?";
        $types = "ssdssiiii";
        $params = array($name, $description, $price, $category, $image, $stock, $featured, $new, $category_id);
        
        // Add old_price if provided
        if(isset($old_price) && $old_price !== null) {
            $columns .= ", old_price";
            $placeholders .= ", ?";
            $types .= "d";
            $params[] = $old_price;
        }
        
        // Add SKU if provided
        if(isset($sku) && !empty($sku)) {
            $columns .= ", sku";
            $placeholders .= ", ?";
            $types .= "s";
            $params[] = $sku;
        }
        
        // Insert product
        $sql = "INSERT INTO products ($columns) VALUES ($placeholders)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Create dynamic parameter binding
            $bind_params = array();
            $bind_params[] = &$types;
            for($i = 0; $i < count($params); $i++) {
                $bind_params[] = &$params[$i];
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_params);
            
            if(mysqli_stmt_execute($stmt)) {
                // Product added successfully, redirect to products page
                $success_message = "Product added successfully!";
                // Redirect after a short delay
                header("refresh:2;url=products.php");
            } else {
                $db_error = "Database Error: " . mysqli_error($conn);
                $errors["db"] = "Something went wrong. Please try again later. " . $db_error;
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $db_error = "Prepare Error: " . mysqli_error($conn);
            $errors["db"] = "Failed to prepare statement. " . $db_error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Elegance Jewelry</title>
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
        
        .btn-secondary {
            background-color: var(--grey-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--dark-color);
        }
        
        /* Form Styles */
        .content-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        
        .form-group {
            flex: 1 0 300px;
            margin: 10px;
        }
        
        .form-group.full-width {
            flex: 1 0 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-check input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .error-text {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: var(--grey-color);
            margin-top: 5px;
        }
        
        /* Image Preview Styles */
        .image-preview {
            margin-top: 10px;
            display: none;
            max-width: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive Styles */
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
            
            .form-group {
                flex: 1 0 100%;
            }
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
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Debug box */
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 12px;
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
                    <h1>Add New Product</h1>
                </div>
                <div class="header-actions">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Add Product Form -->
            <div class="content-card">
                <?php if(isset($errors["db"])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors["db"]; ?>
                    <?php if(!empty($db_error)): ?>
                    <br>Technical details: <?php echo $db_error; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($errors["debug"])): ?>
                <div class="debug-info">
                    <?php echo $errors["debug"]; ?>
                </div>
                <?php endif; ?>
                
                <?php if(empty($categories)): ?>
                <div class="alert alert-danger">
                    <strong>Warning:</strong> No categories found. Please <a href="categories.php">add categories</a> before adding products.
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if(isset($errors["name"])): ?>
                            <span class="error-text"><?php echo $errors["name"]; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($price); ?>" required>
                            <?php if(isset($errors["price"])): ?>
                            <span class="error-text"><?php echo $errors["price"]; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="old_price">Old Price (₹) (Optional)</label>
                            <input type="number" name="old_price" id="old_price" class="form-control" step="0.01" min="0" value="<?php echo isset($old_price) ? htmlspecialchars($old_price) : ''; ?>">
                            <?php if(isset($errors["old_price"])): ?>
                            <span class="error-text"><?php echo $errors["old_price"]; ?></span>
                            <?php endif; ?>
                            <span class="help-text">Leave empty if there is no discounted price</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(isset($errors["category_id"])): ?>
                            <span class="error-text"><?php echo $errors["category_id"]; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="sku">SKU (Optional)</label>
                            <input type="text" name="sku" id="sku" class="form-control" value="<?php echo isset($sku) ? htmlspecialchars($sku) : ''; ?>">
                            <?php if(isset($errors["sku"])): ?>
                            <span class="error-text"><?php echo $errors["sku"]; ?></span>
                            <?php endif; ?>
                            <span class="help-text">Product Stock Keeping Unit</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" name="stock" id="stock" class="form-control" min="0" value="<?php echo htmlspecialchars($stock); ?>" required>
                            <?php if(isset($errors["stock"])): ?>
                            <span class="error-text"><?php echo $errors["stock"]; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="description">Product Description</label>
                            <textarea name="description" id="description" class="form-control" required><?php echo htmlspecialchars($description); ?></textarea>
                            <?php if(isset($errors["description"])): ?>
                            <span class="error-text"><?php echo $errors["description"]; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <span class="help-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</span>
                            <?php if(isset($errors["image"])): ?>
                            <span class="error-text"><?php echo $errors["image"]; ?></span>
                            <?php endif; ?>
                            <img id="imagePreview" src="#" alt="Image Preview" class="image-preview">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="featured" id="featured" <?php echo ($featured) ? 'checked' : ''; ?>>
                                <label for="featured">Featured Product</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="new" id="new" <?php echo ($new) ? 'checked' : ''; ?>>
                                <label for="new">New Product</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <button type="submit" name="add_product" class="btn">
                                <i class="fas fa-plus-circle"></i> Add Product
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Preview uploaded image
        function previewImage(input) {
            var preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>