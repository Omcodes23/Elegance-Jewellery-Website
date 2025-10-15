<?php
// Admin panel product management functions

// Function to get all products
function getAllProducts() {
    global $conn;
    
    $sql = "SELECT * FROM products ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    
    $products = [];
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get a product by ID
function getProductById($id) {
    global $conn;
    
    $sql = "SELECT * FROM products WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
    }
    
    return false;
}

// Function to add a new product
function addProduct($data) {
    global $conn;
    
    $sql = "INSERT INTO products (name, description, price, old_price, category_id, image, stock, featured, sku, weight, dimensions, materials) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param(
            $stmt, 
            "ssddisisiiss", 
            $data['name'],
            $data['description'],
            $data['price'],
            $data['old_price'],
            $data['category_id'],
            $data['image'],
            $data['stock'],
            $data['featured'],
            $data['sku'],
            $data['weight'],
            $data['dimensions'],
            $data['materials']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($conn);
        }
    }
    
    return false;
}

// Function to update a product
function updateProduct($id, $data) {
    global $conn;
    
    $sql = "UPDATE products SET 
            name = ?,
            description = ?,
            price = ?,
            old_price = ?,
            category_id = ?,
            image = ?,
            stock = ?,
            featured = ?,
            sku = ?,
            weight = ?,
            dimensions = ?,
            materials = ?
            WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param(
            $stmt, 
            "ssddisisissi", 
            $data['name'],
            $data['description'],
            $data['price'],
            $data['old_price'],
            $data['category_id'],
            $data['image'],
            $data['stock'],
            $data['featured'],
            $data['sku'],
            $data['weight'],
            $data['dimensions'],
            $data['materials'],
            $id
        );
        
        return mysqli_stmt_execute($stmt);
    }
    
    return false;
}

// Function to delete a product
function deleteProduct($id) {
    global $conn;
    
    $sql = "DELETE FROM products WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    return false;
}

// Function to handle image upload
function uploadProductImage($file) {
    $target_dir = "../assets/img/products/";
    $file_name = basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }
    
    // Check file size
    if ($file["size"] > 5000000) { // 5MB max
        return ["success" => false, "message" => "File is too large."];
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" && $imageFileType != "webp") {
        return ["success" => false, "message" => "Only JPG, JPEG, PNG, WEBP & GIF files are allowed."];
    }
    
    // Create a unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "file_path" => "assets/img/products/" . $new_filename];
    } else {
        return ["success" => false, "message" => "There was an error uploading your file."];
    }
}

// Function to get all categories
function getAllCategories() {
    global $conn;
    
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    
    $categories = [];
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Category management functions
function addCategory($name, $description = '') {
    global $conn;
    
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($conn);
        }
    }
    
    return false;
}

function updateCategory($id, $name, $description = '') {
    global $conn;
    
    $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    return false;
}

function deleteCategory($id) {
    global $conn;
    
    // First check if category has products
    $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            return ["success" => false, "message" => "Category has products. Cannot delete."];
        }
    }
    
    // If no products, delete category
    $sql = "DELETE FROM categories WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            return ["success" => true];
        }
    }
    
    return ["success" => false, "message" => "Error deleting category."];
}

// Order management functions
function getAllOrders() {
    global $conn;
    
    $sql = "SELECT o.*, c.first_name, c.last_name, c.email 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            ORDER BY o.order_date DESC";
    
    $result = mysqli_query($conn, $sql);
    
    $orders = [];
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
    }
    
    return $orders;
}

function getOrderById($id) {
    global $conn;
    
    $sql = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address, c.city, c.state, c.zip, c.country 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE o.id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $order = mysqli_fetch_assoc($result);
            
            // Get order items
            $sql = "SELECT oi.*, p.name, p.sku, p.image 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $order['items'] = [];
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $order['items'][] = $row;
                    }
                }
            }
            
            return $order;
        }
    }
    
    return false;
}

function updateOrderStatus($id, $status) {
    global $conn;
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    return false;
}

// Customer management functions
function getAllCustomers() {
    global $conn;
    
    $sql = "SELECT * FROM customers ORDER BY first_name, last_name ASC";
    $result = mysqli_query($conn, $sql);
    
    $customers = [];
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
    }
    
    return $customers;
}

function getCustomerById($id) {
    global $conn;
    
    $sql = "SELECT * FROM customers WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
    }
    
    return false;
}

// Dashboard statistics
function getDashboardStats($conn) {
    $stats = [
        'products' => 0,
        'orders' => 0,
        'customers' => 0,
        'revenue' => 0,
        'recent_orders' => [],
        'low_stock' => [],
        'out_of_stock' => [],
        'best_sellers' => []
    ];
    
    // Get total products
    $sql = "SELECT COUNT(*) as count FROM products";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['products'] = $row['count'];
        mysqli_free_result($result);
    }
    
    // Get total orders
    $sql = "SELECT COUNT(*) as count FROM orders";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['orders'] = $row['count'];
        mysqli_free_result($result);
    }
    
    // Get total customers
    $sql = "SELECT COUNT(*) as count FROM customers";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['customers'] = $row['count'];
        mysqli_free_result($result);
    }
    
    // Get total revenue
    $sql = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'Completed'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats['revenue'] = $row['total'] ? $row['total'] : 0;
        mysqli_free_result($result);
    }
    
    // Get recent orders with customer names
    $sql = "SELECT o.*, c.name as customer_name 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            ORDER BY o.order_date DESC 
            LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['recent_orders'][] = $row;
        }
        mysqli_free_result($result);
    }
    
    // Get low stock products (stock < 10 but > 0)
    $sql = "SELECT * FROM products WHERE stock > 0 AND stock < 10 ORDER BY stock ASC LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['low_stock'][] = $row;
        }
        mysqli_free_result($result);
    }
    
    // Get out of stock products
    $sql = "SELECT * FROM products WHERE stock = 0 LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['out_of_stock'][] = $row;
        }
        mysqli_free_result($result);
    }
    
    // Get best selling products
    $sql = "SELECT p.*, SUM(oi.quantity) as total_sold 
            FROM products p 
            JOIN order_items oi ON p.id = oi.product_id 
            JOIN orders o ON oi.order_id = o.id 
            WHERE o.status = 'Completed' 
            GROUP BY p.id 
            ORDER BY total_sold DESC 
            LIMIT 5";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats['best_sellers'][] = $row;
        }
        mysqli_free_result($result);
    }
    
    return $stats;
}