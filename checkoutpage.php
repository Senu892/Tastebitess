<?php
session_start();
include_once './dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$snackbox_name = "Custom Snack Box";
$error = null;
$boxSize = '';
$quantity = 1;
$selectedProducts = [];
$productDetails = [];

// Initialize and standardize order type
$orderType = isset($_POST['order_type']) ? $_POST['order_type'] : 
              (isset($_SESSION['cart_data']['order_type']) ? $_SESSION['cart_data']['order_type'] : 'One-Time');

// Standardize order type format
if (strtolower($orderType) === 'subscription' || strtolower($orderType) === 'subscribe') {
    $orderType = 'Subscription';
} else {
    $orderType = 'One-Time';
}

// Initialize box size from POST data
$boxSize = isset($_POST['box_size']) ? $_POST['box_size'] : 'Regular';

// For predefined snackbox orders
if (isset($_POST['snackbox_id'])) {
    $snackbox_id = $_POST['snackbox_id'];
    $boxSize = $_POST['box_size'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];
    

    $stmt = $conn->prepare("SELECT snackbox_name FROM snackboxes WHERE id = ?");
    $stmt->bind_param("i", $snackbox_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $snackbox_name = $row['snackbox_name'];
    }
    $stmt->close();
}

// Get user details
$userId = $_SESSION['user_id'];
$userQuery = "SELECT full_name, phone, address FROM user WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows > 0) {
    $userData = $userResult->fetch_assoc();
} else {
    $userData = [
        'full_name' => 'N/A',
        'phone' => 'N/A',
        'address' => 'N/A'
    ];
}

// Get selected products and their details
if (isset($_POST['selected_products'])) {
    $selectedProducts = json_decode($_POST['selected_products'], true);
    if (!is_array($selectedProducts)) {
        $selectedProducts = explode(',', $_POST['selected_products']);
    }
    
    // Filter out invalid values
    $selectedProducts = array_filter($selectedProducts, function($id) {
        return !empty($id) && is_numeric($id);
    });
    
    // Get product details if there are valid products
    if (!empty($selectedProducts)) {
        $placeholders = str_repeat('?,', count($selectedProducts) - 1) . '?';
        $productQuery = "SELECT * FROM products WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($productQuery);
        $stmt->bind_param(str_repeat('i', count($selectedProducts)), ...$selectedProducts);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $productDetails[] = $row;
        }
    }
}

// Process the order when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $customerName = $_POST['customer_name'] ?? $userData['full_name'];
    $customerPhone = $_POST['customer_phone'] ?? $userData['phone'];
    $customerAddress = $_POST['customer_address'] ?? $userData['address'];
    $orderStatus = 'pending';
    
    // Standardize payment type
    $paymentType = isset($_POST['order_type']) ? 
                   (strtolower($_POST['order_type']) === 'subscription' ? 'Subscription' : 'One-Time') : 
                   'One-Time';
    
    // Get cart data
    $cartData = [];
    if (isset($_POST['cart_data']) && !empty($_POST['cart_data'])) {
        $cartData = json_decode($_POST['cart_data'], true);
        error_log("Cart Data: " . print_r($cartData, true)); // Debug log
    } elseif (isset($_POST['selected_products'])) {
        // Handle single item orders
        $selectedProducts = json_decode($_POST['selected_products'], true);
        if (!is_array($selectedProducts)) {
            $selectedProducts = explode(',', $_POST['selected_products']);
        }
        
        // Filter out invalid product IDs
        $selectedProducts = array_filter($selectedProducts, function($id) {
            return !empty($id) && is_numeric($id);
        });

        $cartData = [[
            'type' => isset($_POST['snackbox_id']) ? 'Predefined' : 'Customized',
            'productIds' => $selectedProducts,
            'size' => $_POST['box_size'] ?? '',
            'quantity' => $_POST['quantity'] ?? 1,
            'price' => $_POST['total_price'] ?? 0,
            'snackboxId' => $_POST['snackbox_id'] ?? null,
            'order_type' => $paymentType
        ]];
    }

    // Initialize success counter
    $successfulOrders = 0;
    
    // Process each item in the cart
    foreach ($cartData as $item) {
        // Ensure productIds is an array and contains only valid IDs
        $productIds = isset($item['productIds']) ? (array)$item['productIds'] : [];
        $productIds = array_filter($productIds, function($id) {
            return !empty($id) && is_numeric($id);
        });
        
        // Skip if no valid product IDs
        if (empty($productIds)) {
            continue;
        }

        // Convert product IDs to a comma-separated string for storage
        $productIdString = implode(',', $productIds);
        
        // Determine the correct order type
        $itemType = isset($item['type']) ? $item['type'] : 
                   (isset($item['snackboxId']) && !empty($item['snackboxId']) ? 'Predefined' : 'Customized');
        
        // Get the box size, ensuring it's not empty
        $itemSize = !empty($item['size']) ? $item['size'] : 
                   (!empty($_POST['box_size']) ? $_POST['box_size'] : 'Regular');
        
        $itemQuantity = $item['quantity'] ?? 1;
        $itemPrice = $item['price'] ?? 0;
        $snackboxId = $item['snackboxId'] ?? null;
        
        // Debug logging
        error_log("Processing order - Type: " . $itemType . ", Size: " . $itemSize . ", Product IDs: " . $productIdString);
        
        // Create the order
        $stmt = $conn->prepare("INSERT INTO Orders (
            user_id, 
            order_type,
            product_id,
            snackbox_id,
            snackbox_size, 
            product_price, 
            product_quantity, 
            payment_type,
            customer_name,
            customer_phone,
            customer_address,
            order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "issisdisssss",
            $userId,
            $itemType,
            $productIdString,
            $snackboxId,
            $itemSize,
            $itemPrice,
            $itemQuantity,
            $paymentType,  // Use standardized payment type
            $customerName,
            $customerPhone,
            $customerAddress,
            $orderStatus
        );

        if ($stmt->execute()) {
            $successfulOrders++;
            error_log("Order created successfully - Type: $itemType, Size: $itemSize");
        } else {
            error_log("Error creating order: " . $stmt->error);
        }
    }
    
    // Check if all orders were successful
    if ($successfulOrders === count($cartData)) {
        $_SESSION['order_success'] = true;
        echo "<script>
            localStorage.removeItem('cart');
            alert('Order placed successfully!');
            window.location.href = 'index.php';
        </script>";
        exit();
    } else {
        $error = "Error processing your order. Please try again.";
    }
}

