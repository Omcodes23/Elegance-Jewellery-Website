<?php
// Initialize the session
session_start();

// Check if order ID is provided
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : 'ORD-' . rand(100000, 999999);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Elegance Jewelry</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 50px 20px;
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 40px;
        }
        
        .success-title {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .order-info {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: left;
        }
        
        .order-details {
            margin-bottom: 30px;
        }
        
        .order-details h3 {
            margin-bottom: 15px;
            color: var(--secondary-color);
            border-bottom: 1px solid #e1e1e1;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .detail-row span:first-child {
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .action-button {
            padding: 12px 24px;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .primary-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .primary-btn:hover {
            background-color: var(--dark-color);
        }
        
        .secondary-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .secondary-btn:hover {
            background-color: var(--dark-color);
        }
        
        @media screen and (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="logo">
                <h1>Elegance Jewelry</h1>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="index.html#categories">Categories</a></li>
                    <li><a href="index.html#featured">Featured</a></li>
                    <li><a href="index.html#new-arrivals">New Arrivals</a></li>
                </ul>
            </nav>
            <div class="nav-icons">
                <a href="#" class="cart-icon"><i class="fas fa-shopping-cart"></i><span class="cart-count">0</span></a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Order Success Section -->
    <section class="order-success">
        <div class="container">
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="success-title">Thank You for Your Order!</h2>
                <p>Your order has been received and is now being processed. You will receive a confirmation email shortly.</p>
                
                <div class="order-info">
                    <div class="order-details">
                        <h3>Order Details</h3>
                        <div class="detail-row">
                            <span>Order Number:</span>
                            <span><?php echo $order_id; ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Order Date:</span>
                            <span><?php echo date('F j, Y'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Payment Method:</span>
                            <span>Credit Card</span>
                        </div>
                        <div class="detail-row">
                            <span>Shipping Method:</span>
                            <span>Standard Shipping (3-5 business days)</span>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <h3>Shipping Address</h3>
                        <p>
                            John Doe<br>
                            123 Main Street<br>
                            Apt 4B<br>
                            New York, NY 10001<br>
                            United States
                        </p>
                    </div>
                    
                    <div class="order-details">
                        <h3>Estimated Delivery</h3>
                        <p><?php echo date('F j, Y', strtotime('+5 days')); ?></p>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="index.html" class="action-button primary-btn">Continue Shopping</a>
                    <a href="#" class="action-button secondary-btn">Track Order</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>Elegance Jewelry</h3>
                    <p>Discover the finest jewelry pieces crafted for elegance and sophistication.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="index.html#featured">Shop</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Jewelry Lane, Diamond City</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@elegancejewelry.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Elegance Jewelry. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script>
        // Clear cart after successful order
        localStorage.removeItem('cart');
        
        // Update cart count
        document.addEventListener('DOMContentLoaded', function() {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = '0';
            }
        });
    </script>
</body>
</html>