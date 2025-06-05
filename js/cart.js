// Cart functionality for GK Lab
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart if it doesn't exist
    if (!localStorage.getItem('gklab_cart')) {
        localStorage.setItem('gklab_cart', JSON.stringify([]));
    }
    
    // Update cart count in UI
    updateCartCount();
    
    // Add event listeners to all Add to Cart buttons in test/checkup cards
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get the parent card to extract item info
            const card = this.closest('.checkup-card');
            if (!card) return;
            
            // Extract item information
            const title = card.querySelector('.checkup-title').textContent;
            const priceElement = card.querySelector('.discounted-price');
            if (!priceElement) return;
            
            // Extract price (remove currency symbol and convert to number)
            const priceText = priceElement.textContent;
            const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
            
            // Get test/checkup ID if available
            const detailsBtn = card.querySelector('.btn-details');
            const id = detailsBtn ? detailsBtn.getAttribute('data-test-id') || detailsBtn.getAttribute('data-checkup-id') : Date.now();
            
            // Add item to cart
            addToCart({
                id: id,
                name: title,
                price: price,
                type: card.querySelector('.checkup-badge').textContent.trim()
            });
            
            // Show success message
            showNotification(`${title} added to cart!`);
            
            // Add animation to cart button
            animateCartButton();
        });
    });
    
    // Add event listeners to popup "Add to Cart" buttons
    const popupCartButtons = document.querySelectorAll('.add-to-cart-popup');
    
    popupCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get data from button attributes
            const id = this.getAttribute('data-item-id');
            const name = this.getAttribute('data-item-name');
            const price = parseFloat(this.getAttribute('data-item-price'));
            const type = this.getAttribute('data-item-type');
            
            // Add item to cart
            addToCart({
                id: id,
                name: name,
                price: price,
                type: type
            });
            
            // Show success message
            showNotification(`${name} added to cart!`);
            
            // Close the popup
            const popup = this.closest('.popup-overlay');
            if (popup) {
                popup.style.display = 'none';
            }
            
            // Add animation to cart button
            animateCartButton();
        });
    });
    
    // Add event listeners to popup close buttons
    const popupCloseButtons = document.querySelectorAll('.popup-close');
    
    popupCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const popup = this.closest('.popup-overlay');
            if (popup) {
                popup.style.display = 'none';
            }
        });
    });
    
    // Add event listeners to "View Details" buttons
    const viewDetailsButtons = document.querySelectorAll('.btn-details');
    
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const testId = this.getAttribute('data-test-id');
            const checkupId = this.getAttribute('data-checkup-id');
            
            if (testId) {
                const popupId = `test-popup-${testId}`;
                const popup = document.getElementById(popupId);
                if (popup) {
                    popup.style.display = 'flex';
                }
            } else if (checkupId) {
                const popupId = `checkup-popup-${checkupId}`;
                const popup = document.getElementById(popupId);
                if (popup) {
                    popup.style.display = 'flex';
                }
            }
        });
    });
});

// Function to add item to cart
function addToCart(item) {
    // Get current cart
    const cart = JSON.parse(localStorage.getItem('gklab_cart'));
    
    // Check if item already exists in cart
    const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id);
    
    if (existingItemIndex > -1) {
        // Item exists, increment quantity
        cart[existingItemIndex].quantity = (cart[existingItemIndex].quantity || 1) + 1;
    } else {
        // Add new item with quantity 1
        item.quantity = 1;
        cart.push(item);
    }
    
    // Save updated cart
    localStorage.setItem('gklab_cart', JSON.stringify(cart));
    
    // Update cart count in UI
    updateCartCount();
}

// Function to update cart count in UI
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('gklab_cart'));
    const count = cart.reduce((total, item) => total + (item.quantity || 1), 0);
    
    // Create or update cart count element
    const cartBtn = document.querySelector('.btn-cart');
    
    if (cartBtn) {
        let cartCountBadge = cartBtn.querySelector('.cart-count');
        
        if (!cartCountBadge) {
            cartCountBadge = document.createElement('span');
            cartCountBadge.classList.add('cart-count');
            cartBtn.appendChild(cartCountBadge);
        }
        
        cartCountBadge.textContent = count;
        cartCountBadge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Function to animate the cart button
function animateCartButton() {
    const cartBtn = document.querySelector('.btn-cart');
    
    if (cartBtn) {
        cartBtn.classList.add('cart-added');
        
        // Remove the class after animation completes
        setTimeout(() => {
            cartBtn.classList.remove('cart-added');
        }, 500);
    }
}

// Function to display notification when item is added to cart
function showNotification(message) {
    // Check if notification container exists, if not create it
    let notificationContainer = document.getElementById('notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.bottom = '20px';
        notificationContainer.style.right = '20px';
        notificationContainer.style.zIndex = '1000';
        document.body.appendChild(notificationContainer);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.classList.add('notification');
    notification.style.backgroundColor = '#16A085';
    notification.style.color = 'white';
    notification.style.padding = '10px 15px';
    notification.style.borderRadius = '4px';
    notification.style.marginTop = '10px';
    notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    notification.style.display = 'flex';
    notification.style.justifyContent = 'space-between';
    notification.style.alignItems = 'center';
    
    // Add message and close button
    notification.innerHTML = `
        <span>${message}</span>
        <button style="background: none; border: none; color: white; font-size: 16px; cursor: pointer; margin-left: 10px;">×</button>
    `;
    
    // Add notification to container
    notificationContainer.appendChild(notification);
    
    // Set timeout to remove notification
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s';
        setTimeout(() => {
            notification.remove();
            if (notificationContainer.children.length === 0) {
                notificationContainer.remove();
            }
        }, 500);
    }, 3000);
    
    // Add click event for close button
    const closeButton = notification.querySelector('button');
    closeButton.addEventListener('click', () => {
        notification.remove();
        if (notificationContainer.children.length === 0) {
            notificationContainer.remove();
        }
    });
} 