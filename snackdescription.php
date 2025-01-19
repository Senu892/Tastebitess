<?php
session_start();
include 'dbconnection.php';

// Get snack box ID from URL
$snackbox_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch snack box details
$sql = "SELECT snackbox_name, snackbox_size, snacks_selected, snackbox_price, snackboximage_url 
        FROM snackboxes 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $snackbox_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$product = $result->fetch_assoc();

// Convert snacks_selected string to array
$selected_snacks = explode(',', $product['snacks_selected']);

// Fetch product names for selected snacks
$product_ids = array_map('trim', $selected_snacks);
$product_ids_string = implode(',', $product_ids);

// Fetch product names from products table
$products_sql = "SELECT product_name FROM products WHERE id IN ($product_ids_string)";
$products_result = $conn->query($products_sql);

$product_features = array();
if ($products_result && $products_result->num_rows > 0) {
    while ($product_row = $products_result->fetch_assoc()) {
        $product_features[] = $product_row['product_name'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['snackbox_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="md:flex">
                <!-- Product Image -->
                <div class="md:w-1/2 p-6">
                    <div class="bg-pink-50 rounded-lg p-4">
                        <img 
                            src="<?php echo htmlspecialchars($product['snackboximage_url']); ?>" 
                            alt="<?php echo htmlspecialchars($product['snackbox_name']); ?>" 
                            class="w-full h-auto object-cover rounded-lg"
                        >
                    </div>
                </div>
                

                <!-- Product Details -->
                <div class="md:w-1/2 p-6">
                    <div class="space-y-4">
                        <h1 class="text-3xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($product['snackbox_name']); ?>
                        </h1>

                        <div class="flex items-center space-x-2">
                            <span class="text-green-600 font-medium">In stock</span>
                        </div>

                        <div class="text-2xl font-bold text-gray-900">
                            $<?php echo number_format($product['snackbox_price'], 2); ?>
                        </div>

                        <!-- Middle Section: Order Details -->
                        <div class="col-span-4">
                            <div class="mb-6">
                                <span class="text-gray-600">Size:</span>
                                <select class="border rounded px-2 py-1 ml-2">
                                    <option selected><?php echo htmlspecialchars(ucfirst($product['snackbox_size'])); ?></option>
                                </select>
                            </div>

                            <div class="bg-white">
                                <h2 class="text-[#DC143C] text-xl font-semibold mb-2">Total Amount</h2>
                                <p class="text-green-600 text-2xl font-bold mb-4" id="totalAmount">
                                    $<?php echo number_format($product['snackbox_price'], 2); ?>
                                </p>
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 mb-2">Quantity</label>
                                    <input class="w-full border rounded px-3 py-2" type="number" name="quantity" value="1" min="1" id="quantity">
                                </div>

                                <div class="space-y-2 mb-6">
                                    <label class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="radio" name="order_type" value="onetime" checked id="onetimeOrder">
                                            <span class="ml-2">One-time order</span>
                                        </div>
                                        <span>$<?php echo number_format($product['snackbox_price'], 2); ?></span>
                                    </label>
                                    <label class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="radio" name="order_type" value="subscription" id="subscriptionOrder">
                                            <span class="ml-2">Subscribe & Save</span>
                                        </div>
                                        <span>(Monthly) $<?php echo number_format($product['snackbox_price'] * 0.9, 2); ?></span>
                                    </label>
                                </div>

                                <form action="checkoutpage.php" method="POST" id="purchase-form">
                                    
                                    <input type="hidden" name="snackbox_id" value="<?php echo $snackbox_id; ?>">
                                    <input type="hidden" name="box_size" value="<?php echo htmlspecialchars($product['snackbox_size']); ?>">
                                    <input type="hidden" name="selected_products" value="<?php echo htmlspecialchars($product['snacks_selected']); ?>">
                                    <input type="hidden" name="total_price" id="form-total-price" value="<?php echo htmlspecialchars($product['snackbox_price']); ?>">
                                    <input type="hidden" name="quantity" id="form-quantity" value="1">
                                    <input type="hidden" name="order_type" id="form-order-type" value="onetime">

                                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-md mb-3">Buy Now</button>
                                    <button type="button" class="w-full border border-orange-400 text-orange-400 py-2 rounded-md">+ Add to Cart</button>
                                </form>
                            </div>
                        </div>


                        <!-- Description -->
                        <div class="pt-6">
                            <p class="text-gray-600 mb-4">
                                This snack box includes the following delicious items:
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ($product_features as $feature): ?>
                                    <li class="flex items-start">
                                        <span class="text-pink-600 mr-2">•</span>
                                        <span class="text-gray-600">
                                            <?php echo htmlspecialchars($feature); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
       
    <!-- Footer -->
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
    
    // Update hidden form values when quantity or order type changes
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('purchase-form');
        const quantityInput = document.getElementById('quantity');
        const orderTypeInputs = document.querySelectorAll('input[name="order_type"]');
        const formQuantity = document.getElementById('form-quantity');
        const formOrderType = document.getElementById('form-order-type');
        const formTotalPrice = document.getElementById('form-total-price');
        const basePrice = <?php echo $product['snackbox_price']; ?>;

        function updateFormValues() {
            const quantity = parseInt(quantityInput.value) || 1;
            const orderType = document.querySelector('input[name="order_type"]:checked').value;
            const price = orderType === 'subscription' ? basePrice * 0.9 : basePrice;
            const total = price * quantity;

            formQuantity.value = quantity;
            formOrderType.value = orderType;
            formTotalPrice.value = total.toFixed(2);
        }

        // Add event listeners
        quantityInput.addEventListener('change', updateFormValues);
        orderTypeInputs.forEach(input => {
            input.addEventListener('change', updateFormValues);
        });

        // Initial update
        updateFormValues();

        // Form validation
        form.addEventListener('submit', function(e) {
            const quantity = parseInt(quantityInput.value);
            if (!quantity || quantity < 1) {
                e.preventDefault();
                alert('Please select a valid quantity');
                return false;
            }
        });

        document.querySelector('button[type="button"]').onclick = function() {
    const item = {
        id: <?php echo $snackbox_id; ?>,
        type: 'premade',
        name: '<?php echo addslashes($product['snackbox_name']); ?>',
        quantity: parseInt(document.getElementById('quantity').value),
        price: <?php echo $product['snackbox_price']; ?>,
        orderType: document.querySelector('input[name="order_type"]:checked').value
    };
    addToCart(item);
};
    });

    function submitOrder() {
        // Create a form dynamically
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'checkoutpage.php';
        
        // Add necessary fields
        const fields = {
            'snackbox_id': '<?php echo $snackbox_id; ?>',
            'quantity': document.getElementById('quantity').value,
            'order_type': document.querySelector('input[name="order_type"]:checked').value,
            'total_price': document.getElementById('totalAmount').textContent.replace('$', '')
        };
        
        // Create hidden inputs
        for (const [key, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    }

         // Get base price from PHP
    const basePrice = <?php echo $product['snackbox_price']; ?>;
    
    // Function to update total amount
    function updateTotalAmount() {
        const quantity = parseInt(document.getElementById('quantity').value);
        const orderType = document.querySelector('input[name="order_type"]:checked').value;
        
        // Calculate price based on order type
        let price = basePrice;
        if (orderType === 'subscription') {
            // 10% discount for subscription
            price = basePrice * 0.9;
        }
        
        // Calculate total
        const total = price * quantity;
        
        // Update display
        document.getElementById('totalAmount').textContent = `$${total.toFixed(2)}`;
        
        // Update form hidden input
        document.getElementById('form-total-price').value = total.toFixed(2);
    }

    // Add event listeners
    document.getElementById('quantity').addEventListener('change', updateTotalAmount);
    document.getElementById('onetimeOrder').addEventListener('change', updateTotalAmount);
    document.getElementById('subscriptionOrder').addEventListener('change', updateTotalAmount);
    
    // Initialize total amount
    updateTotalAmount();

    
    </script>
    <script src="cart.js" defer></script>
    </body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>
