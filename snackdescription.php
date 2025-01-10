<?php
$product = [
    'name' => 'Japanese Snacks',
    'price' => 50.52,
    'status' => 'In stock',
    'description' => 'Enjoy a mix of sweet, savory, and adventurous flavors, including:',
    'features' => [
        'Japanese mochi with tea cookies',
        'Popular Japanese chocolate bars and chips',
        'Something for the family, alone, and more',
        'Traditional seasonal Japanese snacks',
        'Unique potato chips and savory delights'
    ],
    'sizes' => ['Small', 'Medium', 'Large']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="md:flex">
                <!-- Product Image -->
                <div class="md:w-1/2 p-6">
                    <div class="bg-pink-50 rounded-lg p-4">
                        <img 
                            src="snack.jpg" 
                            alt="Japanese Snacks Box" 
                            class="w-full h-auto object-cover rounded-lg"
                        >
                    </div>
                </div>

                <!-- Product Details -->
                <div class="md:w-1/2 p-6">
                    <div class="space-y-4">
                        <h1 class="text-3xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h1>

                        <div class="flex items-center space-x-2">
                            <span class="text-green-600 font-medium">
                                <?php echo htmlspecialchars($product['status']); ?>
                            </span>
                        </div>

                        <div class="text-2xl font-bold text-gray-900">
                            $<?php echo number_format($product['price'], 2); ?>
                        </div>

                        <!-- Middle Section: Order Details -->
                <div class="col-span-4">
                    <div class="mb-6">
                        <span class="text-gray-600">Size :</span>
                        <select class="border rounded px-2 py-1 ml-2">
                            <option>Large</option>
                            <option>Medium</option>
                            <option>Small</option>
                        </select>
                    </div>

                    <div class="bg-white">
                        <h2 class="text-[#DC143C] text-xl font-semibold mb-2">Total Amount</h2>
                        <p class="text-green-600 text-2xl font-bold mb-4">$50.52</p>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Quantity</label>
                            <input class="w-full border rounded px-3 py-2" type="number" name="quantity">
                        </div>

                        <div class="space-y-2 mb-6">
                            <label class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="radio" name="order_type" checked>
                                    <span class="ml-2">One-time order</span>
                                </div>
                                <span>$50.52</span>
                            </label>
                            <label class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="radio" name="order_type">
                                    <span class="ml-2">Subscribe & Save</span>
                                </div>
                                <span>(Monthly) $50.52</span>
                            </label>
                        </div>

                        <button class="w-full bg-green-600 text-white py-2 rounded-md mb-3">Buy Now</button>
                        <button class="w-full border border-orange-400 text-orange-400 py-2 rounded-md">+ Add to Cart</button>
                    </div>
                </div>
                        <!-- Description -->
                        <div class="pt-6">
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ($product['features'] as $feature): ?>
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