<?php
session_start();
include_once './dbconnection.php';

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
    $customerName = $_POST['customer_name'] ?? $userData['full_name'];
    $customerPhone = $_POST['customer_phone'] ?? $userData['phone'];
    $customerAddress = $_POST['customer_address'] ?? $userData['address'];
    $orderStatus = 'pending';
    $orderType = 'Customized';
    $paymentType = ($orderType === 'subscription') ? 'Subscription' : 'One-Time';
    
    // Create variables for bind_param
    $stmt = $conn->prepare("INSERT INTO Orders (
        user_id, 
        order_type, 
        product_id,
        snackbox_size, 
        product_price, 
        product_quantity, 
        payment_type,
        customer_name,
        customer_phone,
        customer_address,
        order_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind parameters using variables
    $stmt->bind_param(
        "isssdisssss",
        $userId,
        $orderType,
        $productIdsString,
        $boxSize,
        $finalTotal,
        $quantity,
        $paymentType,
        $customerName,
        $customerPhone,
        $customerAddress,
        $orderStatus
    );

    if ($stmt->execute()) {
        // Order saved successfully
        $orderId = $conn->insert_id;
        $_SESSION['order_success'] = true;
        echo "<script>
            alert('Order placed successfully!');
            window.location.href = 'index.php';
        </script>";
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
<body class="bg-gray-100 flex flex-col min-h-screen">
    <!-- Navigation Bar remains the same -->
    <nav class="bg-white py-4">
        <!-- Navigation content remains the same -->
    </nav>

    <main class="flex-grow py-8">
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
                        <div class="rounded-xl aspect-[1.6/1] relative shadow-sm overflow-hidden">
                            <img src="visacard.png" alt="VISA" class="absolute inset-0 h-full w-full object-cover">
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="payment-form">
                        <input type="hidden" name="process_payment" value="1">
                        <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($orderType); ?>">
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

    <!-- Footer remains the same -->
    <footer class="bg-[#FFDAC1] py-12 mt-auto">
        <!-- Footer content remains the same -->
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

</body>
</html>
