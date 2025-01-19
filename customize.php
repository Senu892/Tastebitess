<?php
session_start();

// Initialize session variables with default values
$username = '';
$isLoggedIn = false;

// Check if user is logged in and set variables
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $isLoggedIn = true;
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '';
}

// Include the database connection file
include 'dbconnection.php';
// Define box size limits
$boxSizeLimits = [
    'large' => 9,
    'medium' => 7,
    'small' => 5
];

// Fetch all products from database with prices
$sql = "SELECT id, product_name, product_image, product_price FROM products ORDER BY product_name";
$result = $conn->query($sql);
$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

if (!isset($_SESSION['selected_snacks'])) {
    $_SESSION['selected_snacks'] = [];
}

// Create a JSON object of product prices for JavaScript
$productPrices = [];
foreach ($products as $product) {
    $productPrices[$product['id']] = $product['product_price'];
}

$userId = $_SESSION['user_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TasteBites - Customize Your Box</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .snack-card {
            background: #F5F5F5;
            border-radius: 1rem;
            transition: transform 0.2s;
        }
        .snack-card:hover {
            transform: translateY(-5px);
        }
        .snack-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .selected-snack-item {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .remove-animation {
            animation: fadeOut 0.3s ease-out;
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gray-100">
<nav class="bg-white py-4 z-10">
        <div class="max-w-7lg mx-auto px-4 flex justify-between items-center">
            <img src="logo.png" alt="TasteBites" class="h-8">
            <div class="flex space-x-8 items-center">
                <a href="index.php" class="text-black">Home</a>
                <a href="customize.php" class="text-black">Customize</a>
                <a href="subscription.php" class="text-black">Subscription</a>
                <a href="aboutuspage.php" class="text-black">About Us</a>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <a href="userprofile.php" class="bg-[#FFDAC1] px-6 py-1 rounded-full">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <a href="logout.php" class="text-black">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="bg-[#FFDAC1] px-6 py-1 rounded-full">Login</a>
                <?php endif; ?>
                <button id="cartButton" class="text-gray-600 relative" onclick="toggleCart()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span id="cartCount" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center hidden">0</span>
                </button>
            </div>
        </div>
    </nav>

    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="fixed right-0 top-0 h-full w-96 bg-white shadow-lg">
            <div class="p-4 flex flex-col h-full">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Shopping Cart</h2>
                    <button onclick="toggleCart()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="cartItems" class="flex-grow overflow-y-auto">
                    <!-- Cart items will be inserted here -->
                </div>
                <div class="border-t pt-4">
                    <div class="flex justify-between mb-4">
                        <span class="font-bold">Total:</span>
                        <span id="cartTotal" class="font-bold">$0.00</span>
                    </div>
                    <button onclick="proceedToCheckout(event)" class="w-full bg-green-600 text-white py-2 rounded-md mb-2">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>    
     <!-- Main Content -->
     <main class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-[#DC143C] mb-8">Let's Customize</h1>
        
        <!-- Customization Box -->
        <form id="customization-form" action="checkoutpage.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
            <input type="hidden" name="selected_products" id="selected-products-input">
            <input type="hidden" name="total_price" id="total-price-input">
            
            <div class="bg-white rounded-lg border">
                <div class="grid grid-cols-12 gap-8 p-6">
                    <!-- Left Section: Box Image -->
                    <div class="col-span-5">
                        <div class="bg-[#FFF5EE] rounded-lg p-8">
                            <img src="snack.png" alt="Snack Box" class="w-full">
                        </div>
                    </div>

                    <!-- Order Details Section -->
                    <div class="col-span-4">
                        <div class="mb-6">
                            <span class="text-gray-600">Size :</span>
                            <select name="box_size" id="box-size" class="border rounded px-2 py-1 ml-2">
                                <option value="large">Large</option>
                                <option value="medium">Medium</option>
                                <option value="small">Small</option>
                            </select>
                        </div>

                        <!-- Snack limit indicator -->
                        <div class="mb-4">
                            <p class="text-gray-600">Selected: <span id="selected-count">0</span>/<span id="max-items">9</span> items</p>
                        </div>


                        <div class="bg-white">
                            <h2 class="text-[#DC143C] text-xl font-semibold mb-2">Total Amount</h2>
                            <p class="text-green-600 text-2xl font-bold mb-4">$<span id="total-price">0.00</span></p>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Quantity</label>
                                <input class="w-full border rounded px-3 py-2" type="number" name="quantity" min="1" value="1" id="quantity-input">
                            </div>

                            <div class="space-y-2 mb-6">
                                <label class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="radio" name="order_type" value="onetime" checked>
                                        <span class="ml-2">One-time order</span>
                                    </div>
                                    <span>$<span id="one-time-price">0.00</span></span>
                                </label>
                                <label class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="radio" name="order_type" value="subscription">
                                        <span class="ml-2">Subscribe & Save</span>
                                    </div>
                                    <span>(Monthly) $<span id="subscription-price">0.00</span></span>
                                </label>
                            </div>

                            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-md mb-3">Proceed to Checkout</button>
                            <button type="button" class="w-full border border-orange-400 text-orange-400 py-2 rounded-md">+ Add to Cart</button>
                        </div>
                    </div>

                   <!-- Right Section: Selected Snacks -->
                    <div class="col-span-3">
                        <h3 class="text-orange-500 font-semibold mb-4">Selected Snacks</h3>
                        <div id="selected-snacks" class="space-y-2">
                            <!-- Selected snacks will be added here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Browse Snacks Section -->
        <div class="mt-16 text-center">
            <h2 class="text-2xl font-bold text-[#DC143C] mb-8">Browse our Snacks</h2>
            <div class="grid grid-cols-5 gap-6">
                <?php foreach ($products as $product): ?>
                <div class="snack-card cursor-pointer"
                     data-id="<?= htmlspecialchars($product['id']) ?>"
                     data-name="<?= htmlspecialchars($product['product_name']) ?>"
                     data-price="<?= htmlspecialchars($product['product_price']) ?>"
                     data-image="<?= htmlspecialchars($product['product_image']) ?>">
                    <img src="<?= htmlspecialchars($product['product_image']) ?>" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                         class="w-full h-40 object-cover rounded-t-lg">
                    <p class="py-3 text-center text-gray-800">
                        <?= htmlspecialchars($product['product_name']) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <footer class="bg-[#FFDAC1] py-12 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <img src="logo.png" alt="TasteBites" class="h-11 mb-4">
                    <p class="text-gray-800">Sweet Every Bite</p>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <h3 class="font-bold text-lg mb-4">Navigate</h3>
                        <ul class="space-y-2">
                            <li><a href="./index.php" class="hover:text-gray-600">Home</a></li>
                            <li><a href="./customize.php" class="hover:text-gray-600">Customize</a></li>
                            <li><a href="./subscription.php" class="hover:text-gray-600">Subscription</a></li>
                            <li><a href="./aboutuspage.php" class="hover:text-gray-600">About Us</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-4">Contact US</h3>
                        <ul class="space-y-2 text-gray-800">
                            <li>Location: 123 Flavor Street,</li>
                            <li>Colombo, Sri Lanka</li>
                            <li>Call Us: +94777890</li>
                            <li>Email: hello@tastebites.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="text-center mt-12 pt-8 border-t border-orange-200 text-sm text-gray-800">
                © 2024 Taste Bites. All Rights Reserved.
            </div>
        </div>
    </footer>


    <script>
document.addEventListener('DOMContentLoaded', function() {
    const selectedSnacks = new Set();
    const boxSizeLimits = <?php echo json_encode($boxSizeLimits); ?>;
    let totalPrice = 0;

    const form = document.getElementById('customization-form');
    const boxSizeSelect = document.getElementById('box-size');
    const quantityInput = document.getElementById('quantity-input');
    const totalPriceElement = document.getElementById('total-price');
    const oneTimePriceElement = document.getElementById('one-time-price');
    const subscriptionPriceElement = document.getElementById('subscription-price');
    const selectedProductsInput = document.getElementById('selected-products-input');
    const totalPriceInput = document.getElementById('total-price-input');
    const orderTypeInputs = document.querySelectorAll('input[name="order_type"]');
    const selectedCountElement = document.getElementById('selected-count');
    const maxItemsElement = document.getElementById('max-items');
    const selectedSnacksContainer = document.getElementById('selected-snacks');

    const productPrices = <?php echo json_encode($productPrices); ?>;
    const subscriptionDiscount = 0.9; // 10% discount for subscription

    function updateMaxItems() {
        const boxSize = boxSizeSelect.value;
        const maxItems = boxSizeLimits[boxSize];
        maxItemsElement.textContent = maxItems;

        // Remove excess items if box size is decreased
        if (selectedSnacks.size > maxItems) {
            const snacksArray = Array.from(selectedSnacks);
            const toRemove = snacksArray.slice(maxItems);
            toRemove.forEach(id => removeSnack(id));
        }

        updateSnackCardsState();
    }

    function updateSnackCardsState() {
        const boxSize = boxSizeSelect.value;
        const maxItems = boxSizeLimits[boxSize];
        const snackCards = document.querySelectorAll('.snack-card');

        snackCards.forEach(card => {
            if (selectedSnacks.size >= maxItems && !selectedSnacks.has(card.dataset.id)) {
                card.classList.add('disabled');
            } else {
                card.classList.remove('disabled');
            }
        });
    }

    function calculateFinalPrice() {
        const quantity = parseInt(quantityInput.value) || 1;
        const basePrice = totalPrice;
        const selectedOrderType = document.querySelector('input[name="order_type"]:checked').value;
        
        const oneTimeTotal = (basePrice * quantity).toFixed(2);
        const subscriptionTotal = (basePrice * quantity * subscriptionDiscount).toFixed(2);
        
        oneTimePriceElement.textContent = oneTimeTotal;
        subscriptionPriceElement.textContent = subscriptionTotal;
        
        const finalTotal = selectedOrderType === 'subscription' ? subscriptionTotal : oneTimeTotal;
        totalPriceElement.textContent = finalTotal;
        totalPriceInput.value = finalTotal;
    }

    function updatePriceDisplay() {
        calculateFinalPrice();
        updateSelectedProducts();
        selectedCountElement.textContent = selectedSnacks.size;
        updateSnackCardsState();
    }

    function updateSelectedProducts() {
        selectedProductsInput.value = JSON.stringify(Array.from(selectedSnacks));
    }

    async function removeSnack(id) {
        const element = document.querySelector(`.selected-snack-item[data-id="${id}"]`);
        if (element) {
            // Add fade out animation
            element.classList.add('remove-animation');
            
            // Wait for animation to complete
            await new Promise(resolve => setTimeout(resolve, 300));
            
            selectedSnacks.delete(id);
            totalPrice -= parseFloat(productPrices[id]) || 0;
            
            // Remove the element after animation
            element.remove();
            
            updatePriceDisplay();
        }
    }

    function addSnackToSelection(snackData) {
        if (selectedSnacks.has(snackData.id)) return;
        
        const boxSize = boxSizeSelect.value;
        const maxItems = boxSizeLimits[boxSize];
        
        if (selectedSnacks.size >= maxItems) {
            alert(`You can only select up to ${maxItems} snacks for a ${boxSize} box!`);
            return;
        }
        
        selectedSnacks.add(snackData.id);
        totalPrice += parseFloat(productPrices[snackData.id]) || 0;

        const snackElement = document.createElement('div');
        snackElement.className = 'selected-snack-item flex items-center justify-between bg-gray-50 p-2 rounded mb-2';
        snackElement.dataset.id = snackData.id;
        snackElement.innerHTML = `
            <div class="flex items-center">
                <img src="${snackData.image}" alt="${snackData.name}" class="h-8 w-8 rounded object-cover">
                <span class="ml-2 text-sm">${snackData.name}</span>
            </div>
            <div class="flex items-center">
                <span class="mr-2 text-sm">$${productPrices[snackData.id]}</span>
                <button type="button" class="text-gray-400 hover:text-gray-600 remove-snack" aria-label="Remove item">×</button>
            </div>
        `;

        selectedSnacksContainer.appendChild(snackElement);

        snackElement.querySelector('.remove-snack').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            removeSnack(snackData.id);
        });

        updatePriceDisplay();
    }

    // Add event listeners
    boxSizeSelect.addEventListener('change', updateMaxItems);
    quantityInput.addEventListener('input', updatePriceDisplay);
    orderTypeInputs.forEach(input => {
        input.addEventListener('change', updatePriceDisplay);
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (selectedSnacks.size === 0) {
            alert('Please select at least one snack!');
            return;
        }

        const boxSize = boxSizeSelect.value;
        const maxItems = boxSizeLimits[boxSize];
        if (selectedSnacks.size > maxItems) {
            alert(`You can only select up to ${maxItems} snacks for a ${boxSize} box!`);
            return;
        }

        this.submit();
    });

    document.querySelectorAll('.snack-card').forEach(card => {
        card.addEventListener('click', function() {
            if (this.classList.contains('disabled')) return;
            
            const snackData = {
                id: this.dataset.id,
                name: this.dataset.name,
                image: this.dataset.image,
                price: this.dataset.price
            };
            addSnackToSelection(snackData);
        });
    });

    document.querySelector('button[type="button"]').onclick = function() {
    // Get the selected snacks from our existing Set
    const selectedSnacksArray = Array.from(selectedSnacks);
    
    // Get current box size
    const boxSize = document.getElementById('box-size').value;
    
    // Get quantity
    const quantity = parseInt(document.getElementById('quantity-input').value) || 1;
    
    // Get order type
    const orderType = document.querySelector('input[name="order_type"]:checked').value;
    
    // Get total price
    const totalPrice = parseFloat(document.getElementById('total-price').textContent);
    
    // Create item object for cart
    const item = {
        id: 'custom-' + Date.now(), // Unique ID for custom box
        type: 'custom',
        name: `Custom ${boxSize.charAt(0).toUpperCase() + boxSize.slice(1)} Box`,
        quantity: quantity,
        price: totalPrice,
        orderType: orderType,
        boxSize: boxSize,
        products: selectedSnacksArray
    };

    // Only add to cart if there are selected snacks
    if (selectedSnacksArray.length > 0) {
        // Check if we have enough snacks for the selected box size
        const maxItems = boxSizeLimits[boxSize];
        if (selectedSnacksArray.length <= maxItems) {
            addToCart(item);
        } else {
            alert(`You can only select up to ${maxItems} snacks for a ${boxSize} box!`);
        }
    } else {
        alert('Please select at least one snack before adding to cart!');
    }
};

    // Initialize the page
    updateMaxItems();
});

 // Move selectedSnacks to global scope
</script>
<script src="cart.js" defer></script>
</body>
</html>