<?php
// Initialize the session
session_start();

// Include database connection file
require_once "includes/config.php";

// Check if product ID and quantity are provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    header("Location: index.html");
    exit;
}

$product_id = $_GET['product_id'];
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

// Fetch product details from database
$sql = "SELECT * FROM products WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    } else {
        // Fallback to dummy data if product not found
        $product = [
            'id' => $product_id,
            'name' => 'Diamond Eternity Ring',
            'price' => 1999.99,
            'description' => 'This stunning Diamond Eternity Ring features brilliant-cut diamonds set in 18K white gold.',
            'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
            'category' => 'rings',
            'sku' => 'DER-' . rand(1000, 9999)
        ];
    }
    
    $stmt->close();
} else {
    // Fallback to dummy data if database error
    $product = [
        'id' => $product_id,
        'name' => 'Diamond Eternity Ring',
        'price' => 1999.99,
        'description' => 'This stunning Diamond Eternity Ring features brilliant-cut diamonds set in 18K white gold.',
        'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
        'category' => 'rings',
        'sku' => 'DER-' . rand(1000, 9999)
    ];
}

// Calculate totals
$subtotal = $product['price'] * $quantity;
$tax = $subtotal * 0.07; // 7% tax
$shipping = 15.00; // Flat shipping rate
$total = $subtotal + $tax + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Elegance Jewelry</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .checkout-form {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .checkout-summary {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            align-self: start;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .checkout-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .checkout-item-image {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .checkout-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .checkout-item-details h3 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .checkout-item-price {
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .checkout-item-quantity {
            color: var(--grey-color);
            font-size: 0.9rem;
        }
        
        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .checkout-total {
            font-weight: 600;
            font-size: 1.2rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e1e1e1;
        }
        
        .payment-methods {
            margin-top: 20px;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .payment-method input {
            margin-right: 10px;
        }
        
        .card-icons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .card-icon {
            width: 40px;
            height: 25px;
            background-color: #f5f5f5;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .submit-order-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 4px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .submit-order-btn:hover {
            background-color: var(--dark-color);
        }
        
        @media screen and (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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

    <!-- Checkout Section -->
    <section class="checkout">
        <div class="container">
            <h2 class="section-title">Checkout</h2>
            <div class="checkout-container">
                <div class="checkout-form">
                    <h3>Billing Details</h3>
                    <form id="checkout-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first-name">First Name</label>
                                <input type="text" id="first-name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last-name">Last Name</label>
                                <input type="text" id="last-name" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Street Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="state">State / Province</label>
                                <input type="text" id="state" name="state" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zip">ZIP / Postal Code</label>
                                <input type="text" id="zip" name="zip" required>
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <select id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                    <option value="IN">India</option>
                                </select>
                            </div>
                        </div>
                        
                        <h3>Payment Information</h3>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="credit-card" name="payment_method" value="credit_card" checked>
                                <label for="credit-card">Credit Card</label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="paypal" name="payment_method" value="paypal">
                                <label for="paypal">PayPal</label>
                            </div>
                        </div>
                        
                        <div class="credit-card-fields">
                            <div class="form-group">
                                <label for="card-name">Name on Card</label>
                                <input type="text" id="card-name" name="card_name" required>
                            </div>
                            <div class="form-group">
                                <label for="card-number">Card Number</label>
                                <input type="text" id="card-number" name="card_number" required>
                                <div class="card-icons">
                                    <div class="card-icon"><i class="fab fa-cc-visa"></i></div>
                                    <div class="card-icon"><i class="fab fa-cc-mastercard"></i></div>
                                    <div class="card-icon"><i class="fab fa-cc-amex"></i></div>
                                    <div class="card-icon"><i class="fab fa-cc-discover"></i></div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="card-expiry">Expiration Date (MM/YY)</label>
                                    <input type="text" id="card-expiry" name="card_expiry" placeholder="MM/YY" required>
                                </div>
                                <div class="form-group">
                                    <label for="card-cvv">CVV</label>
                                    <input type="text" id="card-cvv" name="card_cvv" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-order-btn">Place Order</button>
                    </form>
                </div>
                
                <div class="checkout-summary">
                    <h3>Order Summary</h3>
                    <div class="checkout-item">
                        <div class="checkout-item-image">
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                        </div>
                        <div class="checkout-item-details">
                            <h3><?php echo $product['name']; ?></h3>
                            <div class="checkout-item-price">₹<?php echo number_format($product['price'], 2); ?></div>
                            <div class="checkout-item-quantity">Quantity: <?php echo $quantity; ?></div>
                        </div>
                    </div>
                    
                    <div class="checkout-summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="checkout-summary-row">
                        <span>Tax (7%)</span>
                        <span>₹<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="checkout-summary-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="checkout-summary-row checkout-total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle payment fields based on selected payment method
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const creditCardFields = document.querySelector('.credit-card-fields');
            
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (this.value === 'credit_card') {
                        creditCardFields.style.display = 'block';
                    } else {
                        creditCardFields.style.display = 'none';
                    }
                });
            });
            
            // Form submission
            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Simulate form processing
                    const submitBtn = document.querySelector('.submit-order-btn');
                    submitBtn.textContent = 'Processing...';
                    submitBtn.disabled = true;
                    
                    // Simulate API call
                    setTimeout(() => {
                        // Redirect to success page
                        window.location.href = 'order-success.php?order_id=' + generateOrderId();
                    }, 2000);
                });
            }
            
            // Generate random order ID
            function generateOrderId() {
                return 'ORD-' + Math.floor(100000 + Math.random() * 900000);
            }
        });
    </script>
</body>
</html>