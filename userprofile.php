<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Database connection
require_once 'dbconnection.php';

// Handle form submission for user details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        $stmt = $conn->prepare("UPDATE user SET full_name = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $_POST['full_name'], $_POST['phone'], $_POST['address'], $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Profile updated successfully!";
        }
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage());
        $_SESSION['error'] = "Error updating profile";
    }
}

// Get user details from database using session user_id
try {
    $stmt = $conn->prepare("SELECT full_name, phone, address FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // If user not found, redirect to login
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Get user's orders with product names
    // First get the orders
    $orders_stmt = $conn->prepare("
        SELECT 
            o.order_id,
            o.order_date,
            o.product_id,
            o.snackbox_size,
            o.product_price,
            o.product_quantity,
            o.order_status,
            o.payment_type
        FROM orders o
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC
    ");
    $orders_stmt->bind_param("i", $_SESSION['user_id']);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $orders = [];
    
    while ($order = $orders_result->fetch_assoc()) {
        // Get all products for this order
        $product_ids = explode(',', $order['product_id']);
        $product_names = [];
        
        // Prepare and execute query for each product
        $product_stmt = $conn->prepare("SELECT product_name FROM products WHERE id = ?");
        
        foreach ($product_ids as $pid) {
            $product_stmt->bind_param("i", $pid);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            if ($product_row = $product_result->fetch_assoc()) {
                $product_names[] = $product_row['product_name'];
            }
        }
        
        // Add the product names to the order array
        $order['product_names'] = implode(', ', $product_names);
        $orders[] = $order;
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $user = [
        'full_name' => $_SESSION['username'],
        'phone' => 'Error loading data',
        'address' => 'Error loading data'
    ];
    $orders = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - TasteBites</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Alpine.js for handling modals -->
    <script src="https://unpkg.com/alpinejs" defer></script>
</head>
<body class="min-h-screen flex flex-col bg-gray-100" x-data="{ editModal: false }">
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

    <!-- Edit Profile Modal -->
    <div x-show="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4" @click.away="editModal = false">
            <h2 class="text-2xl font-semibold mb-6">Edit Profile</h2>
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                        ><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="editModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="update_user"
                            class="px-4 py-2 bg-orange-500 text-white rounded-md text-sm font-medium hover:bg-orange-600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- My Account Header -->
        <h1 class="text-pink-600 text-2xl font-semibold mb-8">My Account</h1>

        <!-- User Info Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-12">
            <!-- Personal Info Card -->
            <div class="bg-orange-50 rounded-lg p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="font-medium text-lg"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <button @click="editModal = true" class="text-orange-500 hover:text-orange-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Shipping Address Card -->
            <div class="bg-orange-50 rounded-lg p-6 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-lg mb-2">Shipping Address</h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['address']); ?></p>
                    </div>
                    <button @click="editModal = true" class="text-orange-500 hover:text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="mb-12">
            <h2 class="text-xl font-semibold mb-6">Recent Orders</h2>
            <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-sm">
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Size</th>
                                <th class="px-6 py-4">Products</th>
                                <th class="px-6 py-4">Price</th>
                                <th class="px-6 py-4">Quantity</th>
                                <th class="px-6 py-4">Payment</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr class="border-t">
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">No orders found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td class="px-6 py-4"><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($order['snackbox_size']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            
                                            <span class="font-medium">
                                                <?php 
                                                    echo !empty($order['product_names']) ? htmlspecialchars($order['product_names']) : 'Customized Box';
                                                ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">$<?php echo number_format($order['product_price'], 2); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($order['product_quantity']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($order['payment_type'] ?? 'Standard'); ?></td>
                                    <td class="px-6 py-4">
                                        <button class="px-4 py-1.5 text-orange-500 border border-orange-500 rounded-full text-sm hover:bg-orange-50 transition">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
</body>
</html>