// Calculate final prices
$subtotal = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
$shipping = 3.00;
$finalTotal = $subtotal + $shipping;

// Set box size and quantity if not already set
if (!isset($boxSize) && isset($_POST['box_size'])) {
    $boxSize = $_POST['box_size'];
}
if (!isset($quantity) && isset($_POST['quantity'])) {
    $quantity = $_POST['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TasteBites Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #FFFFFF;
            min-height: 100vh;
        }
        .card-gradient {
            background: linear-gradient(135deg, #D4AF37 0%, #C5A028 100%);
        }
        .input-field {
            border-bottom: 1px solid #E5E5E5;
            border-top: none;
            border-left: none;
            border-right: none;
            border-radius: 0;
            padding: 8px 0;
            margin-bottom: 16px;
        }
        .input-field:focus {
            outline: none;
            border-color: #666;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <!-- Navigation Bar remains the same -->
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

    <main class="flex-grow py-8">
        <div class="max-w-[1200px] mx-auto px-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-rose-500 font-medium text-xl">Checkout</h2>
                <span class="bg-orange-50 text-xs px-3 py-1 rounded-full text-orange-800">
                    <?php echo ucfirst($order_type); ?> Order
                </span>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-[1.2fr,0.8fr] gap-8">
                <!-- Left Column - Payment -->
                <div class="bg-orange-50 rounded-2xl p-8">
                    <!-- Credit Card Display -->
                    <div class="mb-8 max-w-md">
                        <div class="rounded-xl aspect-[1.6/1] relative shadow-sm overflow-hidden">
                            <img src="visacard.png" alt="VISA" class="absolute inset-0 h-full w-full object-cover">
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="payment-form">
                        <input type="hidden" name="process_payment" value="1">
                        <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($order_type); ?>">
                        <input type="hidden" name="box_size" value="<?php echo htmlspecialchars($boxSize); ?>">
                        <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
                        <input type="hidden" name="selected_products" value='<?php echo htmlspecialchars($_POST['selected_products']); ?>'>
                        <input type="hidden" name="total_price" value="<?php echo htmlspecialchars($finalTotal); ?>">

                        <!-- Customer Details Section -->
                        <div class="mb-8">
                            <h3 class="text-gray-700 font-medium mb-4">Delivery Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-500 text-sm mb-2">Full Name</label>
                                    <input type="text" name="customer_name" class="input-field w-full bg-transparent" 
                                           value="<?php echo htmlspecialchars($userData['full_name']); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-500 text-sm mb-2">Phone</label>
                                    <input type="text" name="customer_phone" class="input-field w-full bg-transparent"
                                           value="<?php echo htmlspecialchars($userData['phone']); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-500 text-sm mb-2">Delivery Address</label>
                                    <input type="text" name="customer_address" class="input-field w-full bg-transparent"
                                           value="<?php echo htmlspecialchars($userData['address']); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details Section -->
                        <div class="mb-8">
                            <h3 class="text-gray-700 font-medium mb-4">Payment Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-500 text-sm mb-2">Cardholder Name</label>
                                    <input type="text" name="cardholder_name" class="input-field w-full bg-transparent">
                                </div>
                                <div>
                                    <label class="block text-gray-500 text-sm mb-2">Card Number</label>
                                    <input type="text" name="card_number" class="input-field w-full bg-transparent">
                                </div>
                                <div class="grid grid-cols-2 gap-8">
                                    <div>
                                        <label class="block text-gray-500 text-sm mb-2">Exp Date</label>
                                        <input type="text" name="exp_date" class="input-field w-full bg-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-500 text-sm mb-2">CVC</label>
                                        <input type="text" name="cvc" class="input-field w-full bg-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Order Summary Box -->
                   
                    <div class="bg-orange-50 rounded-2xl p-6">
                        <h3 class="text-orange-900 text-lg font-medium mb-6">Order Summary</h3>

                        <?php
                            // Check if cart_data exists in POST
                            if (isset($_POST['cart_data']) && !empty($_POST['cart_data'])) {
                                $cart_data = json_decode($_POST['cart_data'], true);
                                if ($cart_data) { ?>
                                    <div class="space-y-4 mb-6">
                                        <?php foreach ($cart_data as $item): ?>
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 bg-white rounded-xl p-2">
                                                    <img src="snack.png" alt="Snack Box" class="w-full h-full object-contain">
                                                </div>
                                                <div class="flex-grow">
                                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($item['name']); ?></p>
                                                    <p class="text-gray-500 text-sm">
                                                        <?php echo htmlspecialchars($item['type']); ?> - 
                                                        <?php echo htmlspecialchars($item['quantity']); ?> Box(es)
                                                    </p>
                                                    <p class="text-gray-500 text-sm">
                                                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                        <?php echo ($item['order_type'] === 'subscription') ? '(Monthly Subscription)' : ''; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php } 
                            } else { ?>
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 bg-white rounded-xl p-2">
                                        <img src="snack.png" alt="Japanese Snacks Box" class="w-full h-full object-contain">
                                    </div>
                                    <div class="flex-grow">
                                        <p class="text-gray-900 font-medium">Custom Snack Box</p>
                                        <p class="text-gray-500 text-sm"><?php echo ucfirst($boxSize); ?> Size - <?php echo $quantity; ?> Box(es)</p>
                                        <p class="text-gray-500 text-sm">
                                            $<?php echo number_format($subtotal, 2); ?> 
                                            <?php echo ($orderType === 'subscription') ? '(Monthly Subscription)' : ''; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php } ?>

                        <div class="space-y-3 border-t border-orange-200 pt-4">
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Shipping</span>
                                <span>$<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-medium text-gray-900 pt-2">
                                <span>Total</span>
                                <span>$<?php echo number_format($finalTotal, 2); ?></span>
                            </div>
                            
                            <button type="submit" form="payment-form" class="w-full bg-orange-200 text-orange-900 py-3 rounded-xl text-base font-medium mt-4">
                                Complete Purchase
                            </button>
                        </div>
                    </div>
                </div>
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
    // Format card number input
    document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 16) value = value.slice(0, 16);
        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        e.target.value = value;
    });

    // Format expiration date input
    document.querySelector('input[name="exp_date"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4) value = value.slice(0, 4);
        if (value.length >= 2) {
            value = value.slice(0, 2) + '/' + value.slice(2);
        }
        e.target.value = value;
    });

    // Format CVC input
    document.querySelector('input[name="cvc"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 3) value = value.slice(0, 3);
        e.target.value = value;
    });

    // Form validation
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const customerName = document.querySelector('input[name="customer_name"]').value;
        const customerPhone = document.querySelector('input[name="customer_phone"]').value;
        const customerAddress = document.querySelector('input[name="customer_address"]').value;
        const cardholderName = document.querySelector('input[name="cardholder_name"]').value;
        const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
        const expDate = document.querySelector('input[name="exp_date"]').value;
        const cvc = document.querySelector('input[name="cvc"]').value;

        // Validate customer details
        if (!customerName.trim()) {
            e.preventDefault();
            alert('Please enter your full name');
            return;
        }

        if (!customerPhone.trim() || !/^\d{10}$/.test(customerPhone.replace(/\D/g, ''))) {
            e.preventDefault();
            alert('Please enter a valid 10-digit phone number');
            return;
        }

        if (!customerAddress.trim()) {
            e.preventDefault();
            alert('Please enter your delivery address');
            return;
        }

        // Validate payment details
        if (!cardholderName.trim()) {
            e.preventDefault();
            alert('Please enter cardholder name');
            return;
        }

        if (!/^\d{16}$/.test(cardNumber)) {
            e.preventDefault();
            alert('Please enter a valid 16-digit card number');
            return;
        }

        if (!/^\d{2}\/\d{2}$/.test(expDate)) {
            e.preventDefault();
            alert('Please enter expiration date in MM/YY format');
            return;
        }

        // Basic expiration date validation
        const [month, year] = expDate.split('/');
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() % 100;
        const currentMonth = currentDate.getMonth() + 1;

        if (parseInt(month) < 1 || parseInt(month) > 12) {
            e.preventDefault();
            alert('Invalid month in expiration date');
            return;
        }

        if (parseInt(year) < currentYear || 
            (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
            e.preventDefault();
            alert('Card has expired');
            return;
        }

        if (!/^\d{3}$/.test(cvc)) {
            e.preventDefault();
            alert('Please enter a valid 3-digit CVC');
            return;
        }
    });

    // Real-time validation feedback
    const inputs = document.querySelectorAll('.input-field');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.style.borderColor = '#ff0000';
            } else {
                this.style.borderColor = '#E5E5E5';
            }
        });

        input.addEventListener('focus', function() {
            this.style.borderColor = '#666';
        });
    });
</script>
<scrip src="cart.js" defer></script>

</body>
</html>
