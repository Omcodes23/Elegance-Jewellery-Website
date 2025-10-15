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

// Initialize variables
$site_name = $site_logo = $primary_color = $secondary_color = $currency_symbol = "";
$store_email = $store_phone = $store_address = "";
$smtp_host = $smtp_username = $smtp_password = $smtp_port = "";
$success_message = $error_message = "";

// Function to format currency in Indian format
function formatIndianCurrency($amount) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return '₹' . $formatted;
}

// Fetch current settings from database
$sql = "SELECT * FROM settings WHERE id = 1";
if($result = mysqli_query($conn, $sql)){
    if(mysqli_num_rows($result) > 0){
        $settings = mysqli_fetch_assoc($result);
        
        // Store settings data
        $site_name = $settings["site_name"] ?? '';
        $site_logo = $settings["site_logo"] ?? '';
        $primary_color = $settings["primary_color"] ?? '#5a1a32';
        $secondary_color = $settings["secondary_color"] ?? '#eec34e';
        $currency_symbol = $settings["currency_symbol"] ?? '₹';
        $store_email = $settings["store_email"] ?? '';
        $store_phone = $settings["store_phone"] ?? '';
        $store_address = $settings["store_address"] ?? '';
        $smtp_host = $settings["smtp_host"] ?? '';
        $smtp_username = $settings["smtp_username"] ?? '';
        $smtp_password = $settings["smtp_password"] ?? '';
        $smtp_port = $settings["smtp_port"] ?? '';
    }
    mysqli_free_result($result);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate and sanitize inputs
    $site_name = trim($_POST["site_name"]);
    $primary_color = trim($_POST["primary_color"]);
    $secondary_color = trim($_POST["secondary_color"]);
    $currency_symbol = trim($_POST["currency_symbol"]);
    $store_email = trim($_POST["store_email"]);
    $store_phone = trim($_POST["store_phone"]);
    $store_address = trim($_POST["store_address"]);
    $smtp_host = trim($_POST["smtp_host"]);
    $smtp_username = trim($_POST["smtp_username"]);
    $smtp_password = trim($_POST["smtp_password"]);
    $smtp_port = trim($_POST["smtp_port"]);
    
    // Handle logo upload if a new one is provided
    if(isset($_FILES["site_logo"]) && $_FILES["site_logo"]["error"] == 0){
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
        $filename = $_FILES["site_logo"]["name"];
        $filetype = $_FILES["site_logo"]["type"];
        $filesize = $_FILES["site_logo"]["size"];
        
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) die("Error: Please select a valid file format.");
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) die("Error: File size is larger than the allowed limit.");
        
        // Verify MIME type of the file
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it
            $target_dir = "../assets/images/";
            $target_file = $target_dir . basename($filename);
            
            // Generate unique filename to avoid overwriting
            $new_filename = uniqid() . "." . $ext;
            $target_file = $target_dir . $new_filename;
            
            if(move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)){
                $site_logo = "assets/images/" . $new_filename;
            } else {
                $error_message = "Error uploading your file.";
            }
        } else {
            $error_message = "Error: There was a problem with your upload. Please try again.";
        }
    } else {
        // Keep existing logo if no new one is uploaded
        $site_logo = $settings["site_logo"] ?? '';
    }
    
    // Check if settings record exists
    $check_sql = "SELECT id FROM settings WHERE id = 1";
    $exists = false;
    if($result = mysqli_query($conn, $check_sql)){
        if(mysqli_num_rows($result) > 0){
            $exists = true;
        }
        mysqli_free_result($result);
    }
    
    // Update or insert settings
    if($exists){
        // Update existing settings
        $sql = "UPDATE settings SET 
                site_name = ?, 
                site_logo = ?, 
                primary_color = ?, 
                secondary_color = ?, 
                currency_symbol = ?, 
                store_email = ?, 
                store_phone = ?, 
                store_address = ?, 
                smtp_host = ?, 
                smtp_username = ?, 
                smtp_password = ?, 
                smtp_port = ? 
                WHERE id = 1";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssssssss", 
                                  $site_name, 
                                  $site_logo, 
                                  $primary_color, 
                                  $secondary_color, 
                                  $currency_symbol, 
                                  $store_email, 
                                  $store_phone, 
                                  $store_address, 
                                  $smtp_host, 
                                  $smtp_username, 
                                  $smtp_password, 
                                  $smtp_port);
            
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Settings updated successfully!";
            } else{
                $error_message = "Error updating settings. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        // Insert new settings
        $sql = "INSERT INTO settings (id, site_name, site_logo, primary_color, secondary_color, currency_symbol, store_email, store_phone, store_address, smtp_host, smtp_username, smtp_password, smtp_port) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssssssss", 
                                  $site_name, 
                                  $site_logo, 
                                  $primary_color, 
                                  $secondary_color, 
                                  $currency_symbol, 
                                  $store_email, 
                                  $store_phone, 
                                  $store_address, 
                                  $smtp_host, 
                                  $smtp_username, 
                                  $smtp_password, 
                                  $smtp_port);
            
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Settings added successfully!";
            } else{
                $error_message = "Error adding settings. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Elegance Jewelry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $primary_color ?? '#5a1a32'; ?>;
            --secondary-color: <?php echo $secondary_color ?? '#eec34e'; ?>;
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
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Tab Styles */
        .tab-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .tab-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1rem;
            font-family: inherit;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
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
            min-height: 100px;
            resize: vertical;
        }
        
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
            border: 2px solid var(--border-color);
            vertical-align: middle;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .current-logo {
            max-width: 200px;
            max-height: 100px;
            margin-bottom: 10px;
        }
        
        .btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn:hover {
            background-color: var(--primary-color);
        }
        
        /* Responsive Styles */
        @media screen and (max-width: 992px) {
            .form-row {
                flex-direction: column;
                gap: 0;
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
            
            .tab-nav {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                flex: 1;
                text-align: center;
                padding: 10px;
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
                <a href="settings.php" class="menu-item active">
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
                    <h1>Store Settings</h1>
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Settings Tabs -->
            <div class="tab-container">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="general">General Settings</button>
                    <button class="tab-btn" data-tab="appearance">Appearance</button>
                    <button class="tab-btn" data-tab="contact">Contact Information</button>
                    <button class="tab-btn" data-tab="email">Email Settings</button>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <!-- General Settings Tab -->
                    <div class="tab-content active" id="general">
                        <div class="form-group">
                            <label for="site_name">Store Name</label>
                            <input type="text" name="site_name" id="site_name" class="form-control" value="<?php echo htmlspecialchars($site_name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_logo">Store Logo</label>
                            <?php if(!empty($site_logo)): ?>
                            <div>
                                <img src="../<?php echo htmlspecialchars($site_logo); ?>" alt="Current Logo" class="current-logo">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="site_logo" id="site_logo" class="form-control">
                            <small>Leave empty to keep the current logo. Recommended dimensions: 200x80px</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="currency_symbol">Currency Symbol</label>
                            <input type="text" name="currency_symbol" id="currency_symbol" class="form-control" value="<?php echo htmlspecialchars($currency_symbol); ?>" required>
                            <small>Examples: ₹, $, €, £</small>
                        </div>
                    </div>
                    
                    <!-- Appearance Tab -->
                    <div class="tab-content" id="appearance">
                        <div class="form-group">
                            <label for="primary_color">Primary Color</label>
                            <div style="display: flex; align-items: center;">
                                <input type="text" name="primary_color" id="primary_color" class="form-control" value="<?php echo htmlspecialchars($primary_color); ?>" required>
                                <span class="color-preview" id="primary_color_preview" style="background-color: <?php echo htmlspecialchars($primary_color); ?>;"></span>
                            </div>
                            <small>Enter a hex color code (e.g., #5a1a32)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="secondary_color">Secondary Color</label>
                            <div style="display: flex; align-items: center;">
                                <input type="text" name="secondary_color" id="secondary_color" class="form-control" value="<?php echo htmlspecialchars($secondary_color); ?>" required>
                                <span class="color-preview" id="secondary_color_preview" style="background-color: <?php echo htmlspecialchars($secondary_color); ?>;"></span>
                            </div>
                            <small>Enter a hex color code (e.g., #eec34e)</small>
                        </div>
                    </div>
                    
                    <!-- Contact Information Tab -->
                    <div class="tab-content" id="contact">
                        <div class="form-group">
                            <label for="store_email">Store Email</label>
                            <input type="email" name="store_email" id="store_email" class="form-control" value="<?php echo htmlspecialchars($store_email); ?>" required>
                            <small>This email will be displayed on the contact page and used for receiving messages</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="store_phone">Store Phone</label>
                            <input type="text" name="store_phone" id="store_phone" class="form-control" value="<?php echo htmlspecialchars($store_phone); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="store_address">Store Address</label>
                            <textarea name="store_address" id="store_address" class="form-control"><?php echo htmlspecialchars($store_address); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Email Settings Tab -->
                    <div class="tab-content" id="email">
                        <div class="form-group">
                            <label for="smtp_host">SMTP Server</label>
                            <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="<?php echo htmlspecialchars($smtp_host); ?>">
                            <small>e.g., smtp.gmail.com</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="smtp_username">SMTP Username</label>
                                <input type="text" name="smtp_username" id="smtp_username" class="form-control" value="<?php echo htmlspecialchars($smtp_username); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_password">SMTP Password</label>
                                <input type="password" name="smtp_password" id="smtp_password" class="form-control" value="<?php echo htmlspecialchars($smtp_password); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_port">SMTP Port</label>
                            <input type="text" name="smtp_port" id="smtp_port" class="form-control" value="<?php echo htmlspecialchars($smtp_port); ?>">
                            <small>Common ports: 587 (TLS), 465 (SSL)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Tabs functionality
        const tabButtons = document.querySelectorAll(".tab-btn");
        const tabContents = document.querySelectorAll(".tab-content");
        
        tabButtons.forEach(button => {
            button.addEventListener("click", function() {
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove("active"));
                tabContents.forEach(content => content.classList.remove("active"));
                
                // Add active class to current button
                this.classList.add("active");
                
                // Show current tab content
                const tabId = this.getAttribute("data-tab");
                document.getElementById(tabId).classList.add("active");
            });
        });
        
        // Color preview
        const primaryColorInput = document.getElementById("primary_color");
        const primaryColorPreview = document.getElementById("primary_color_preview");
        
        primaryColorInput.addEventListener("input", function() {
            primaryColorPreview.style.backgroundColor = this.value;
        });
        
        const secondaryColorInput = document.getElementById("secondary_color");
        const secondaryColorPreview = document.getElementById("secondary_color_preview");
        
        secondaryColorInput.addEventListener("input", function() {
            secondaryColorPreview.style.backgroundColor = this.value;
        });
    </script>
</body>
</html>