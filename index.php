<?php
session_start();

// Initialize session variables with default values
$username = '';
$isLoggedIn = false;

// Check if user is logged in and set variables
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $isLoggedIn = true;
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '';
}

// Include the database connection file
include 'dbconnection.php';

// Fetch snack boxes
$sql = "SELECT id, snackbox_name, snackbox_size, snacks_selected, snackbox_price, snackboximage_url FROM snackboxes";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

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

    <main class="flex-grow">
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 py-4 grid grid-cols-2 gap-8 items-center">
    <div>
        <h1 class="text-4xl font-bold mb-4">
            A World of<br>
            Flavors at <span class="text-[#DC143C]">Your Doorstep!</span>
        </h1>
        <p class="hero-text-color mb-8">
            Discover global flavors with our curated snack boxes. From familiar treats to rare rarities, TasteBites has it all. Explore tastes from across the globe – all perfectly packaged and delivered to your door!
        </p>
        <a href="#slide"><button class="bg-[#DC143C] text-white px-8 py-3 rounded-full">
            Order Now
        </button></a>
    </div>
    <div class="scale-150 transform origin-center"> <!-- Added scaling -->
        <img src="index-hero.png" alt="Snack Box Collection" class="w-full">
    </div>
</section>

        <!-- Customized Snack Boxes -->
        <section id="slide" class="max-w-7xl mx-auto px-4 py-16">
            <div class="text-center mb-12">
                <h2 class="text-[#DC143C] text-2xl font-bold">Customized Snack Boxes</h2>
                <h3 class="text-3xl font-bold mt-2">Find Your Perfect Box</h3>
            </div>
            
            <div id="product-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <a href="snackdescription.php?id=<?php echo $row['id']; ?>">
                                <img 
                                    src="<?php echo htmlspecialchars($row['snackboximage_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($row['snackbox_name']); ?>"
                                    class="w-full h-48 object-cover"
                                >
                                <div class="p-4">
                                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['snackbox_name']); ?></h3>
                                    <p>Size: <?php echo htmlspecialchars($row['snackbox_size']); ?></p>
                                    <p class="text-lg font-bold text-[#DC143C]">$<?php echo htmlspecialchars(number_format($row['snackbox_price'], 2)); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No snack boxes available at the moment.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

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
    <script src="cart.js"></script>

</body>
</html>
<?php $conn->close(); ?>