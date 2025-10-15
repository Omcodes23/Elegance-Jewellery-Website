document.addEventListener('DOMContentLoaded', function() {
    // Fix Ruby Stud and Pearl Stud earrings image loading issue
    const productImages = document.querySelectorAll('.product-image img');
    
    productImages.forEach(img => {
        // Check if the image source is broken or not loaded properly
        img.onerror = function() {
            // Check product name in parent element
            const productTitle = this.closest('.product-card').querySelector('.product-title');
            
            if (productTitle) {
                const titleText = productTitle.textContent.toLowerCase();
                
                // Set fallback images for Ruby Stud and Pearl Stud earrings
                if (titleText.includes('ruby stud')) {
                    this.src = 'assets/img/products/earrings/ruby-stud-earrings.jpg';
                } else if (titleText.includes('pearl stud')) {
                    this.src = 'assets/img/products/earrings/pearl-stud-earrings.jpg';
                } else {
                    // Fallback for other products with missing images
                    this.src = 'assets/img/products/product-placeholder.jpg';
                }
            }
        };
        
        // Also specifically check for these products and force correct images
        const productTitle = img.closest('.product-card')?.querySelector('.product-title');
        if (productTitle) {
            const titleText = productTitle.textContent.toLowerCase();
            
            if (titleText.includes('ruby stud')) {
                img.src = 'assets/img/products/earrings/ruby-stud-earrings.jpg';
            } else if (titleText.includes('pearl stud')) {
                img.src = 'assets/img/products/earrings/pearl-stud-earrings.jpg';
            }
        }
    });
});