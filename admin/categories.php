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
$category_name = $description = "";
$image = "";
$errors = [];
$success_message = "";

// Create upload directory if it doesn't exist
$uploads_dir = "../uploads/categories/";
if(!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if we're adding a new category
    if(isset($_POST["add_category"])) {
        // Validate category name
        if(empty(trim($_POST["category_name"]))) {
            $errors["category_name"] = "Please enter a category name.";
        } else {
            $category_name = trim($_POST["category_name"]);
            
            // Check if category already exists
            $sql = "SELECT id FROM categories WHERE name = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $category_name);
                
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) > 0) {
                        $errors["category_name"] = "This category already exists.";
                    }
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        // Get description (optional)
        $description = trim($_POST["description"] ?? "");
        
        // Handle image upload
        if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
            $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if(!in_array($_FILES["image"]["type"], $allowed_types)) {
                $errors["image"] = "Only JPG, PNG, and GIF files are allowed.";
            } elseif($_FILES["image"]["size"] > $max_size) {
                $errors["image"] = "Image size should be less than 5MB.";
            } else {
                // Generate unique filename
                $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                $file_name = uniqid() . "." . $file_ext;
                $target_file = $uploads_dir . $file_name;
                
                // Upload the file
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = "uploads/categories/" . $file_name;
                } else {
                    $errors["image"] = "Failed to upload image. Error: " . $_FILES["image"]["error"];
                }
            }
        }
        
        // If no errors, insert category
        if(empty($errors)) {
            // Check if description and image columns exist
            $check_columns_query = "SHOW COLUMNS FROM categories LIKE 'description'";
            $result = mysqli_query($conn, $check_columns_query);
            if(mysqli_num_rows($result) == 0) {
                // Add description column
                $alter_query = "ALTER TABLE categories ADD COLUMN description TEXT";
                mysqli_query($conn, $alter_query);
            }
            
            $check_columns_query = "SHOW COLUMNS FROM categories LIKE 'image'";
            $result = mysqli_query($conn, $check_columns_query);
            if(mysqli_num_rows($result) == 0) {
                // Add image column
                $alter_query = "ALTER TABLE categories ADD COLUMN image VARCHAR(255)";
                mysqli_query($conn, $alter_query);
            }
            
            // Insert with description and image
            $sql = "INSERT INTO categories (name, description, image) VALUES (?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sss", $category_name, $description, $image);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success_message = "Category added successfully!";
                    $category_name = ""; // Clear the form
                    $description = "";
                    $image = "";
                } else {
                    $errors["db"] = "Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Check if we're deleting a category
    if(isset($_POST["delete_category"]) && !empty($_POST["delete_category"])) {
        $category_id = $_POST["delete_category"];
        
        // Check if category is in use by any products
        $in_use = false;
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $category_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if($row = mysqli_fetch_assoc($result)) {
                $in_use = ($row["count"] > 0);
            }
            
            mysqli_stmt_close($stmt);
        }
        
        if($in_use) {
            $errors["delete"] = "Cannot delete category because it is used by one or more products.";
        } else {
            // Get category image path before deletion
            $image_path = "";
            $sql = "SELECT image FROM categories WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $category_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if($row = mysqli_fetch_assoc($result)) {
                    $image_path = $row["image"];
                }
                
                mysqli_stmt_close($stmt);
            }
            
            // Delete category from database
            $sql = "DELETE FROM categories WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $category_id);
                
                if(mysqli_stmt_execute($stmt)) {
                    // Delete category image if exists
                    if(!empty($image_path) && file_exists("../" . $image_path)) {
                        unlink("../" . $image_path);
                    }
                } else {
                    $errors["delete"] = "Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Check if we're editing a category
    if(isset($_POST["edit_category"]) && !empty($_POST["category_id"]) && !empty($_POST["category_name"])) {
        $category_id = $_POST["category_id"];
        $category_name = trim($_POST["category_name"]);
        $description = trim($_POST["description"] ?? "");
        $old_image = $_POST["old_image"] ?? "";
        
        // Validate category name
        if(empty($category_name)) {
            $errors["edit_name"] = "Please enter a category name.";
        } else {
            // Check if new name already exists for another category
            $sql = "SELECT id FROM categories WHERE name = ? AND id != ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $category_name, $category_id);
                
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) > 0) {
                        $errors["edit_name"] = "This category name already exists.";
                    }
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        // Handle image upload for edit
        $image = $old_image; // Default to old image
        if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
            $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if(!in_array($_FILES["image"]["type"], $allowed_types)) {
                $errors["edit_image"] = "Only JPG, PNG, and GIF files are allowed.";
            } elseif($_FILES["image"]["size"] > $max_size) {
                $errors["edit_image"] = "Image size should be less than 5MB.";
            } else {
                // Generate unique filename
                $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                $file_name = uniqid() . "." . $file_ext;
                $target_file = $uploads_dir . $file_name;
                
                // Upload the file
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = "uploads/categories/" . $file_name;
                    
                    // Delete old image if exists
                    if(!empty($old_image) && file_exists("../" . $old_image)) {
                        unlink("../" . $old_image);
                    }
                } else {
                    $errors["edit_image"] = "Failed to upload image. Error: " . $_FILES["image"]["error"];
                }
            }
        }
        
        // If no errors, update category
        if(empty($errors)) {
            $sql = "UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssi", $category_name, $description, $image, $category_id);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success_message = "Category updated successfully!";
                } else {
                    $errors["edit"] = "Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Check if categories table exists, if not create it
$table_exists = false;
$sql = "SHOW TABLES LIKE 'categories'";
if($result = mysqli_query($conn, $sql)) {
    if(mysqli_num_rows($result) > 0) {
        $table_exists = true;
    }
    mysqli_free_result($result);
}

if(!$table_exists) {
    // Create categories table with description and image
    $sql = "CREATE TABLE categories (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if(mysqli_query($conn, $sql)) {
        $success_message = "Categories table created successfully!";
    } else {
        $errors["db"] = "Error creating categories table: " . mysqli_error($conn);
    }
} else {
    // Check if description and image columns exist
    $check_columns_query = "SHOW COLUMNS FROM categories LIKE 'description'";
    $result = mysqli_query($conn, $check_columns_query);
    if(mysqli_num_rows($result) == 0) {
        // Add description column
        $alter_query = "ALTER TABLE categories ADD COLUMN description TEXT";
        mysqli_query($conn, $alter_query);
    }
    
    $check_columns_query = "SHOW COLUMNS FROM categories LIKE 'image'";
    $result = mysqli_query($conn, $check_columns_query);
    if(mysqli_num_rows($result) == 0) {
        // Add image column
        $alter_query = "ALTER TABLE categories ADD COLUMN image VARCHAR(255)";
        mysqli_query($conn, $alter_query);
    }
}

