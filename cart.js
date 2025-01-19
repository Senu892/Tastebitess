// Cart functionality
let cart = [];

// Initialize cart from localStorage
function initializeCart() {
    const savedCart = localStorage.getItem('cart');
    cart = savedCart ? JSON.parse(savedCart) : [];
    updateCartCount();
    renderCartItems();
}

// Save cart to localStorage
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    renderCartItems();
}

// Update cart count badge
function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    cartCount.classList.toggle('hidden', totalItems === 0);
}

// Toggle cart modal
function toggleCart(event) {
    if (event) {
        event.stopPropagation();
    }
    const modal = document.getElementById('cartModal');
    modal.classList.toggle('hidden');
    if (!modal.classList.contains('hidden')) {
        renderCartItems();
    }
}

// Add item to cart
function addToCart(item) {
    const existingItem = cart.find(cartItem => 
        cartItem.id === item.id && 
        cartItem.type === item.type && 
        cartItem.orderType === item.orderType
    );

    if (existingItem) {
        existingItem.quantity += item.quantity;
    } else {
        cart.push(item);
    }

    saveCart();
    showCartNotification();
}

// Show notification when item is added
function showCartNotification() {
    alert('Item added to cart!');
}

// Remove item from cart
function removeFromCart(index) {
    cart.splice(index, 1);
    saveCart();
}

// Update item quantity
function updateQuantity(index, newQuantity) {
    if (newQuantity > 0) {
        cart[index].quantity = newQuantity;
    } else {
        removeFromCart(index);
    }
    saveCart();
}

// Calculate total price
function calculateTotal() {
    return cart.reduce((total, item) => {
        let price = item.price;
        if (item.orderType === 'subscription') {
            price *= 0.9; // 10% discount for subscription
        }
        return total + (price * item.quantity);
    }, 0);
}

// Render cart items
function renderCartItems() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    if (!cartItems) return;

    cartItems.innerHTML = '';
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-center text-gray-500 py-4">Your cart is empty</p>';
        cartTotal.textContent = '$0.00';
        return;
    }

    cart.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.className = 'flex justify-between items-center p-4 border-b';
        itemElement.innerHTML = `
            <div class="flex-grow">
                <h3 class="font-medium">${item.name}</h3>
                <p class="text-sm text-gray-500">
                    ${item.type === 'custom' ? 'Custom Box' : 'Pre-made Box'} - 
                    ${item.orderType === 'subscription' ? 'Subscription' : 'One-time'}
                </p>
                <div class="flex items-center mt-2">
                    <button onclick="updateQuantity(${index}, ${item.quantity - 1})" class="text-gray-500 px-2">-</button>
                    <span class="mx-2">${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, ${item.quantity + 1})" class="text-gray-500 px-2">+</button>
                </div>
            </div>
            <div class="text-right">
                <p class="font-medium">$${(item.price * item.quantity).toFixed(2)}</p>
                <button onclick="removeFromCart(${index})" class="text-red-500 text-sm">Remove</button>
            </div>
        `;
        cartItems.appendChild(itemElement);
    });

    cartTotal.textContent = `$${calculateTotal().toFixed(2)}`;
}

// Proceed to checkout
function proceedToCheckout(event) {
    if (event) {
        event.preventDefault();
    }
    
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    // Create the form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'checkoutpage.php';

    // Format the products array in the required structure
    const formattedProducts = cart.map(item => {
        // Ensure we always have an array of product IDs
        let productIds;
        if (Array.isArray(item.productIds)) {
            productIds = item.productIds;
        } else if (item.productIds) {
            productIds = [item.productIds];
        } else if (item.id) {
            productIds = [item.id];
        } else {
            productIds = [];
        }

        // Filter out any null or undefined values
        productIds = productIds.filter(id => id != null);

        return {
            ids: productIds,
            type: item.type === 'custom' ? 'Customized' : 'Predefined',
            name: item.name || '',
            price: item.price || 0,
            quantity: item.quantity || 1
        };
    });

    // Add the cart data with complete information
    const cartDataInput = document.createElement('input');
    cartDataInput.type = 'hidden';
    cartDataInput.name = 'cart_data';
    cartDataInput.value = JSON.stringify(cart.map(item => ({
        ...item,
        productIds: Array.isArray(item.productIds) ? item.productIds : 
                   item.productIds ? [item.productIds] : 
                   item.id ? [item.id] : []
    })));
    form.appendChild(cartDataInput);

    // Add formatted products
    const formattedProductsInput = document.createElement('input');
    formattedProductsInput.type = 'hidden';
    formattedProductsInput.name = 'formatted_products';
    formattedProductsInput.value = JSON.stringify(formattedProducts);
    form.appendChild(formattedProductsInput);

    // Create a flattened array of all product IDs
    const allProductIds = formattedProducts
        .flatMap(item => item.ids)
        .filter(id => id != null);

    // Add other required fields
    const formFields = {
        'selected_products': JSON.stringify(allProductIds),
        'order_type': cart[0]?.orderType || 'one-time',
        'total_price': calculateTotal(),
        'box_size': cart[0]?.boxSize || 'regular',
        'quantity': cart.reduce((sum, item) => sum + (item.quantity || 1), 0),
    };

    // Add each field to the form
    Object.entries(formFields).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });

    // Debug logging
    console.log('Cart Data:', cart);
    console.log('Formatted Products:', formattedProducts);
    console.log('All Product IDs:', allProductIds);

    // Append form to body and submit
    document.body.appendChild(form);
    form.submit();
}

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', initializeCart);