<?php
// Debug page to show raw category data
require_once 'includes/config.php';

// Set headers for better viewing but not as JSON to make it readable in browser
header('Content-Type: text/html');
echo '<h1>Category Data Debug</h1>';

try {
    // Query to fetch all categories regardless of status
    $sql = "SELECT * FROM categories ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Show table structure
    echo '<h2>Database Table Structure</h2>';
    echo '<pre>';
    $tableInfoSql = "DESCRIBE categories";
    $tableStmt = $conn->prepare($tableInfoSql);
    $tableStmt->execute();
    $tableInfo = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($tableInfo);
    echo '</pre>';
    
    // Output raw category data
    echo '<h2>Raw Category Data</h2>';
    echo '<pre>';
    print_r($categories);
    echo '</pre>';
    
    // Output JSON for JavaScript testing
    echo '<h2>JSON Format (for copy/paste testing)</h2>';
    echo '<textarea style="width:100%; height:200px;">';
    echo json_encode($categories, JSON_PRETTY_PRINT);
    echo '</textarea>';
    
} catch (PDOException $e) {
    echo '<div style="color:red">';
    echo 'Database error: ' . $e->getMessage();
    echo '</div>';
}
?>