<?php
// Simple connection test file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    // Try to include the config file
    if (file_exists('includes/config.php')) {
        echo "<p>✓ Config file found</p>";
        
        // Include the config file
        require_once 'includes/config.php';
        
        // Check if $conn variable exists
        if (isset($conn) && $conn instanceof PDO) {
            echo "<p>✓ Database connection established</p>";
            
            // Test connection with a simple query
            $stmt = $conn->query("SELECT 1");
            if ($stmt) {
                echo "<p>✓ Database query successful</p>";
                
                // Try to query the categories table
                try {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM categories");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p>✓ Categories table accessible, found {$result['count']} categories</p>";
                    
                    // Show the first few categories
                    $stmt = $conn->query("SELECT id, name, description, image FROM categories LIMIT 5");
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<h2>Sample Categories:</h2>";
                    echo "<pre>";
                    print_r($categories);
                    echo "</pre>";
                    
                } catch (PDOException $e) {
                    echo "<p>✗ Error accessing categories table: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p>✗ Could not execute database query</p>";
            }
        } else {
            echo "<p>✗ Database connection variable not found or not a PDO instance</p>";
            
            // Try to recreate the connection
            echo "<h2>Attempting to create a new connection:</h2>";
            
            // Define connection variables (these would normally be in config.php)
            $host = 'localhost';
            $dbname = 'elegance_jewelry'; // From your SQL file
            $username = 'root'; // Default XAMPP username
            $password = ''; // Default XAMPP password (empty)
            
            try {
                $testConn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<p>✓ Test connection successful with default credentials</p>";
            } catch (PDOException $e) {
                echo "<p>✗ Test connection failed: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p>✗ Config file not found at 'includes/config.php'</p>";
        
        // Check if other common paths exist
        $paths = [
            'config/database.php',
            'config/db.php',
            'db_config.php',
            'includes/db.php'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                echo "<p>! Found potential config file at: $path</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}
?>