<?php
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-white py-4 z-10">
        <div class="max-w-7lg mx-auto px-4 flex justify-between items-center">
            <img src="logo.png" alt="TasteBites" class="h-8">
            <div class="flex space-x-8 items-center">
                <a href="index.php" class="text-black">Home</a>
                <a href="customize.php" class="text-black">Customize</a>
                <a href="subscription.php" class="text-black">Subscription</a>
                <a href="aboutuspage.php" class="text-black">About Us</a>
                <?php if ($isLoggedIn): ?>
                    <a href="userprofile.php" class="bg-[#FFDAC1] px-6 py-1 rounded-full">
                        <?php echo htmlspecialchars($username); ?>
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

    <!-- Main Content -->
    <main class="min-h-screen">
        <!-- Hero Section -->
        <section class="py-16 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="space-y-6">
                        <div class="text-sm text-gray-600 uppercase tracking-wider">About Us</div>
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight">
                            Creating delightful
                            <span class="relative">
                                <span class="relative z-10">Snacking</span>
                                <span class="absolute bottom-1 left-0 w-full h-3 bg-[#FFDAC1] -z-10"></span>
                            </span>
                            experiences.
                        </h1>
                        <p class="text-gray-600 text-lg leading-relaxed">
                            TasteBites is your premier destination for artisanal snacks and treats. We curate and deliver exceptional snacking experiences to food enthusiasts across Sri Lanka. With more than 50,000 satisfied customers and partnerships with over 100 local artisans, we're revolutionizing how people discover and enjoy premium snacks.
                        </p>
                        <div class="pt-4">
                            <a href="index.php" class="inline-block bg-[#FFDAC1] text-gray-800 px-8 py-3 rounded-full font-medium hover:bg-[#FFE5D1] transition-colors">
                                Start Snacking
                            </a>
                        </div>
                    </div>

                    <!-- Right Image -->
                    <div class="relative">
                        <div class="relative rounded-full overflow-hidden aspect-square">
                            <img src="snacks.jpg" alt="TasteBites Snack Experience" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-[#265E5A] mix-blend-multiply rounded-full"></div>
                        </div>
                        <!-- Decorative Elements -->
                        <div class="absolute -top-8 -right-8 w-24 h-24">
                            <svg viewBox="0 0 100 100" class="text-[#FFDAC1] w-full h-full">
                                <path d="M50 0 L100 50 L50 100 L0 50 Z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="absolute -bottom-4 -left-4 w-32 h-32">
                            <svg viewBox="0 0 100 100" class="text-[#FFDAC1] opacity-50 w-full h-full">
                                <circle cx="50" cy="50" r="50" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white p-8 rounded-lg shadow-sm">
                        <div class="w-12 h-12 bg-[#FFDAC1] rounded-full flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-4">Premium Quality</h3>
                        <p class="text-gray-600">We source only the finest ingredients and partner with skilled artisans to create exceptional snacks.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white p-8 rounded-lg shadow-sm">
                        <div class="w-12 h-12 bg-[#FFDAC1] rounded-full flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-4">Fresh Daily</h3>
                        <p class="text-gray-600">Every snack is freshly made and delivered to ensure the best taste and quality in every bite.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white p-8 rounded-lg shadow-sm">
                        <div class="w-12 h-12 bg-[#FFDAC1] rounded-full flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2M6 7l-3-1m3 1l3 9a5.002 5.002 0 006.001 0M18 7l-3 9m3-9l3 1"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-4">Customizable</h3>
                        <p class="text-gray-600">Create your perfect snack box with our wide selection of treats tailored to your taste.</p>
                    </div>
                </div>
            </div>
        </section>
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
                            <li><a href="index.php" class="hover:text-gray-600">Home</a></li>
                            <li><a href="snacks.php" class="hover:text-gray-600">Snacks</a></li>
                            <li><a href="subscription.php" class="hover:text-gray-600">Subscription</a></li>
                            <li><a href="aboutuspage.php" class="hover:text-gray-600">About Us</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-4">Contact Us</h3>
                        <ul class="space-y-2 text-gray-800">
                            <li>Location: 123 Flavor Street, Colombo, Sri Lanka</li>
                            <li>Call Us: +94 777 890</li>
                            <li>Email: hello@tastebites.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-orange-200 text-sm text-gray-800">
                &copy; 2024 TasteBites. All Rights Reserved.
            </div>
        </div>
    </footer>
</body>
</html>