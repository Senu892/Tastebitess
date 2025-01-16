<?php
session_start();
include_once './dbconnection.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $userId = $_SESSION['user_id'];
    $fullName = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $updateQuery = "UPDATE user SET full_name = ?, phone = ?, address = ? WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssi", $fullName, $phone, $address, $userId);
    
    if ($stmt->execute()) {
        // Refresh user data after update
        $userQuery = "SELECT full_name, phone, address FROM user WHERE user_id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $userData = $userResult->fetch_assoc();
    }

    // Preserve checkout data
    $_POST['selected_products'] = $_POST['previous_selected_products'];
    $_POST['total_price'] = $_POST['previous_total_price'];
    $_POST['box_size'] = $_POST['previous_box_size'];
    $_POST['quantity'] = $_POST['previous_quantity'];
    $_POST['order_type'] = $_POST['previous_order_type'];
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Check if form data is received
if (!isset($_POST['selected_products']) || !isset($_POST['total_price'])) {
    header('Location: customize.php');
    exit();
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

// Get selected products details
$selectedProducts = json_decode($_POST['selected_products'], true);
$boxSize = $_POST['box_size'];
$quantity = $_POST['quantity'];
$orderType = $_POST['order_type'];
$totalPrice = $_POST['total_price'];

// Convert the products array to a comma-separated string
$productIdsString = implode(',', $selectedProducts);

// Calculate final prices
$subtotal = floatval($totalPrice);
$shipping = 3.00;
$finalTotal = $subtotal + $shipping;

// Process the order when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Prepare the order insertion query
    $orderQuery = "INSERT INTO Orders (
        user_id, 
        order_type, 
        product_id,
        snackbox_size, 
        product_price, 
        product_quantity, 
        payment_type
    ) VALUES (?, 'Customized', ?, ?, ?, ?, ?)";
    
    $paymentType = ($orderType === 'subscription') ? 'Subscription' : 'One-Time';
    
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param(
        "issdis",
        $userId,
        $productIdsString,
        $boxSize,
        $finalTotal,
        $quantity,
        $paymentType
    );

    if ($stmt->execute()) {
        // Order saved successfully
        $orderId = $conn->insert_id;
        exit();
    } else {
        // Handle error
        $error = "Error processing your order. Please try again.";
    }
}

// Get selected products details
$selectedProducts = json_decode($_POST['selected_products'], true);
$boxSize = $_POST['box_size'];
$quantity = $_POST['quantity'];
$orderType = $_POST['order_type'];
$totalPrice = $_POST['total_price'];

// Convert the products array to a comma-separated string
$productIdsString = implode(',', $selectedProducts);

// Calculate final prices
$subtotal = floatval($totalPrice);
$shipping = 3.00;
$finalTotal = $subtotal + $shipping;

// Process the order when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Prepare the order insertion query
    $orderQuery = "INSERT INTO Orders (
        user_id, 
        order_type, 
        product_id,
        snackbox_size, 
        product_price, 
        product_quantity, 
        payment_type
    ) VALUES (?, 'Customized', ?, ?, ?, ?, ?)";
    
    $paymentType = ($orderType === 'subscription') ? 'Subscription' : 'One-Time';
    
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param(
        "issdis",  // Changed parameter type for product_price to 'd' for double
        $userId,
        $productIdsString,  // Using the comma-separated string instead of array
        $boxSize,
        $finalTotal,
        $quantity,
        $paymentType
    );

    if ($stmt->execute()) {
        // Order saved successfully
        $orderId = $conn->insert_id;
        
        exit();
    } else {
        // Handle error
        $error = "Error processing your order. Please try again.";
    }
}

