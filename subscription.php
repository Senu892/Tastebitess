<?php
session_start();
include_once './dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get logged-in user ID
$userId = $_SESSION['user_id'];

// Handle subscription cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_subscription'])) {
    $orderId = $_POST['order_id'];
    $updateQuery = "UPDATE Orders SET payment_type = 'Subscription;cancel' WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
}

// Fetch subscription orders for the logged-in user
$query = "SELECT order_id, product_id, snackbox_size, product_price, product_quantity, payment_type, order_date 
          FROM Orders 
          WHERE user_id = ? AND payment_type LIKE 'Subscription%'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$subscriptionOrders = [];
while ($row = $result->fetch_assoc()) {
    $subscriptionOrders[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #F9FAFB;
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <!-- Navigation Bar -->
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
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800">My Subscriptions</h2>
                <p class="text-sm text-gray-500">Manage your active subscription orders below.</p>
            </div>

            <!-- Subscription Orders List -->
            <?php if (!empty($subscriptionOrders)) : ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($subscriptionOrders as $order) : 
                        // Split payment_type to get the subscription status
                        list($paymentType, $subscriptionStatus) = explode(';', $order['payment_type']);
                        
                        // Calculate next payment renewal date
                        $startDate = new DateTime($order['order_date']);
                        $nextRenewalDate = $startDate->modify('+1 month')->format('F j, Y');
                    ?>
                        <div class="bg-white shadow-md rounded-xl p-6 border">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Order ID: <?php echo htmlspecialchars($order['order_id']); ?></h3>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600"><strong>Snack Box Size:</strong> <?php echo htmlspecialchars($order['snackbox_size']); ?></p>
                                <p class="text-sm text-gray-600"><strong>Quantity:</strong> <?php echo htmlspecialchars($order['product_quantity']); ?> box(es)</p>
                                <p class="text-sm text-gray-600"><strong>Price:</strong> $<?php echo number_format($order['product_price'], 2); ?></p>
                                <p class="text-sm text-gray-600"><strong>Payment Type:</strong> <?php echo htmlspecialchars($paymentType); ?></p>
                                <p class="text-sm text-gray-600"><strong>Subscription Status:</strong> 
                                    <span class="<?php echo ($subscriptionStatus === 'active') ? 'text-green-500' : 'text-red-500'; ?>">
                                        <?php echo ucfirst($subscriptionStatus); ?>
                                    </span>
                                </p>
                                <?php if ($subscriptionStatus === 'active') : ?>
                                    <p class="text-sm text-gray-600"><strong>Next Renewal Date:</strong> <?php echo htmlspecialchars($nextRenewalDate); ?></p>
                                <?php endif; ?>
                            </div>
                            <!-- Cancel Subscription Button -->
                            <?php if ($subscriptionStatus === 'active') : ?>
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                    <button type="submit" name="cancel_subscription" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                        Cancel Subscription
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="bg-orange-50 text-orange-900 p-6 rounded-xl text-center">
                    <p class="text-sm">You currently have no active subscriptions.</p>
                </div>
            <?php endif; ?>
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
    <script src="cart.js" defer></script>
</body>
</html>