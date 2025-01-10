<?php
include_once './dbconnection.php';

// Fetch all products from database with prices
$sql = "SELECT id, product_name, product_image, product_price FROM products ORDER BY product_name";
$result = $conn->query($sql);
$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

session_start();
if (!isset($_SESSION['selected_snacks'])) {
    $_SESSION['selected_snacks'] = [];
}

// Create a JSON object of product prices for JavaScript
$productPrices = [];
foreach ($products as $product) {
    $productPrices[$product['id']] = $product['product_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TasteBites - Customize Your Box</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .snack-card {
            background: #F5F5F5;
            border-radius: 1rem;
            transition: transform 0.2s;
        }
        .snack-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-white">
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


<body class="bg-white">
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-[#DC143C] mb-8">Let's Customize</h1>
        
        <!-- Customization Box -->
        <div class="bg-white rounded-lg border">
            <div class="grid grid-cols-12 gap-8 p-6">
                <!-- Left Section: Box Image -->
                <div class="col-span-5">
                    <div class="bg-[#FFF5EE] rounded-lg p-8">
                        <img src="snack.jpg" alt="Snack Box" class="w-full">
                    </div>
                </div>
        <!-- Modified Order Details Section -->
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
                <p class="text-green-600 text-2xl font-bold mb-4">$<span id="total-price">0.00</span></p>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Quantity</label>
                    <input class="w-full border rounded px-3 py-2" type="number" name="quantity" min="1" value="1" id="quantity-input">
                </div>

                <div class="space-y-2 mb-6">
                    <label class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="radio" name="order_type" checked>
                            <span class="ml-2">One-time order</span>
                        </div>
                        <span>$<span id="one-time-price">0.00</span></span>
                    </label>
                    <label class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="radio" name="order_type">
                            <span class="ml-2">Subscribe & Save</span>
                        </div>
                        <span>(Monthly) $<span id="subscription-price">0.00</span></span>
                    </label>
                </div>

                <button class="w-full bg-green-600 text-white py-2 rounded-md mb-3">Buy Now</button>
                <button class="w-full border border-orange-400 text-orange-400 py-2 rounded-md">+ Add to Cart</button>
            </div>
        </div>

        <!-- Right Section: Selected Snacks -->
        <div class="col-span-3">
                    <h3 class="text-orange-500 font-semibold mb-4">Selected Snacks</h3>
                    <div id="selected-snacks" class="space-y-2">
                        <!-- Selected snacks will be added here dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Browse Snacks Section -->
        <div class="mt-16 text-center">
            <h2 class="text-2xl font-bold text-[#DC143C] mb-8">Browse our Snacks</h2>
            <div class="grid grid-cols-5 gap-6">
                <?php foreach ($products as $product): ?>
                <div class="snack-card cursor-pointer"
                     data-id="<?= htmlspecialchars($product['id']) ?>"
                     data-name="<?= htmlspecialchars($product['product_name']) ?>"
                     data-image="<?= htmlspecialchars($product['product_image']) ?>">
                    <img src="<?= htmlspecialchars($product['product_image']) ?>" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                         class="w-full h-40 object-cover rounded-t-lg">
                    <p class="py-3 text-center text-gray-800">
                        <?= htmlspecialchars($product['product_name']) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#FFDAC1] mt-16 py-8">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-2">
            <div>
                <img src="logo.png" alt="TasteBites" class="h-11 mb-4">
                <p>Sweet Every Bite</p>
            </div>
            <div class="grid grid-cols-2">
                <div>
                    <h3 class="font-bold mb-4">Navigate</h3>
                    <ul class="space-y-2">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Snacks</a></li>
                        <li><a href="#">Subscription</a></li>
                        <li><a href="#">About Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold mb-4">Contact US</h3>
                    <ul class="space-y-2">
                        <li>Location: 123 Flavor Street,</li>
                        <li>Colombo,Sri Lanka</li>
                        <li>Call Us: +94777890</li>
                        <li>Email: hello@tastebites.com</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 mt-8 text-sm" >
            © 2024 Taste Bites. All Rights Reserved.
        </div>
    </footer>

    <script>
        // Add product prices to JavaScript
        const productPrices = <?php echo json_encode($productPrices); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const selectedSnacks = new Set();
            const maxSnacks = 8;
            let totalPrice = 0;

            const quantityInput = document.getElementById('quantity-input');
            const totalPriceElement = document.getElementById('total-price');
            const oneTimePriceElement = document.getElementById('one-time-price');
            const subscriptionPriceElement = document.getElementById('subscription-price');

            function updatePriceDisplay() {
                const quantity = parseInt(quantityInput.value) || 1;
                const basePrice = totalPrice;
                const total = (basePrice * quantity).toFixed(2);
                const subscriptionDiscount = 0.9; // 10% discount for subscription

                totalPriceElement.textContent = total;
                oneTimePriceElement.textContent = total;
                subscriptionPriceElement.textContent = (total * subscriptionDiscount).toFixed(2);
            }

            // Add quantity input handler
            quantityInput.addEventListener('input', updatePriceDisplay);

            // Modified addSnackToSelection function
            function addSnackToSelection(snackData) {
                if (selectedSnacks.has(snackData.id)) return;
                if (selectedSnacks.size >= maxSnacks) {
                    alert('You can only select up to 8 snacks!');
                    return;
                }
                
                selectedSnacks.add(snackData.id);
                totalPrice += parseFloat(productPrices[snackData.id]) || 0;
                updatePriceDisplay();

                const selectedSnacksContainer = document.getElementById('selected-snacks');
                
                const snackElement = document.createElement('div');
                snackElement.className = 'flex items-center justify-between bg-gray-50 p-2 rounded';
                snackElement.innerHTML = `
                    <div class="flex items-center">
                        <img src="${snackData.image}" alt="${snackData.name}" class="h-8 w-8 rounded">
                        <span class="ml-2">${snackData.name}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-2">$${productPrices[snackData.id]}</span>
                        <button class="text-gray-400 hover:text-gray-600 remove-snack" data-id="${snackData.id}">×</button>
                    </div>
                `;

                selectedSnacksContainer.appendChild(snackElement);

                // Add remove handler
                snackElement.querySelector('.remove-snack').addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectedSnacks.delete(this.dataset.id);
                    totalPrice -= parseFloat(productPrices[this.dataset.id]) || 0;
                    updatePriceDisplay();
                    snackElement.remove();
                    updateServer(Array.from(selectedSnacks));
                });

                updateServer(Array.from(selectedSnacks));
            }

            // Add click handlers to snack cards
            document.querySelectorAll('.snack-card').forEach(card => {
                card.addEventListener('click', function() {
                    const snackData = {
                        id: this.dataset.id,
                        name: this.dataset.name,
                        image: this.dataset.image
                    };
                    addSnackToSelection(snackData);
                });
            });

            function updateServer(selectedSnacksArray) {
                fetch('update_selection.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        selected_snacks: selectedSnacksArray
                    })
                });
            }
        });
    </script>
</body>
</html>