// Rest of your existing code for fetching product details
$productDetails = [];
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
<body class=" bg-gray-100 flex flex-col min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white py-4">
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
                <button class="text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    

    <main class="flex-grow py-8">
        <!-- Main Container -->
        <div class="max-w-[1200px] mx-auto px-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-rose-500 font-medium text-xl">Checkout</h2>
                <span class="bg-orange-50 text-xs px-3 py-1 rounded-full text-orange-800">
                    <?php echo ucfirst($orderType); ?> Order
                </span>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-[1.2fr,0.8fr] gap-8">
                <!-- Left Column - Payment -->
                <div class="bg-orange-50 rounded-2xl p-8">
                    <!-- Credit Card Display -->
                    <div class="mb-8 max-w-md">
                        <div class="card-gradient rounded-xl p-4 aspect-[1.6/1] relative shadow-sm">
                            <div class="absolute bottom-4 left-4">
                                <div class="flex gap-2 mb-2">
                                    <div class="w-8 h-5 bg-white/20 rounded"></div>
                                    <div class="w-8 h-5 bg-white/20 rounded"></div>
                                </div>
                            </div>
                            <img src="visa.png" alt="VISA" class="h-8 absolute top-4 right-4">
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="payment-form">
                        <input type="hidden" name="process_payment" value="1">
                        <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($orderType); ?>">
                        <input type="hidden" name="box_size" value="<?php echo htmlspecialchars($boxSize); ?>">
                        <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
                        <input type="hidden" name="selected_products" value="<?php echo htmlspecialchars($_POST['selected_products']); ?>">
                        <input type="hidden" name="total_price" value="<?php echo htmlspecialchars($finalTotal); ?>">

                        <div class="mb-8">
                            <label class="block text-gray-500 text-sm mb-2">Cardholder Name</label>
                            <input type="text" name="cardholder_name" class="input-field w-full bg-transparent">
                        </div>
                        <div class="mb-8">
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
                    </form>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Profile Box -->
                    <div class="bg-orange-50 rounded-2xl p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex items-start gap-3 w-full" id="profile-display">
                                <div class="w-8 h-8 bg-white rounded-full"></div>
                                <div>
                                    <p id="display-name"><?php echo htmlspecialchars($userData['full_name']); ?></p>
                                    <p id="display-address"><?php echo htmlspecialchars($userData['address']); ?></p>
                                    <p id="display-phone"><?php echo htmlspecialchars($userData['phone']); ?></p>
                                </div>
                            </div>
                            <!-- Edit Form (Initially Hidden) -->
                            <form id="profile-edit-form" class="hidden w-full" method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                <input type="hidden" name="previous_selected_products" value='<?php echo htmlspecialchars($_POST['selected_products']); ?>'>
                                <input type="hidden" name="previous_total_price" value="<?php echo htmlspecialchars($_POST['total_price']); ?>">
                                <input type="hidden" name="previous_box_size" value="<?php echo htmlspecialchars($_POST['box_size']); ?>">
                                <input type="hidden" name="previous_quantity" value="<?php echo htmlspecialchars($_POST['quantity']); ?>">
                                <input type="hidden" name="previous_order_type" value="<?php echo htmlspecialchars($_POST['order_type']); ?>">
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Full Name</label>
                                        <input type="text" name="full_name" class="w-full p-2 border rounded" 
                                            value="<?php echo htmlspecialchars($userData['full_name']); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Address</label>
                                        <input type="text" name="address" class="w-full p-2 border rounded"
                                            value="<?php echo htmlspecialchars($userData['address']); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Phone</label>
                                        <input type="text" name="phone" class="w-full p-2 border rounded"
                                            value="<?php echo htmlspecialchars($userData['phone']); ?>">
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="bg-rose-500 text-white px-4 py-2 rounded">Save</button>
                                        <button type="button" onclick="toggleEdit()" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
                                    </div>
                                </div>
                            </form>
                            <button onclick="toggleEdit()" class="text-rose-500" id="edit-button">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Order Summary Box -->
                    <div class="bg-orange-50 rounded-2xl p-6">
                        <h3 class="text-orange-900 text-lg font-medium mb-6">Order Summary</h3>
                        
                        <div class="space-y-4 mb-6">
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
                        </div>

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
    <!-- Footer -->
    <footer class="bg-[#FFDAC1] py-12 mt-auto">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <img src="logo.png" alt="TasteBites" class="h-11 mb-4">
                    <p class="text-gray-800">Sweet Every Bite</p>
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <h3 class="font-bold text-lg mb-4">Navigate</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="hover:text-gray-600">Home</a></li>
                            <li><a href="#" class="hover:text-gray-600">Snacks</a></li>
                            <li><a href="#" class="hover:text-gray-600">Subscription</a></li>
                            <li><a href="#" class="hover:text-gray-600">About Us</a></li>
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
            <div class="mt-12 pt-8 border-t border-orange-200 text-sm text-gray-800">
                © 2024 Taste Bites. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script>
    // Profile editing functions
    function toggleEdit() {
        const displayEl = document.getElementById('profile-display');
        const formEl = document.getElementById('profile-edit-form');
        const editButton = document.getElementById('edit-button');
        
        if (formEl.classList.contains('hidden')) {
            displayEl.classList.add('hidden');
            formEl.classList.remove('hidden');
            editButton.classList.add('hidden');
        } else {
            displayEl.classList.remove('hidden');
            formEl.classList.add('hidden');
            editButton.classList.remove('hidden');
        }
    }

    // Profile form validation
    document.getElementById('profile-edit-form').addEventListener('submit', function(e) {
        const phone = document.querySelector('input[name="phone"]').value;
        const fullName = document.querySelector('input[name="full_name"]').value;
        const address = document.querySelector('input[name="address"]').value;

        if (!fullName.trim()) {
            e.preventDefault();
            alert('Please enter your full name');
            return;
        }

        if (!address.trim()) {
            e.preventDefault();
            alert('Please enter your address');
            return;
        }

        if (!phone.trim() || !/^\d{10}$/.test(phone.replace(/\D/g, ''))) {
            e.preventDefault();
            alert('Please enter a valid 10-digit phone number');
            return;
        }
    });

    // Format card number input
    document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        if (value.length > 16) {
            value = value.slice(0, 16);
        }
        // Add spaces after every 4 digits for visual formatting
        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        e.target.value = value;
    });

    // Format expiration date input
    document.querySelector('input[name="exp_date"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        if (value.length > 4) {
            value = value.slice(0, 4);
        }
        if (value.length >= 2) {
            value = value.slice(0, 2) + '/' + value.slice(2);
        }
        e.target.value = value;
    });

    // Format CVC input
    document.querySelector('input[name="cvc"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        if (value.length > 3) {
            value = value.slice(0, 3);
        }
        e.target.value = value;
    });

    // Add basic form validation for payment
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const cardholderName = document.querySelector('input[name="cardholder_name"]').value;
        const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
        const expDate = document.querySelector('input[name="exp_date"]').value;
        const cvc = document.querySelector('input[name="cvc"]').value;

        if (cardholderName.trim() === '') {
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

    // Optional: Real-time validation feedback
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
    
</body>
</html>