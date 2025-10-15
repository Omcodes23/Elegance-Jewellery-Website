<?php
// Check if XAMPP is working properly
$server_status = "Working";
echo "<h1>XAMPP Status Check</h1>";
echo "<p>Server Status: $server_status</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current File Path: " . __FILE__ . "</p>";

// List all CSS and JS files to verify they exist
$css_dir = __DIR__ . '/assets/css/';
$js_dir = __DIR__ . '/assets/js/';

echo "<h2>CSS Files:</h2>";
if (is_dir($css_dir)) {
    $css_files = scandir($css_dir);
    echo "<ul>";
    foreach ($css_files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>CSS directory not found at: $css_dir</p>";
}

echo "<h2>JavaScript Files:</h2>";
if (is_dir($js_dir)) {
    $js_files = scandir($js_dir);
    echo "<ul>";
    foreach ($js_files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>JavaScript directory not found at: $js_dir</p>";
}
?>