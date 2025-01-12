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
$userQuery = "SELECT full_name, phone, address FROM user WHERE user_id = ?"; // Adjust column names as needed
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows > 0) {
    $userData = $userResult->fetch_assoc();
} else {
    // Handle case where user data is not found
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

// Calculate final prices
$subtotal = floatval($totalPrice);
$shipping = 3.00;
$finalTotal = $subtotal + $shipping;

// Fetch selected products details from database
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
<body class="flex flex-col min-h-screen">
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
                    <form action="process_payment.php" method="POST" class="max-w-md" id="payment-form">
                        <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($orderType); ?>">
                        <input type="hidden" name="box_size" value="<?php echo htmlspecialchars($boxSize); ?>">
                        <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
                        <input type="hidden" name="selected_products" value="<?php echo htmlspecialchars($_POST['selected_products']); ?>">
                        <input type="hidden" name="total_price" value="<?php echo htmlspecialchars($finalTotal); ?>">

                        <div class="mb-8">
                            <label class="block text-gray-500 text-sm mb-2">Cardholder Name</label>
                            <input type="text" class="input-field w-full bg-transparent">
                        </div>
                        <div class="mb-8">
                            <label class="block text-gray-500 text-sm mb-2">Card Number</label>
                            <input type="text" class="input-field w-full bg-transparent">
                        </div>
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <label class="block text-gray-500 text-sm mb-2">Exp Date</label>
                                <input type="text" class="input-field w-full bg-transparent">
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm mb-2">CVC</label>
                                <input type="text" class="input-field w-full bg-transparent">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Profile Box -->
                    <div class="bg-orange-50 rounded-2xl p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-white rounded-full"></div>
                                <div>
                                    <p> <?php echo htmlspecialchars($userData['full_name']); ?></p>
                                    <p> <?php echo htmlspecialchars($userData['address']); ?></p>
                                    <p> <?php echo htmlspecialchars($userData['phone']); ?></p>
                                </div>
                            </div>
                            <button class="text-rose-500">
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
                                    <img src="snack.jpg" alt="Japanese Snacks Box" class="w-full h-full object-contain">
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
        // Add basic form validation
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const cardNumber = document.querySelector('input[name="card_number"]').value;
            const expDate = document.querySelector('input[name="exp_date"]').value;
            const cvc = document.querySelector('input[name="cvc"]').value;

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

            if (!/^\d{3}$/.test(cvc)) {
                e.preventDefault();
                alert('Please enter a valid 3-digit CVC');
                return;
            }
        });

        // Format expiration date input
        document.querySelector('input[name="exp_date"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2,4);
            }
            e.target.value = value;
        });
    </script>
    
</body>
</html>