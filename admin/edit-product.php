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

// Check if column exists function
function columnExists($conn, $tableName, $columnName) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return (mysqli_num_rows($result) > 0);
}

// Define variables
$name = $description = $price = $stock = $category_id = "";
$featured = $new = 0;
$image = "";
$errors = [];
$success_message = "";
$new_image = "";

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}

// Check if ID parameter exists
if(empty($_GET["id"])) {
    header("location: products.php");
    exit();
}

// Initialize variables that might not exist in the product record
if (!isset($product['sku'])) {
    $sku = "";
} else {
    $sku = $product["sku"];
}

// Get product details
$id = $_GET["id"];
$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $sql);
if($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 1) {
        $product = mysqli_fetch_assoc($result);
        
        // Set variables from product
        $name = $product["name"];
        $description = $product["description"];
        $price = $product["price"];
        $old_price = isset($product["old_price"]) ? $product["old_price"] : null;
        $stock = $product["stock"];
        $category_id = $product["category_id"];
        $category = $product["category_name"];
        $sku = isset($product["sku"]) ? $product["sku"] : "";
        $image = $product["image"];
        $featured = $product["featured"];
        $new = $product["new"];
        
        // Initialize product categories - removing the query to non-existent table
        $productCategories = [];
        if (isset($category_id) && !empty($category_id)) {
            $productCategories[] = $category_id;
        }
    } else {
        // Product not found
        header("location: products.php");
        exit();
    }
    
    mysqli_stmt_close($stmt);
} else {
    $errors["db"] = "Something went wrong. Please try again later. Error: " . mysqli_error($conn);
}

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
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_product"])) {
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
    if(isset($_POST["sku"])) {
        $sku = trim($_POST["sku"]);
    } else {
        $sku = "";
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
                $new_image = "uploads/products/" . $file_name;
                
                // Delete old image if exists
                if(!empty($image) && file_exists("../" . $image)) {
                    unlink("../" . $image);
                }
            } else {
                $errors["image"] = "Failed to upload image. Error: " . $_FILES["image"]["error"];
            }
        }
    }

    // If no errors, update product in database
    if(empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Prepare a proper parameterized query
            $sql = "UPDATE products SET 
                    name = ?,
                    description = ?,
                    price = ?,
                    category = ?,
                    stock = ?,
                    featured = ?,
                    new = ?,
                    sku = ?";
            
            // Add optional fields
            if(isset($old_price) && $old_price !== null) {
                $sql .= ", old_price = ?";
            } else {
                $sql .= ", old_price = NULL";
            }
            
            if(!empty($new_image)) {
                $sql .= ", image = ?";
            }
            
            // Complete the SQL statement
            $sql .= " WHERE id = ?";
            
            // Prepare statement
            $stmt = mysqli_prepare($conn, $sql);
            
            if($stmt) {
                // Create the binding parameters string and array
                $bind_params = "ssdsiiss";  // string, string, double, string, int, int, int, string
                $param_values = [$name, $description, $price, $category, $stock, $featured, $new, $sku];
                
                // Add additional parameters if needed
                if(isset($old_price) && $old_price !== null) {
                    $bind_params .= "d";  // Add double for old_price
                    $param_values[] = $old_price;
                }
                
                if(!empty($new_image)) {
                    $bind_params .= "s";  // Add string for image
                    $param_values[] = $new_image;
                }
                
                // Add the ID parameter
                $bind_params .= "i";  // Add integer for id
                $param_values[] = $id;
                
                // Bind parameters dynamically
                mysqli_stmt_bind_param($stmt, $bind_params, ...$param_values);
                
                // Execute the statement
                if(mysqli_stmt_execute($stmt)) {
                    // Success - also update category_id
                    if(columnExists($conn, "products", "category_id")) {
                        $update_sql = "UPDATE products SET category_id = ? WHERE id = ?";
                        $cat_stmt = mysqli_prepare($conn, $update_sql);
                        mysqli_stmt_bind_param($cat_stmt, "ii", $category_id, $id);
                        mysqli_stmt_execute($cat_stmt);
                        mysqli_stmt_close($cat_stmt);
                    }
                    
                    // Commit the transaction
                    mysqli_commit($conn);
                    
                    $success_message = "Product updated successfully!";
                    header("refresh:2;url=products.php");
                } else {
                    // Rollback on error
                    mysqli_rollback($conn);
                    $errors["db"] = "Failed to update product: " . mysqli_error($conn);
                }
                
                mysqli_stmt_close($stmt);
            } else {
                // Rollback on error
                mysqli_rollback($conn);
                $errors["db"] = "Failed to prepare statement: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            // Rollback on exception
            mysqli_rollback($conn);
            $errors["db"] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Elegance Jewelry</title>
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
        .current-image {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
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
                    <h1>Edit Product: <?php echo htmlspecialchars($name); ?></h1>
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
            
            <!-- Edit Product Form -->
            <div class="content-card">
                <?php if(isset($errors["db"])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors["db"]; ?>
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post" enctype="multipart/form-data">
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
                            <label for="sku">SKU (Stock Keeping Unit)</label>
                            <input type="text" name="sku" id="sku" class="form-control" value="<?php echo htmlspecialchars($sku); ?>">
                            <span class="help-text">Optional unique identifier for inventory management</span>
                        </div>
                        
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
                            <?php if(!empty($image) && file_exists("../" . $image)): ?>
                            <div>
                                <p>Current Image:</p>
                                <img src="../<?php echo $image; ?>" alt="<?php echo htmlspecialchars($name); ?>" class="current-image">
                            </div>
                            <?php endif; ?>
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
                            <button type="submit" name="update_product" class="btn">
                                <i class="fas fa-save"></i> Update Product
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