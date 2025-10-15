<?php
// Initialize the session
session_start();

// Include database connection file
require_once "includes/config.php";

// Check if category type is provided
if (!isset($_GET['type']) || empty($_GET['type'])) {
    header("Location: index.html");     
    exit;
}

$category = $_GET['type'];
$category_name = ucfirst($category);

// Fetch products from get_products.php using cURL
function getProducts($category) {
    $curl = curl_init();
    $url = "http://localhost/ecomm/get_products.php?category=" . urlencode($category);
    
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        return [];
    }
    
    $products = json_decode($response, true);
    return is_array($products) ? $products : [];
}

// Get products for this category
$products = getProducts($category);

// Get product count for display
$product_count = count($products);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_name; ?> - Elegance Jewelry</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .category-header {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?w=1200') center/cover no-repeat;
            height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        .category-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .category-description {
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.1rem;
        }
        
        .filter-bar {
            background-color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .filter-label {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        
        .products-count {
            color: var(--grey-color);
            font-size: 0.9rem;
        }
        
        @media screen and (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
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
                <a href="#" class="search-icon"><i class="fas fa-search"></i></a>
                <a href="#" class="cart-icon"><i class="fas fa-shopping-cart"></i><span class="cart-count">0</span></a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Category Header -->
    <section class="category-header">
        <div class="container">
            <h1 class="category-title"><?php echo $category_name; ?></h1>
            <p class="category-description">
                <?php
                switch ($category) {
                    case 'rings':
                        echo 'Explore our exquisite collection of rings featuring diamonds, sapphires, emeralds, and other precious gemstones set in gold, platinum, and silver.';
                        break;
                    case 'necklaces':
                        echo 'Discover elegant necklaces and pendants crafted with the finest materials to add a touch of sophistication to any outfit.';
                        break;
                    case 'earrings':
                        echo 'Browse our stunning earrings collection featuring studs, hoops, drops, and chandeliers designed to enhance your natural beauty.';
                        break;
                    case 'bracelets':
                        echo 'Find the perfect bracelet from our selection of tennis bracelets, bangles, cuffs, and charms made with premium materials.';
                        break;
                    default:
                        echo 'Explore our collection of fine jewelry pieces crafted with precision and elegance.';
                }
                ?>
            </p>
        </div>
    </section>

    <!-- Products Section -->
    <section class="category-products">
        <div class="container">
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <span class="filter-label">Sort By:</span>
                    <select class="filter-select" id="sort-products">
                        <option value="default">Featured</option>
                        <option value="price-low-high">Price: Low to High</option>
                        <option value="price-high-low">Price: High to Low</option>
                        <option value="name-a-z">Name: A to Z</option>
                        <option value="name-z-a">Name: Z to A</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Price Range:</span>
                    <select class="filter-select" id="price-range">
                        <option value="all">All Prices</option>
                        <option value="0-1000">$0 - $1,000</option>
                        <option value="1000-2000">$1,000 - $2,000</option>
                        <option value="2000-3000">$2,000 - $3,000</option>
                        <option value="3000+">$3,000+</option>
                    </select>
                </div>
                <span class="products-count"><?php echo $product_count; ?> Products</span>
            </div>
            
            <!-- Products Grid -->
            <div class="product-grid" id="category-products"  >
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-price="<?php echo $product['price']; ?>" data-name="<?php echo $product['name']; ?>">
                            <div class="product-image">
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                <div class="product-icons">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="quick-view" data-id="<?php echo $product['id']; ?>"><i class="far fa-eye"></i></a>
                                </div>
                            </div>
                            <div class="product-details">
                                <h3><?php echo $product['name']; ?></h3>
                                <div class="product-price">
                                    <div>
                                        <span class="price">₹<?php echo number_format($product['price'], 2); ?></span>
                                        <?php if (isset($product['oldPrice']) && $product['oldPrice'] > 0): ?>
                                            <span class="old-price">$<?php echo number_format($product['oldPrice'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="buy-now-btn">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>No products found in this category. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <br>
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
            updateCartCount();
            
            // Sort products functionality
            const sortSelect = document.getElementById('sort-products');
            const priceRangeSelect = document.getElementById('price-range');
            const productsGrid = document.getElementById('category-products');
            
            if (sortSelect && productsGrid) {
                sortSelect.addEventListener('change', function() {
                    sortProducts(this.value);
                });
            }
            
            if (priceRangeSelect && productsGrid) {
                priceRangeSelect.addEventListener('change', function() {
                    filterByPrice(this.value);
                });
            }
            
            // Add event listeners to Add to Cart buttons
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productCard = this.closest('.product-card');
                    const productName = productCard.querySelector('h3').textContent;
                    const productPrice = parseFloat(productCard.getAttribute('data-price'));
                    const productImage = productCard.querySelector('.product-image img').getAttribute('src');
                    
                    const product = {
                        id: parseInt(productId),
                        name: productName,
                        price: productPrice,
                        image: productImage,
                        category: '<?php echo $category; ?>'
                    };
                    
                    addToCart(product);
                });
            });
            
            // Functions for sorting and filtering
            function sortProducts(sortType) {
                const products = Array.from(productsGrid.querySelectorAll('.product-card'));
                
                products.sort((a, b) => {
                    if (sortType === 'price-low-high') {
                        return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                    } else if (sortType === 'price-high-low') {
                        return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                    } else if (sortType === 'name-a-z') {
                        return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                    } else if (sortType === 'name-z-a') {
                        return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                    }
                    return 0;
                });
                
                // Clear the grid and re-append sorted products
                while (productsGrid.firstChild) {
                    productsGrid.removeChild(productsGrid.firstChild);
                }
                
                products.forEach(product => {
                    productsGrid.appendChild(product);
                });
            }
            
            function filterByPrice(range) {
                const products = Array.from(productsGrid.querySelectorAll('.product-card'));
                
                products.forEach(product => {
                    const price = parseFloat(product.getAttribute('data-price'));
                    let shouldShow = true;
                    
                    if (range === '0-1000') {
                        shouldShow = price >= 0 && price <= 1000;
                    } else if (range === '1000-2000') {
                        shouldShow = price > 1000 && price <= 2000;
                    } else if (range === '2000-3000') {
                        shouldShow = price > 2000 && price <= 3000;
                    } else if (range === '3000+') {
                        shouldShow = price > 3000;
                    }
                    
                    product.style.display = shouldShow ? 'block' : 'none';
                });
                
                // Update product count
                const visibleProducts = Array.from(productsGrid.querySelectorAll('.product-card')).filter(p => p.style.display !== 'none');
                document.querySelector('.products-count').textContent = visibleProducts.length + ' Products';
            }
        });
    </script>
</body>
</html>