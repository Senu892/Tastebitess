<?php
include_once '../dbconnection.php'; // Include your database connection file

$message = ""; // Initialize the message variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission to add a product

    // Validate input fields
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $product_price = isset($_POST['product_price']) ? floatval($_POST['product_price']) : 0;
    $product_quantity = isset($_POST['product_quantity']) ? intval($_POST['product_quantity']) : 0;
    $product_image = isset($_POST['product_image']) ? trim($_POST['product_image']) : '';

    // Check if all required fields are filled
    if (empty($product_name) || $product_price <= 0 || $product_quantity <= 0 || empty($product_image)) {
        $message = "All fields are required, and the price, quantity, and image URL must be valid.";
    } elseif (!filter_var($product_image, FILTER_VALIDATE_URL)) {
        $message = "The image URL is not valid.";
    } else {
        // Insert product details into the database
        $sql = "INSERT INTO products (product_name, product_price, product_quantity, product_image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sdss", $product_name, $product_price, $product_quantity, $product_image);

            if ($stmt->execute()) {
                // On successful insertion, set a success message in a session and redirect
                session_start();
                $_SESSION['message'] = "Product added successfully!";
                header("Location: add-products.php");
                exit; // Prevent further execution
            } else {
                $message = "Failed to add product: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $message = "Failed to prepare the statement: " . $conn->error;
        }
    }
}

// Display success message from session (if available)
session_start();
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying it
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-lg w-full">
            <div class="absolute top-4 left-4">
                <a href="admindash.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    Home
                </a>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">Add New Product</h1>

            <!-- Display message if set -->
            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 <?= strpos($message, 'success') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded-lg border">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Product Addition Form -->
            <form action="add-products.php" method="POST" class="space-y-6">
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" id="product_name" name="product_name" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="product_price" class="block text-sm font-medium text-gray-700">Product Price</label>
                    <input type="number" step="0.01" id="product_price" name="product_price" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="product_quantity" class="block text-sm font-medium text-gray-700">Product Quantity</label>
                    <input type="number" id="product_quantity" name="product_quantity" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="product_image" class="block text-sm font-medium text-gray-700">Product Image URL</label>
                    <input type="url" id="product_image" name="product_image" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="https://example.com/image.jpg" required>
                </div>
                <div class="text-center">
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
