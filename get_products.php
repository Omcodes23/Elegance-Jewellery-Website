<?php
// Include database connection
require_once "includes/config.php";

header('Content-Type: application/json');

// Get category parameter
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Validate category
$valid_categories = ['rings', 'necklaces', 'earrings', 'bracelets'];
if (!in_array($category, $valid_categories) && $category !== 'all' && !empty($category)) {
    echo json_encode([]);
    exit;
}

try {
    // Prepare SQL query based on category
    if (!empty($category) && $category !== 'all') {
        $sql = "SELECT id, name, description, price, old_price, image, category, featured, new FROM products WHERE category = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql = "SELECT id, name, description, price, old_price, image, category, featured, new FROM products";
        $result = $conn->query($sql);
    }

    // Fetch products
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'oldPrice' => (!empty($row['old_price'])) ? (float)$row['old_price'] : null,
            'image' => $row['image'],
            'category' => $row['category'],
            'description' => $row['description'] ?? '',
            'featured' => (bool)$row['featured'],
            'new' => (bool)$row['new']
        ];
    }

    // Free result and close statement if applicable
    if (!empty($category) && $category !== 'all') {
        $stmt->close();
    } else {
        $result->free();
    }

    // Return JSON response
    echo json_encode($products);
} catch (Exception $e) {
    // Return empty array on error
    echo json_encode([]);
}

// Close connection
$conn->close();
?>