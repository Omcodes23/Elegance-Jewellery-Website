<?php
// Include configuration file
require_once 'includes/config.php';

// Start the session
session_start();

// Check if we have an order ID
$orderID = isset($_GET['order']) ? $_GET['order'] : 'ORD-' . rand(100000, 999999);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Elegance Jewelry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .confirmation-box {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-icon {
            font-size: 5rem;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .order-details {
            margin: 30px 0;
            text-align: left;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .order-details h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .detail-row strong {
            font-weight: 600;
        }
        
        .buttons {
            margin-top: 30px;
        }
        
        .buttons .btn {
            margin: 0 10px;
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
                <a href="#" class="search-icon"><i class="fas fa-search"></i></a>
                <a href="checkout.php" class="cart-icon"><i class="fas fa-shopping-cart"></i><span class="cart-count">0</span></a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Confirmation Section -->
    <section class="confirmation-section">
        <div class="confirmation-container">
            <div class="confirmation-box">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="confirmation-title">Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order has been successfully placed.</p>
                
                <div class="order-details">
                    <h3>Order Details</h3>
                    <div class="detail-row">
                        <span>Order Number:</span>
                        <strong><?php echo $orderID; ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Date:</span>
                        <strong><?php echo date('F j, Y'); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Payment Method:</span>
                        <strong>Credit Card</strong>
                    </div>
                    <div class="detail-row">
                        <span>Shipping Method:</span>
                        <strong>Standard Shipping</strong>
                    </div>
                </div>
                
                <p>A confirmation email has been sent to your email address with all the details of your order.</p>
                
                <div class="buttons">
                    <a href="index.html" class="btn">Continue Shopping</a>
                    <a href="#" class="btn" onclick="window.print(); return false;">Print Receipt</a>
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

    <!-- Script to clear the cart -->
    <script>
        // Clear the cart on order confirmation page
        localStorage.removeItem('cart');
        
        // Update cart count
        document.addEventListener('DOMContentLoaded', function() {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = '0';
                cartCountElement.style.display = 'none';
            }
            
            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const navLinks = document.querySelector('.nav-links');
                    const navIcons = document.querySelector('.nav-icons');
                    navLinks.classList.toggle('show');
                    navIcons.classList.toggle('show');
                    mobileMenuBtn.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>