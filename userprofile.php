<?php
// Sample user data - in a real app, this would come from a database
$user = [
    'name' => 'Seerat Siddharth',
    'email' => 'seerat.sidharth@gmail.com',
    'phone' => '+91 77 777 7777',
    'address' => 'No. 64 Richmond Pl, Columbus 07, 600070'
];

// Sample orders data
$orders = [
    [
        'order_id' => '0001',
        'placed_on' => '01.12.2024',
        'items' => 'Japanese Snacks',
        'track' => 'Monthly Subscription',
        'status' => 'View'
    ],
    [
        'order_id' => '0002',
        'placed_on' => '01.12.2024',
        'items' => 'Japanese Snacks',
        'track' => 'Monthly Subscription',
        'status' => 'View'
    ],
    [
        'order_id' => '0003',
        'placed_on' => '01.12.2024',
        'items' => 'Japanese Snacks',
        'track' => 'Monthly Subscription',
        'status' => 'View'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - TasteBites</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white py-4 shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <img src="logo.png" alt="TasteBites" class="h-8">
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#" class="text-black hover:text-gray-600">Home</a>
                    <a href="#" class="text-black hover:text-gray-600">Snacks</a>
                    <a href="#" class="text-black hover:text-gray-600">Subscription</a>
                    <a href="#" class="text-black hover:text-gray-600">About Us</a>
                    <button class="bg-[#FFDAC1] px-6 py-1.5 rounded-full hover:bg-[#FFE4CF] transition">Senudi</button>
                    <button class="text-gray-600 hover:text-gray-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- My Account Header -->
        <h1 class="text-pink-600 text-2xl font-semibold mb-8">My Account</h1>

        <!-- User Info Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-12">
            <!-- Personal Info Card -->
            <div class="bg-orange-50 rounded-lg p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gray-300 rounded-full"></div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="font-medium text-lg"><?php echo htmlspecialchars($user['name']); ?></h3>
                            <button class="text-orange-500 hover:text-orange-600">
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
                    <button class="text-orange-500 hover:text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="mb-12">
            <h2 class="text-xl font-semibold mb-6">Recent Orders</h2>
            <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-sm">
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Placed On</th>
                                <th class="px-6 py-4">Items</th>
                                <th class="px-6 py-4">Track</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-6 py-4"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($order['placed_on']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gray-200 rounded"></div>
                                        <span class="font-medium"><?php echo htmlspecialchars($order['items']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($order['track']); ?></td>
                                <td class="px-6 py-4">
                                    <button class="px-4 py-1.5 text-orange-500 border border-orange-500 rounded-full text-sm hover:bg-orange-50 transition">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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