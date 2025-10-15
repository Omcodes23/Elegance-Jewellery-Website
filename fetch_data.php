<?php
// Database connection
require_once 'includes/config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get the action from POST or GET
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Default to categories if no action specified
if (empty($action)) {
    $action = 'get_categories';
}

try {
    // Handle different data requests
    if ($action == 'get_categories') {
        // Query categories based on jewelry_db.sql structure
        $sql = "SELECT id, name, description, image FROM categories ORDER BY id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process categories
        foreach ($categories as &$category) {
            // Generate slug from name
            $category['slug'] = strtolower(str_replace(' ', '-', $category['name']));
        }
        
        // Return success response
        echo json_encode($categories);
    }
    elseif ($action == 'get_products') {
        // Get featured/new parameters if they exist
        $featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;
        $new = isset($_GET['new']) ? (int)$_GET['new'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        
        // Build query based on parameters
        $sql = "SELECT id, name, description, price, old_price, category, image, featured, new FROM products WHERE 1=1";
        $params = [];
        
        if ($featured !== null) {
            $sql .= " AND featured = ?";
            $params[] = $featured;
        }
        
        if ($new !== null) {
            $sql .= " AND new = ?";
            $params[] = $new;
        }
        
        if ($category !== null) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY id ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return success response
        echo json_encode($products);
    }
    else {
        // Invalid action
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action specified']);
    }
}
catch (PDOException $e) {
    // Log the error
    error_log("Database Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
catch (Exception $e) {
    // Log the error
    error_log("General Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>