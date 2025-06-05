// PHP-based Cart Functionality for GK Lab
document.addEventListener('DOMContentLoaded', function() {
    // Initialize by fetching current cart count
    fetchCartCount();
    
    // We don't need to add event listeners here since each page handles its own buttons
    // This file provides the shared functions for cart operations
});

// Function to add item to PHP cart
function addToCartPHP(id, name, price, type) {
    // Determine if we're on the main page or in a subdirectory
    const prefix = window.location.pathname.includes('/pages/') ? '' : 'pages/';
    
    // Navigate to cart.php with item parameters
    const url = `${prefix}cart.php?action=add&id=${encodeURIComponent(id)}&name=${encodeURIComponent(name)}&price=${encodeURIComponent(price)}&type=${encodeURIComponent(type)}`;
    
    // Create an invisible iframe to prevent page navigation
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = url;
    document.body.appendChild(iframe);
    
    // Remove the iframe after a short delay
    setTimeout(() => {
        document.body.removeChild(iframe);
        // Show notification
        showCartNotification(`${name} added to cart!`);
        // Update cart count (if visible on page)
        fetchCartCount();
    }, 700); // Increased timeout for more reliable updates
}

// Function to update cart count via AJAX
function fetchCartCount() {
    // Determine if we're on the main page or in a subdirectory
    const prefix = window.location.pathname.includes('/pages/') ? '' : 'pages/';
    
    fetch(`${prefix}cart-count.php`)
        .then(response => response.json())
        .then(data => {
            updateCartCountUI(data.count);
        })
        .catch(error => {
            console.error('Error fetching cart count:', error);
            // Try alternative path if the first fails
            if (prefix === '') {
                fetch(`../cart-count.php`)
                    .then(response => response.json())
                    .then(data => {
                        updateCartCountUI(data.count);
                    })
                    .catch(err => console.error('Alternative path also failed:', err));
            }
        });
}

// Function to update cart count in UI
function updateCartCountUI(count) {
    const cartCountBadges = document.querySelectorAll('.cart-count');
    
    cartCountBadges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    });
    
    // Animate cart button
    const cartBtn = document.querySelector('.btn-cart');
    if (cartBtn) {
        cartBtn.classList.add('cart-added');
        setTimeout(() => {
            cartBtn.classList.remove('cart-added');
        }, 500);
    }
}

// Function to display notification when item is added to cart
function showCartNotification(message) {
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
    notification.style.animation = 'slideIn 0.3s forwards';
    
    // Add message and close button
    notification.innerHTML = `
        <span>${message}</span>
        <a href="pages/cart.php" style="color: white; margin-left: 15px; padding: 2px 10px; border: 1px solid white; border-radius: 4px; font-size: 12px; text-decoration: none;">View Cart</a>
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

// Add CSS for notification animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes cartPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .cart-added {
        animation: cartPulse 0.5s ease;
    }
`;
document.head.appendChild(style); 