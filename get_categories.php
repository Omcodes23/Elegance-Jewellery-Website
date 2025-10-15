<?php
// Include database connection
require_once "includes/config.php";

// Get categories from database
$categories = [];
$sql = "SELECT id, name, description, image FROM categories ORDER BY id ASC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        // Create slug from name
        $slug = strtolower(str_replace(' ', '-', $row['name']));
        
        // Handle image path
        $image = $row['image'];
        if (!empty($image)) {
            // If image doesn't start with http or /, add the assets path
            if (strpos($image, 'http') !== 0 && strpos($image, '/') !== 0) {
                // Check if it already has the uploads path
                if (strpos($image, 'assets/') === false && strpos($image, 'uploads/') === false) {
                    $image = 'assets/images/' . $image;
                }
            }
            
            // Check if the file exists for local images
            $image_path = $_SERVER['DOCUMENT_ROOT'] . '/ecomm/' . $image;
            if (!preg_match('/^https?:\/\//', $image) && !file_exists($image_path)) {
                // If image doesn't exist, use default
                $image = 'assets/images/no.png';
            }
        } else {
            // Set default image if none provided
            $image = 'assets/images/no.png';
        }
        
        // Format category data
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'image' => $image,
            'slug' => $slug
        ];
    }
    $result->free();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($categories);
?>