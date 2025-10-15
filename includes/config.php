<?php
/* Database credentials for XAMPP */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'elegance_jewelry');

/* Attempt to connect to MySQL database */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

/* Check connection and display a user-friendly error message */
if(!$conn) {
    // Log the actual error for debugging
    error_log("Database Connection Error: " . mysqli_connect_error());
    
    // Check if it's an admin page or a user-facing page
    $is_admin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
    
    // Show appropriate error message
    if($is_admin) {
        echo "<div style='text-align:center; margin-top:100px;'>";
        echo "<h2>Database Connection Error</h2>";
        echo "<p>Could not connect to the database. Please check that:</p>";
        echo "<ol style='display:inline-block; text-align:left;'>";
        echo "<li>XAMPP/MySQL service is running</li>";
        echo "<li>Database credentials are correct</li>";
        echo "<li>Database 'ecomm' exists</li>";
        echo "</ol>";
        echo "<p><a href='javascript:location.reload()'>Try Again</a></p>";
        echo "</div>";
    } else {
        // For end users, show a less technical message
        echo "<div style='text-align:center; margin-top:100px;'>";
        echo "<h2>Site Temporarily Unavailable</h2>";
        echo "<p>We're experiencing technical difficulties. Please try again later.</p>";
        echo "</div>";
    }
    exit;
}

/* Set charset to ensure proper encoding */
mysqli_set_charset($conn, "utf8mb4");

/* Function to escape user inputs and prevent SQL injection */
function clean_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

/* Function to display error messages */
function display_error($message) {
    echo '<div class="alert alert-danger">' . $message . '</div>';
}

/* Function to display success messages */
function display_success($message) {
    echo '<div class="alert alert-success">' . $message . '</div>';
}

// Site Configuration Settings
$config = [
    // Basic Site Information
    'site_name' => 'Elegance Jewelry India',
    'site_tagline' => 'Exquisite Indian Craftsmanship',
    'site_description' => 'Premium jewelry handcrafted in India with the finest materials. Discover our collection of authentic Indian designs.',
    'site_logo' => 'assets/img/logo.png',
    'site_favicon' => 'assets/img/favicon.ico',
    
    // Contact Information
    'address' => '123 MG Road, Bangalore, Karnataka 560001, India',
    'phone' => '+91 98765 43210',
    'email' => 'contact@elegancejewelry.in',
    
    // Social Media Links
    'facebook' => 'https://facebook.com/elegancejewelryindia',
    'instagram' => 'https://instagram.com/elegancejewelry_india',
    'twitter' => 'https://twitter.com/elegancejewelry',
    'pinterest' => 'https://pinterest.com/elegancejewelryindia',
    
    // Currency and Region Settings
    'currency' => '₹', // Indian Rupee symbol
    'currency_code' => 'INR',
    'tax_rate' => 3, // GST percentage for jewelry
    'country' => 'India',
    'language' => 'en',
    'timezone' => 'Asia/Kolkata',
    
    // Shipping Settings
    'free_shipping_min' => 15000, // Free shipping on orders above ₹15,000
    'shipping_flat_rate' => 499, // Standard shipping cost
    'express_shipping_rate' => 999, // Express shipping cost
    
    // Payment Options
    'payment_methods' => [
        'upi' => true,
        'credit_card' => true,
        'debit_card' => true,
        'net_banking' => true,
        'cod' => true, // Cash on Delivery
        'emi' => true  // EMI options
    ],
    
    // Store Settings
    'business_hours' => 'Monday-Saturday: 10:00 AM - 8:00 PM, Sunday: 11:00 AM - 7:00 PM',
    'return_days' => 15, // Return policy in days
    'warranty_period' => '1 year', // Warranty period
    
    // SEO Settings
    'meta_keywords' => 'jewelry, diamonds, gold, silver, rings, necklaces, earrings, bracelets, India, Indian jewelry',
    'meta_author' => 'Elegance Jewelry India',
    'google_analytics' => 'UA-XXXXXXXX-X',
    
    // Database Settings
    'db_host' => 'localhost',
    'db_name' => 'jewelry_store',
    'db_user' => 'root',
    'db_pass' => '',
    
    // Admin Settings
    'admin_email' => 'admin@elegancejewelry.in',
    'items_per_page' => 10, // Number of items to show per page in admin panel
    'security_key' => 'EJ_INDIA_2023', // Used for encryption purposes
    
    // Feature Toggles
    'enable_wishlist' => true,
    'enable_reviews' => true,
    'enable_compare' => true,
    'enable_blog' => true,
    'maintenance_mode' => false
];

