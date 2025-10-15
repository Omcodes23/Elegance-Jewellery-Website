// Debug version to prevent refreshing issues
document.addEventListener('DOMContentLoaded', function() {
    console.log("Script loaded successfully");
    
    // Minimal product data for testing
    const dummyProducts = [
        {
            id: 1,
            name: 'Diamond Eternity Ring',
            price: 1999.99,
            image: 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
            featured: true,
            new: false
        },
        {
            id: 2,
            name: 'Sapphire Pendant Necklace',
            price: 1299.99,
            image: 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600',
            featured: true,
            new: true
        },
        {
            id: 3,
            name: 'Pearl Drop Earrings',
            price: 899.99,
            image: 'https://images.unsplash.com/photo-1635767798638-3665a167adc2?w=600',
            featured: false,
            new: true
        }
    ];
    
    // Simple display function without event handlers for now
    function displayProducts() {
        console.log("Displaying products");
        const featuredContainer = document.getElementById('featured-products');
        const newContainer = document.getElementById('new-products');
        
        if (featuredContainer) {
            featuredContainer.innerHTML = '';
            
            dummyProducts.filter(p => p.featured).forEach(product => {
                const html = `
                    <div class="product-card" data-product-id="${product.id}">
                        <div class="product-image">
                            <img src="${product.image}" alt="${product.name}">
                        </div>
                        <div class="product-details">
                            <h3 class="product-title">${product.name}</h3>
                            <div class="product-price">
                                <div>
                                    <span class="price">$${product.price.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                featuredContainer.innerHTML += html;
            });
        }
        
        if (newContainer) {
            newContainer.innerHTML = '';
            
            dummyProducts.filter(p => p.new).forEach(product => {
                const html = `
                    <div class="product-card" data-product-id="${product.id}">
                        <div class="product-image">
                            <img src="${product.image}" alt="${product.name}">
                        </div>
                        <div class="product-details">
                            <h3 class="product-title">${product.name}</h3>
                            <div class="product-price">
                                <div>
                                    <span class="price">$${product.price.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                newContainer.innerHTML += html;
            });
        }
    }
    
    // Basic mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default behavior
            console.log("Mobile menu clicked");
            const navLinks = document.querySelector('.nav-links');
            const navIcons = document.querySelector('.nav-icons');
            navLinks.classList.toggle('show');
            navIcons.classList.toggle('show');
        });
    }
    
    // Newsletter form handling
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent form submission
            console.log("Newsletter form submitted");
            // Show success message
            alert('Thank you for subscribing!');
            this.reset();
        });
    }
    
    // Call display function
    displayProducts();
    
    // Global error handler
    window.addEventListener('error', function(e) {
        console.error('Global error caught:', e.message);
    });
    
    // Debug memory usage issues
    console.log("Script initialization complete");
});