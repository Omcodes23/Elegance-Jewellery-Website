// Script to handle Buy Now button functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get all Buy Now buttons on the page
    const buyNowButtons = document.querySelectorAll('.buy-now-btn');
    
    buyNowButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default action
            
            // Get product information from the closest product card
            const productCard = this.closest('.product-card');
            if (!productCard) return;
            
            const productId = productCard.dataset.productId || productCard.getAttribute('data-product-id');
            const productTitle = productCard.querySelector('.product-title')?.textContent;
            
            if (productId) {
                // Add the product to cart
                addToCart(productId, 1);
                
                // Redirect to checkout page
                window.location.href = 'checkout.php';
            } else {
                // If product ID is not available, try to use the product page URL
                const productLinkElement = productCard.querySelector('a.product-link');
                if (productLinkElement) {
                    window.location.href = productLinkElement.href;
                } else {
                    console.error('Could not find product ID or link for Buy Now action');
                    
                    // Fallback - redirect to product page if we have the title
                    if (productTitle) {
                        // Create a search URL with the product title
                        window.location.href = `product.php?q=${encodeURIComponent(productTitle)}`;
                    }
                }
            }
        });
    });
    
    // Function to add product to cart
    function addToCart(productId, quantity = 1) {
        // Get existing cart or create new one
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        
        // Check if product already in cart
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            // Update quantity if already in cart
            existingItem.quantity += quantity;
        } else {
            // Find product info - in real app, you'd fetch this from an API
            // For now, just create basic object
            cart.push({
                id: productId,
                quantity: quantity,
                // More details would be added here or fetched separately
            });
        }
        
        // Save cart to localStorage
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Update cart count and show notification
        updateCartCount();
        showCartNotification("Product added to cart!");
    }
    
    // Function to update cart count in navbar
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
        
        // Update all cart count elements
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = cartCount;
            
            // Show or hide based on count
            if (cartCount > 0) {
                element.style.display = 'inline-block';
            } else {
                element.style.display = 'none';
            }
        });
    }
    
    // Function to show notification when product is added to cart
    function showCartNotification(message) {
        // Check if notification element exists
        let notification = document.querySelector('.cart-notification');
        
        // If not, create it
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'cart-notification';
            document.body.appendChild(notification);
            
            // Add styles for the notification
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.background = 'rgba(90, 26, 50, 0.9)';
            notification.style.color = 'white';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '5px';
            notification.style.zIndex = '1000';
            notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            notification.style.transition = 'opacity 0.3s ease-in-out';
            notification.style.opacity = '0';
        }
        
        // Set message and show notification
        notification.textContent = message;
        notification.style.opacity = '1';
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
        }, 3000);
    }
    
    // Initialize cart count on page load
    updateCartCount();
});