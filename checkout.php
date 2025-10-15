<?php
// Include configuration file
require_once 'includes/config.php';

// Start the session
session_start();

// Initialize variables
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form validation would go here
    // We'll redirect to Razorpay payment now instead of processing directly
    echo "<script>window.location.href='order-confirmation.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Elegance Jewelry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .checkout-items {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .checkout-form {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .checkout-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .checkout-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .checkout-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .checkout-item-details {
            flex-grow: 1;
        }
        
        .checkout-item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .checkout-item-price {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .checkout-item-quantity {
            color: #777;
            font-size: 0.9rem;
        }
        
        .checkout-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .checkout-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .checkout-total {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        
        .payment-methods {
            margin: 20px 0;
        }
        
        .payment-method {
            margin-bottom: 10px;
        }
        
        .checkout-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .checkout-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-cart i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-cart p {
            margin-bottom: 20px;
            color: #777;
        }
        
        /* Added styles for quantity controls */
        .item-quantity-controls {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .quantity-btn {
            background: var(--light-color);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .item-quantity {
            margin: 0 10px;
            width: 30px;
            text-align: center;
        }
        
        .remove-item {
            color: #f44336;
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 15px;
            font-size: 0.9rem;
        }
        
        .remove-item:hover {
            text-decoration: underline;
        }
        
        /* Razorpay button style */
        .razorpay-btn {
            background-color: #2d88ff;
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .razorpay-btn img {
            height: 20px;
            margin-right: 10px;
        }
        
        .razorpay-btn:hover {
            background-color: #1a73e8;
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

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="checkout-container">
            <h1 class="section-title">Checkout</h1>
            
            <div class="checkout-grid" id="checkout-grid">
                <!-- This area will be populated by JavaScript -->
                <div class="checkout-items">
                    <h2 class="checkout-title">Your Items</h2>
                    <div id="checkout-items-container">
                        <!-- Items will be loaded here -->
                    </div>
                    <div class="checkout-summary">
                        <div class="checkout-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">₹0.00</span>
                        </div>
                        <div class="checkout-row">
                            <span>Shipping:</span>
                            <span id="shipping">₹0.00</span>
                        </div>
                        <div class="checkout-row">
                            <span>Tax:</span>
                            <span id="tax">₹0.00</span>
                        </div>
                        <div class="checkout-row checkout-total">
                            <span>Total:</span>
                            <span id="total">₹0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-form">
                    <h2 class="checkout-title">Shipping Information</h2>
                    <form id="checkout-form" method="post">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="IN">India</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="UK">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="zip">Zip/Postal Code</label>
                            <input type="text" id="zip" name="zip" required>
                        </div>
                        
                        <!-- Razorpay payment button instead of payment options -->
                        <input type="hidden" name="order_id" id="order_id">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="total_amount" id="total_amount">
                        
                        <button type="submit" class="checkout-btn" id="proceed-to-pay">Proceed to Payment</button>
                    </form>
                </div>
            </div>
            
            <!-- Empty Cart Message (hidden by default) -->
            <div class="empty-cart" style="display: none;" id="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>You haven't added any items to your cart yet.</p>
                <a href="index.html" class="btn">Continue Shopping</a>
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

    <!-- Checkout JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load cart items from localStorage
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Update cart count
            updateCartCount();
            
            // Check if cart is empty
            if (cart.length === 0) {
                document.getElementById('checkout-grid').style.display = 'none';
                document.getElementById('empty-cart').style.display = 'block';
            } else {
                // Display cart items
                displayCheckoutItems(cart);
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
            
            // Form submission
            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    // Form validation can be added here
                    
                    // Submit form normally - PHP will handle the rest
                });
            }
        });
        
        // Display checkout items
        function displayCheckoutItems(cart) {
            const container = document.getElementById('checkout-items-container');
            let subtotal = 0;
            
            container.innerHTML = '';
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                const itemHtml = `
                    <div class="checkout-item" data-index="${index}">
                        <img src="${item.image}" alt="${item.name}" class="checkout-item-image">
                        <div class="checkout-item-details">
                            <h3 class="checkout-item-name">${item.name}</h3>
                            <p class="checkout-item-price">₹${item.price.toFixed(2)}</p>
                            <div class="item-quantity-controls">
                                <button class="quantity-btn minus-btn" onclick="updateQuantity(${index}, -1)">-</button>
                                <span class="item-quantity">${item.quantity}</span>
                                <button class="quantity-btn plus-btn" onclick="updateQuantity(${index}, 1)">+</button>
                                <button class="remove-item" onclick="removeItem(${index})">Remove</button>
                            </div>
                        </div>
                    </div>
                `;
                
                container.innerHTML += itemHtml;
            });
            
            calculateOrderSummary(subtotal);
        }
        
        // Calculate and update order summary
        function calculateOrderSummary(subtotal) {
            // Calculate order summary
            const shipping = subtotal > 100 ? 0 : 10; // Free shipping over ₹100
            const tax = subtotal * 0.07; // 7% tax
            const total = subtotal + shipping + tax;
            
            // Update summary values
            document.getElementById('subtotal').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('shipping').textContent = '₹' + shipping.toFixed(2);
            document.getElementById('tax').textContent = '₹' + tax.toFixed(2);
            document.getElementById('total').textContent = '₹' + total.toFixed(2);
            
            // Update hidden field for Razorpay
            document.getElementById('total_amount').value = total;
        }
        
        // Update quantity of an item
        function updateQuantity(index, change) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Update quantity with minimum of 1
            cart[index].quantity = Math.max(1, cart[index].quantity + change);
            
            // Save updated cart
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Refresh the display
            displayCheckoutItems(cart);
            updateCartCount();
        }
        
        // Remove an item from cart
        function removeItem(index) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Remove the item
            cart.splice(index, 1);
            
            // Save updated cart
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Check if cart is now empty
            if (cart.length === 0) {
                document.getElementById('checkout-grid').style.display = 'none';
                document.getElementById('empty-cart').style.display = 'block';
            } else {
                // Refresh the display
                displayCheckoutItems(cart);
            }
            
            updateCartCount();
        }
        
        // Update cart count
        function updateCartCount() {
            const cartCountElement = document.querySelector('.cart-count');
            if (!cartCountElement) return;
            
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
            
            cartCountElement.textContent = cartCount;
            cartCountElement.style.display = cartCount > 0 ? 'flex' : 'none';
        }
    </script>
</body>
</html>