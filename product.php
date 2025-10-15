<?php
// Initialize the session
session_start();

// Include database connection file
require_once "includes/config.php";

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.html");
    exit;
}

$product_id = $_GET['id'];

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
            'old_price' => 2499.99,
            'description' => 'This stunning Diamond Eternity Ring features brilliant-cut diamonds set in 18K white gold. The diamonds are expertly set to maximize brilliance and fire, creating a ring that truly sparkles from every angle.',
            'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
            'category' => 'rings',
            'stock' => 10,
            'sku' => 'DER-' . rand(1000, 9999),
            'featured' => 1,
            'new' => 0
        ];
    }
    
    $stmt->close();
} else {
    // Fallback to dummy data if database error
    $product = [
        'id' => $product_id,
        'name' => 'Diamond Eternity Ring',
        'price' => 1999.99,
        'old_price' => 2499.99,
        'description' => 'This stunning Diamond Eternity Ring features brilliant-cut diamonds set in 18K white gold. The diamonds are expertly set to maximize brilliance and fire, creating a ring that truly sparkles from every angle.',
        'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
        'category' => 'rings',
        'stock' => 10,
        'sku' => 'DER-' . rand(1000, 9999),
        'featured' => 1,
        'new' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - Elegance Jewelry</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <a href="#" class="cart-icon"><i class="fas fa-shopping-cart"></i><span class="cart-count">0</span></a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Product Detail Section -->
    <section class="product-detail">
        <div class="container">
            <div class="product-detail-container">
                <div class="product-detail-image">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                </div>
                <div class="product-detail-info">
                    <h2><?php echo $product['name']; ?></h2>
                    <div class="price-container">
                        <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php if(isset($product['oldPrice']) && $product['oldPrice'] > $product['price']): ?>
                            <span class="old-price">₹<?php echo number_format($product['oldPrice'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-detail-desc">
                        <p><?php echo $product['description']; ?></p>
                    </div>
                    <div class="product-detail-meta">
                        <span><strong>SKU:</strong> <?php echo $product['sku']; ?></span>
                        <span><strong>Category:</strong> <?php echo ucfirst($product['category']); ?></span>
                        <span><strong>Availability:</strong> <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?></span>
                    </div>
                    <div class="product-quantity">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <div class="product-actions">
                        <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <button class="buy-now-btn" data-id="<?php echo $product['id']; ?>">
                            Buy Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products Section -->
    <section class="related-products">
        <div class="container">
            <h2 class="section-title">You May Also Like</h2>
            <div class="product-grid" id="related-products">
                <!-- Related products will be loaded here via JavaScript -->
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
            // Product quantity buttons
            const minusBtn = document.querySelector('.minus');
            const plusBtn = document.querySelector('.plus');
            const quantityInput = document.querySelector('.quantity-input');
            
            if (minusBtn && plusBtn && quantityInput) {
                minusBtn.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value);
                    if (value > 1) {
                        value--;
                        quantityInput.value = value;
                    }
                });
                
                plusBtn.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value);
                    let max = parseInt(quantityInput.getAttribute('max'));
                    if (value < max) {
                        value++;
                        quantityInput.value = value;
                    }
                });
            }
            
            // Buy Now button
            const buyNowBtn = document.querySelector('.buy-now-btn');
            if (buyNowBtn) {
                buyNowBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const quantity = quantityInput ? quantityInput.value : 1;
                    window.location.href = `payment.php?product_id=${productId}&quantity=${quantity}`;
                });
            }
            
            // Add to Cart button
            const addToCartBtn = document.querySelector('.add-to-cart-btn');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                    
                    // Get product data
                    const product = {
                        id: <?php echo $product['id']; ?>,
                        name: '<?php echo addslashes($product['name']); ?>',
                        price: <?php echo $product['price']; ?>,
                        image: '<?php echo $product['image']; ?>',
                        quantity: quantity
                    };
                    
                    addToCart(product);
                });
            }
        });
        
        // Load related products
        async function loadRelatedProducts() {
            try {
                const response = await fetch(`api/related_products.php?category=<?php echo $product['category']; ?>&id=<?php echo $product['id']; ?>`);
                const products = await response.json();
                
                const relatedProductsContainer = document.getElementById('related-products');
                if (relatedProductsContainer) {
                    displayProducts(products, relatedProductsContainer);
                }
            } catch (error) {
                console.error('Error loading related products:', error);
                
                // Fallback data
                const relatedProducts = [
                    {
                        id: 9,
                        name: 'Vintage Diamond Ring',
                        price: 2199.99,
                        oldPrice: 2499.99,
                        image: 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
                        category: 'rings'
                    },
                    {
                        id: 10,
                        name: 'Silver Band Ring',
                        price: 799.99,
                        oldPrice: 999.99,
                        image: 'https://images.unsplash.com/photo-1606293459339-ac2b41c98d5c?w=600',
                        category: 'rings'
                    },
                    {
                        id: 11,
                        name: 'Gold Engagement Ring',
                        price: 2899.99,
                        oldPrice: 3299.99,
                        image: 'https://images.unsplash.com/photo-1586878341523-7c1ef1a0e254?w=600',
                        category: 'rings'
                    },
                    {
                        id: 12,
                        name: 'Platinum Wedding Band',
                        price: 1499.99,
                        oldPrice: 1699.99,
                        image: 'https://images.unsplash.com/photo-1607191502475-943eb63be467?w=600',
                        category: 'rings'
                    }
                ];
                
                const relatedProductsContainer = document.getElementById('related-products');
                if (relatedProductsContainer) {
                    displayProducts(relatedProducts, relatedProductsContainer);
                }
            }
        }
        
        // Call function to load related products
        loadRelatedProducts();
    </script>
</body>
</html>