// Convert prices to Indian format (₹) and localize formatting
function formatIndianPrice(price) {
    // Format number to Indian style with commas (e.g., 1,00,000)
    const formattedValue = price.toLocaleString('en-IN', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    });
    
    return '₹' + formattedValue;
}

// Update all price displays on the page to Indian currency format
function updateCurrencyDisplay() {
    // Update product prices on cards
    const priceElements = document.querySelectorAll('.price');
    priceElements.forEach(element => {
        const originalPrice = parseFloat(element.textContent.replace(/[^0-9.-]+/g, ""));
        element.textContent = formatIndianPrice(originalPrice);
    });
    
    // Update old prices if they exist
    const oldPriceElements = document.querySelectorAll('.old-price');
    oldPriceElements.forEach(element => {
        const originalPrice = parseFloat(element.textContent.replace(/[^0-9.-]+/g, ""));
        element.textContent = formatIndianPrice(originalPrice);
    });
    
    // Update any other price displays (cart, totals, etc.)
    const productDetailPrice = document.querySelector('.product-detail-price');
    if (productDetailPrice) {
        const originalPrice = parseFloat(productDetailPrice.textContent.replace(/[^0-9.-]+/g, ""));
        productDetailPrice.textContent = formatIndianPrice(originalPrice);
    }
}

// Call this function when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateCurrencyDisplay();
});