// Fetch all categories
$categories = [];
$sql = "SELECT * FROM categories ORDER BY name ASC";
if($result = mysqli_query($conn, $sql)) {
    while($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Elegance Jewelry</title>
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
        
        /* Card Styles */
        .content-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* Form Styles */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        
        .form-group {
            flex: 1 0 300px;
            margin: 10px;
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
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .action-cell {
            display: flex;
            gap: 5px;
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--grey-color);
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
        }
        
        /* Image Preview */
        .image-preview {
            max-width: 100px;
            margin-top: 10px;
            border-radius: 4px;
            display: none;
        }
        
        .category-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
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
                <a href="customers.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                
                <h3>Settings</h3>
                <a href="categories.php" class="menu-item active">
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
                    <h1>Categories Management</h1>
                </div>
            </div>
            
            <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($errors["db"]) || isset($errors["delete"])): ?>
            <div class="alert alert-danger">
                <?php echo isset($errors["db"]) ? $errors["db"] : $errors["delete"]; ?>
            </div>
            <?php endif; ?>
            
            <!-- Add Category Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Add New Category</h2>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" name="category_name" id="category_name" class="form-control" value="<?php echo htmlspecialchars($category_name); ?>" required>
                            <?php if(isset($errors["category_name"])): ?>
                            <span class="error-text"><?php echo $errors["category_name"]; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Category Image</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                            <span class="help-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</span>
                            <?php if(isset($errors["image"])): ?>
                            <span class="error-text"><?php echo $errors["image"]; ?></span>
                            <?php endif; ?>
                            <img id="imagePreview" src="#" alt="Image Preview" class="image-preview">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="description">Category Description</label>
                            <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="add_category" class="btn">
                                <i class="fas fa-plus-circle"></i> Add Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Categories List Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Categories List</h2>
                </div>
                
                <?php if(count($categories) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <?php if(!empty($category['image']) && file_exists("../" . $category['image'])): ?>
                                    <img src="../<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                                    <?php else: ?>
                                    <span class="no-image">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td>
                                    <?php 
                                        echo !empty($category['description']) 
                                            ? (strlen($category['description']) > 100 
                                                ? htmlspecialchars(substr($category['description'], 0, 100)) . '...' 
                                                : htmlspecialchars($category['description'])) 
                                            : 'No description'; 
                                    ?>
                                </td>
                                <td class="action-cell">
                                    <button class="btn btn-sm" onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', '<?php echo addslashes($category['description'] ?? ''); ?>', '<?php echo addslashes($category['image'] ?? ''); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <p>No categories found. Add your first category using the form above.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Category</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <input type="hidden" name="old_image" id="edit_old_image">
                    
                    <div class="form-group">
                        <label for="edit_category_name">Category Name</label>
                        <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                        <?php if(isset($errors["edit_name"])): ?>
                        <span class="error-text"><?php echo $errors["edit_name"]; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Category Description</label>
                        <textarea name="description" id="edit_description" class="form-control"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_image">Category Image</label>
                        <input type="file" name="image" id="edit_image" class="form-control" accept="image/*" onchange="previewImage(this, 'editImagePreview')">
                        <span class="help-text">Leave empty to keep current image</span>
                        <?php if(isset($errors["edit_image"])): ?>
                        <span class="error-text"><?php echo $errors["edit_image"]; ?></span>
                        <?php endif; ?>
                        
                        <div id="current_image_container" style="margin-top: 10px;">
                            <label>Current Image:</label>
                            <div id="current_image"></div>
                        </div>
                        
                        <img id="editImagePreview" src="#" alt="Image Preview" class="image-preview">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_category" class="btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category "<span id="delete_category_name"></span>"?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="post">
                    <input type="hidden" name="delete_category" id="delete_category_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Preview uploaded image
        function previewImage(input, previewId) {
            var preview = document.getElementById(previewId);
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
        
        // Edit Modal Functions
        function openEditModal(id, name, description, image) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_old_image').value = image || '';
            
            // Show current image if available
            var currentImageContainer = document.getElementById('current_image_container');
            var currentImage = document.getElementById('current_image');
            
            if (image && image !== '') {
                currentImageContainer.style.display = 'block';
                currentImage.innerHTML = '<img src="../' + image + '" alt="Current Image" style="max-width: 100px; border-radius: 4px;">';
            } else {
                currentImageContainer.style.display = 'none';
            }
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Delete Modal Functions
        function openDeleteModal(id, name) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            var editModal = document.getElementById('editModal');
            var deleteModal = document.getElementById('deleteModal');
            
            if (event.target == editModal) {
                closeEditModal();
            }
            
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>