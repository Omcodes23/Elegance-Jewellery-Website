<?php
// Prevent any automatic redirects
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegance Jewelry - Test Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #5a1a32;
        }
        .btn {
            display: inline-block;
            background-color: #5a1a32;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Page - No Refresh</h1>
        <p>This is a simple test page to check if PHP is causing the refresh issue.</p>
        
        <div id="counter">Page has been loaded <span id="count">0</span> times.</div>
        
        <a href="index.html" class="btn">Go to Homepage</a>
    </div>

    <script>
        // Simple counter to track if page refreshes
        document.addEventListener('DOMContentLoaded', function() {
            let count = parseInt(sessionStorage.getItem('testPageCount') || '0');
            count++;
            sessionStorage.setItem('testPageCount', count);
            document.getElementById('count').textContent = count;
            
            console.log('Test page loaded. Count:', count);
            
            // Log if this was a refresh
            if (performance && performance.navigation) {
                const navType = performance.navigation.type;
                if (navType === 1) {
                    console.log('This page was reloaded by the user');
                } else if (navType === 0) {
                    console.log('This page was accessed via link/direct entry');
                }
            }
        });
    </script>
</body>
</html>