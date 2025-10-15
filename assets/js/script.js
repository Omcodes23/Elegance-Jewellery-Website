// Global Variables
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let isPageRefreshing = false; // Flag to prevent multiple refreshes

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded");
    
    // Load categories from database
    fetchCategoriesFromDB();
    
    // Initialize components
    loadProducts();
    updateCartCount();
    setupEventListeners();
});

// Set up all event listeners in one place
function setupEventListeners() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default action
            toggleMobileMenu();
        });
    }
    
    // Newsletter form submission
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Prevent form submission
            const email = newsletterForm.querySelector('input[type="email"]').value;
            
            if (email) {
                showMessage('Thank you for subscribing!');
                newsletterForm.reset();
            }
        });
    }
    
    // Navigation links - prevent defaults where needed
    const navLinks = document.querySelectorAll('a');
    navLinks.forEach(link => {
        // Only prevent default for hash links that might cause page jumps
        if (link.getAttribute('href') && link.getAttribute('href').startsWith('#')) {
            link.addEventListener('click', (e) => {
                // Allow smooth scrolling to sections without page reload
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
    });
    
    // Modify cart icon to go directly to checkout
    const cartIcon = document.querySelector('.cart-icon');
    if (cartIcon) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'checkout.php';
        });
    }
}

// Toggle Mobile Menu
function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const navIcons = document.querySelector('.nav-icons');
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    
    navLinks.classList.toggle('show');
    navIcons.classList.toggle('show');
    mobileMenuBtn.classList.toggle('active');
}

// Load Products from API or use database data
async function loadProducts() {
    try {
        // First attempt to load from the product API endpoint
        const response = await fetch('api/products.php');
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const products = await response.json();
        displayProducts(products);
    } catch (error) {
        console.error('Error loading products:', error);
        // If API fails, fetch from database directly
        fetchProductsFromDatabase();
    }
}

// Fallback function to get products from database
function fetchProductsFromDatabase() {
    // Create a form to post to a PHP endpoint
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'get_products.php';
    
    // Add a hidden field to identify this is a fallback request
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'fallback';
    input.value = 'true';
    form.appendChild(input);
    
    // Submit the form and handle the response
    form.style.display = 'none';
    document.body.appendChild(form);
    
    // Use fetch to post the form data instead of submitting the form
    fetch('get_products.php', {
        method: 'POST',
        body: new FormData(form),
    })
    .then(response => response.json())
    .then(products => {
        if (products && products.length > 0) {
            displayProducts(products);
        } else {
            console.error('No products returned from database');
        }
    })
    .catch(error => {
        console.error('Error fetching products from database:', error);
    })
    .finally(() => {
        // Clean up the form
        document.body.removeChild(form);
    });
}

// Display fallback products if API fails
function displayFallbackProducts() {
    const dummyProducts = [
        {
            id: 1,
            name: 'Diamond Eternity Ring',
            price: 1999.99,
            oldPrice: 2499.99,
            image: 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
            featured: true,
            new: false
        },
        {
            id: 2,
            name: 'Sapphire Pendant Necklace',
            price: 1299.99,
            oldPrice: 1599.99,
            image: 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600',
            featured: true,
            new: true
        },
        {
            id: 3,
            name: 'Pearl Drop Earrings',
            price: 899.99,
            oldPrice: 1199.99,
            image: 'https://images.unsplash.com/photo-1635767798638-3665a167adc2?w=600',
            featured: true,
            new: false
        },
        {
            id: 4,
            name: 'Gold Tennis Bracelet',
            price: 2499.99,
            oldPrice: 2999.99,
            image: 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600',
            featured: true,
            new: false
        },
        {
            id: 5,
            name: 'Emerald Halo Ring',
            price: 1799.99,
            oldPrice: 2199.99,
            image: 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
            featured: false,
            new: true
        },
        {
            id: 6,
            name: 'Ruby Stud Earrings',
            price: 1299.99,
            oldPrice: 1499.99,
            image: 'https://images.unsplash.com/photo-1635767798638-3665a167adc2?w=600',
            featured: false,
            new: true
        },
        {
            id: 7,
            name: 'Platinum Chain Necklace',
            price: 1599.99,
            oldPrice: 1899.99,
            image: 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600',
            featured: false,
            new: true
        },
        {
            id: 8,
            name: 'Diamond Charm Bracelet',
            price: 1899.99,
            oldPrice: 2299.99,
            image: 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600',
            featured: false,
            new: true
        }
    ];
    
    displayProducts(dummyProducts);
}

// Display Products
function displayProducts(products) {
    const featuredProductsContainer = document.getElementById('featured-products');
    const newProductsContainer = document.getElementById('new-products');
    
    if (featuredProductsContainer) {
        displayProductsInContainer(
            products.filter(product => product.featured), 
            featuredProductsContainer
        );
    }
    
    if (newProductsContainer) {
        displayProductsInContainer(
            products.filter(product => product.new), 
            newProductsContainer
        );
    }
}