// Localization options for different regions of India
$regions = [
    'north' => [
        'name' => 'North India',
        'popular_styles' => ['Kundan', 'Polki', 'Meenakari'],
        'languages' => ['Hindi', 'Punjabi', 'Urdu']
    ],
    'south' => [
        'name' => 'South India',
        'popular_styles' => ['Temple Jewelry', 'Kasuti', 'Nettur Peti'],
        'languages' => ['Tamil', 'Telugu', 'Kannada', 'Malayalam']
    ],
    'east' => [
        'name' => 'East India',
        'popular_styles' => ['Filigree', 'Dokra', 'Tribal'],
        'languages' => ['Bengali', 'Odia', 'Assamese']
    ],
    'west' => [
        'name' => 'West India',
        'popular_styles' => ['Pachchikam', 'Thewa', 'Gajra'],
        'languages' => ['Marathi', 'Gujarati', 'Konkani']
    ]
];

// Sample customer reviews with Indian names and contexts
$sample_reviews = [
    [
        'name' => 'Priya Sharma',
        'location' => 'Delhi',
        'rating' => 5,
        'date' => '2023-08-15',
        'title' => 'Stunning Diamond Earrings',
        'review' => 'I purchased these earrings for my daughter\'s wedding and they were absolutely stunning. The craftsmanship is excellent and they sparkle beautifully. Worth every rupee!'
    ],
    [
        'name' => 'Rajesh Patel',
        'location' => 'Mumbai',
        'rating' => 4,
        'date' => '2023-07-22',
        'title' => 'Beautiful Anniversary Gift',
        'review' => 'Bought the gold necklace for my wife on our 10th anniversary. She loved it! The design is intricate and very Indian. Delivery was prompt and packaging was elegant.'
    ],
    [
        'name' => 'Aisha Khan',
        'location' => 'Hyderabad',
        'rating' => 5,
        'date' => '2023-06-10',
        'title' => 'Excellent Quality',
        'review' => 'The pearl bracelet I ordered exceeded my expectations. The pearls are lustrous and the gold clasp is beautifully designed. Will definitely shop here again!'
    ],
    [
        'name' => 'Vijay Reddy',
        'location' => 'Bangalore',
        'rating' => 4,
        'date' => '2023-05-18',
        'title' => 'Great Value',
        'review' => 'The diamond ring is excellent quality for the price. The setting is secure and the design is contemporary yet timeless. Very happy with my purchase.'
    ],
    [
        'name' => 'Sunita Gupta',
        'location' => 'Jaipur',
        'rating' => 5,
        'date' => '2023-04-05',
        'title' => 'Perfect Wedding Jewelry',
        'review' => 'Ordered the complete bridal set and it was absolutely magnificent. The kundan work is exquisite and matched perfectly with my wedding attire. Highly recommend!'
    ]
];

// Helper function to format Indian prices
function format_indian_price($price) {
    return '₹' . number_format((float)$price, 2, '.', ',');
}

// Helper function to get product rating from reviews
function get_product_rating($product_id) {
    global $conn;
    
    // Query to get average rating
    $sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return round($row['avg_rating'], 1);
    }
    
    return 0; // Default rating if query fails
}

// Common database connection (include in all pages)
function get_db_connection() {
    global $config;
    
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Define clean_input function only if it doesn't already exist
if (!function_exists('clean_input')) {
    function clean_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}
?>