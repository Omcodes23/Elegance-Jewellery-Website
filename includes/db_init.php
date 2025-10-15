<?php
// Database initialization script
// This script will create or update tables needed for the e-commerce application

// Include database configuration
require_once "config.php";

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return (mysqli_num_rows($result) > 0);
}

// Create Products Table
if (!tableExists($conn, 'products')) {
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
    
    if (mysqli_query($conn, $sql)) {
        echo "Products table created successfully<br>";
    } else {
        echo "Error creating products table: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Check if category_id column exists
    $result = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'category_id'");
    if (mysqli_num_rows($result) == 0) {
        // Add category_id column
        $sql = "ALTER TABLE products ADD COLUMN category_id INT";
        if (mysqli_query($conn, $sql)) {
            echo "Added category_id column to products table<br>";
            
            // Try to add the foreign key constraint
            $sql = "ALTER TABLE products ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
            if (mysqli_query($conn, $sql)) {
                echo "Added foreign key constraint to products table<br>";
            } else {
                echo "Error adding foreign key constraint: " . mysqli_error($conn) . "<br>";
            }
        } else {
            echo "Error adding category_id column: " . mysqli_error($conn) . "<br>";
        }
    }
}

// Create Categories Table if it doesn't exist
if (!tableExists($conn, 'categories')) {
    $sql = "CREATE TABLE categories (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Categories table created successfully<br>";
        
        // Insert some default categories
        $default_categories = ["Rings", "Necklaces", "Earrings", "Bracelets", "Watches"];
        foreach ($default_categories as $category) {
            $sql = "INSERT INTO categories (name) VALUES (?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $category);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        echo "Default categories added<br>";
    } else {
        echo "Error creating categories table: " . mysqli_error($conn) . "<br>";
    }
}

// Create Orders Table
if (!tableExists($conn, 'orders')) {
    $sql = "CREATE TABLE orders (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        customer_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100),
        postal_code VARCHAR(20) NOT NULL,
        country VARCHAR(100) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        notes TEXT,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Orders table created successfully<br>";
    } else {
        echo "Error creating orders table: " . mysqli_error($conn) . "<br>";
    }
}

// Create Order Items Table
if (!tableExists($conn, 'order_items')) {
    $sql = "CREATE TABLE order_items (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        product_id INT,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Order Items table created successfully<br>";
    } else {
        echo "Error creating order items table: " . mysqli_error($conn) . "<br>";
    }
}

// Create Users Table
if (!tableExists($conn, 'users')) {
    $sql = "CREATE TABLE users (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        full_name VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Users table created successfully<br>";
    } else {
        echo "Error creating users table: " . mysqli_error($conn) . "<br>";
    }
}

// Create Admins Table
if (!tableExists($conn, 'admins')) {
    $sql = "CREATE TABLE admins (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Admins table created successfully<br>";
        
        // Add a default admin account (username: admin, password: admin123)
        $username = "admin";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $email = "admin@example.com";
        
        $sql = "INSERT INTO admins (username, password, email) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $username, $password, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Default admin account created (username: admin, password: admin123)<br>";
        } else {
            echo "Error creating default admin account: " . mysqli_error($conn) . "<br>";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "Error creating admins table: " . mysqli_error($conn) . "<br>";
    }
}

echo "<br>Database initialization complete!";
?>