// Helper function to display products in a specific container
function displayProductsInContainer(products, container) {
    container.innerHTML = '';
    
    products.forEach(product => {
        const productCard = document.createElement('div');
        productCard.classList.add('product-card');
        productCard.setAttribute('data-product-id', product.id);
        
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
                <div class="product-icons">
                    <a href="product.php?id=${product.id}" class="quick-view"><i class="far fa-eye"></i></a>
                    
                </div>
            </div>
            <div class="product-details">
                <h3 class="product-title">${product.name}</h3>
                <div class="product-price">
                    <div>
                        <span class="price">₹${product.price.toFixed(2)}</span>
                        ${product.oldPrice ? `<span class="old-price">₹${product.oldPrice.toFixed(2)}</span>` : ''}
                    </div>
                    <button class="buy-now-btn">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(productCard);
        
        // Add event listeners after appending to DOM
        attachProductEventListeners(productCard, product);
    });
}

// Attach event listeners to product card elements
function attachProductEventListeners(productCard, product) {
    // Change add-to-cart to buy-now
    const buyNowBtn = productCard.querySelector('.buy-now-btn');
    if (buyNowBtn) {
        buyNowBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            addToCart(product);
            // Redirect directly to checkout page
            window.location.href = 'checkout.php';
        });
    }
    
    // Quick view link
    const quickViewBtn = productCard.querySelector('.quick-view');
    if (quickViewBtn) {
        quickViewBtn.addEventListener('click', function(e) {
            // Let this event use default behavior - it's an actual link
        });
    }
    
    // Wishlist button
    const wishlistBtn = productCard.querySelector('.add-to-wishlist');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showMessage(`${product.name} added to wishlist!`);
        });
    }
}

// Add to Cart
function addToCart(product, quantity = 1) {
    // Get existing cart or initialize new one
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    // Check if product already exists in cart
    const existingItemIndex = cart.findIndex(item => item.id === product.id);
    
    if (existingItemIndex >= 0) {
        // Update quantity if product already in cart
        cart[existingItemIndex].quantity += quantity;
    } else {
        // Add new item to cart
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: quantity
        });
    }
    
    // Save cart back to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update cart count
    updateCartCount();
    
    // Show notification
    showMessage(`${product.name} added to cart!`);
}

// Update Cart Count
function updateCartCount() {
    const cartCountElement = document.querySelector('.cart-count');
    if (!cartCountElement) return;
    
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    
    cartCountElement.textContent = cartCount;
    cartCountElement.style.display = cartCount > 0 ? 'flex' : 'none';
}

// Show Message
function showMessage(message) {
    // Remove any existing message
    const existingMessage = document.querySelector('.message-popup');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create new message element
    const messageElement = document.createElement('div');
    messageElement.className = 'message-popup success';
    messageElement.innerHTML = `
        <p>${message}</p>
        <button class="close-message"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(messageElement);
    
    // Add event listener to close button
    const closeBtn = messageElement.querySelector('.close-message');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            messageElement.remove();
        });
    }
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (messageElement.parentNode) {
            messageElement.remove();
        }
    }, 3000);
}

// Function to fetch categories from database
function fetchCategoriesFromDB() {
    // Show loading indicator
    const loadingIndicator = document.getElementById('categories-loading');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'flex';
    }
    
    // Get category container for later use
    const categoryContainer = document.getElementById('category-container');
    
    console.log('Fetching categories from database...');
    
    fetch('get_categories.php')
        .then(response => {
            console.log('Category API response status:', response.status);
            return response.json();
        })
        .then(data => {
            // Hide loading indicator
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            console.log('Categories data received:', data);
            
            if (data && Array.isArray(data)) {
                displayCategories(data);
            } else if (data && data.error) {
                console.error('Error from server:', data.message);
                showCategoryError(categoryContainer);
            } else {
                console.error('Invalid data format received for categories:', data);
                showCategoryError(categoryContainer);
            }
        })
        .catch(error => {
            console.error('Error fetching categories:', error);
            // Hide loading indicator
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            showCategoryError(categoryContainer);
        });
}

// Helper function to show category error
function showCategoryError(container) {
    if (container) {
        container.innerHTML = `
            <div class="error-message">
                <p>Sorry, we couldn't load the collections. Please try again later.</p>
            </div>
        `;
    }
}

// Function to display categories
function displayCategories(categories) {
    const categoryContainer = document.getElementById('category-container');
    if (!categoryContainer) return;
    
    // Clear any existing content
    categoryContainer.innerHTML = '';

    if (!categories || categories.length === 0) {
        console.error('No categories returned from database');
        showCategoryError(categoryContainer);
        return;
    }

    // Display categories from database
    categories.forEach(category => {
        // Create the category card
        const categoryCard = document.createElement('div');
        categoryCard.className = 'category-card';
        categoryCard.setAttribute('data-category-id', category.id);
        
        console.log('Category image path:', category.image);
        
        // Build the category card HTML
        categoryCard.innerHTML = `
            <div class="img-container">
                <img src="${category.image}" alt="${category.name}" onerror="this.src='assets/images/no.png'">
            </div>
            <h3>${category.name}</h3>
            <a href="category.php?id=${category.id}" class="btn-small view-category">View All</a>
        `;
        
        categoryContainer.appendChild(categoryCard);
        
        // Add event listeners after appending to DOM
        attachCategoryEventListeners(categoryCard, category);
    });
}

// Attach event listeners to category card elements
function attachCategoryEventListeners(categoryCard, category) {
    const viewCategoryBtn = categoryCard.querySelector('.view-category');
    if (viewCategoryBtn) {
        viewCategoryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Redirect to category page with proper type parameter for category.php
            const categoryType = category.type || category.name.toLowerCase().replace(/\s+/g, '-');
            window.location.href = `category.php?type=${encodeURIComponent(categoryType)}`;
        });
    }
}