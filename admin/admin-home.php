<?php
session_start(); // Start the session to store success/error messages

include_once '../dbconnection.php'; // Include your database connection file

// Fetch all products from the database
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $product_name = $_POST['product_name'];
    $product_price = floatval($_POST['product_price']);
    $product_quantity = intval($_POST['product_quantity']);
    $product_image = $_POST['product_image'];

    $update_sql = "UPDATE products SET product_name = ?, product_price = ?, product_quantity = ?, product_image = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("sdssi", $product_name, $product_price, $product_quantity, $product_image, $edit_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product updated successfully!";
            header("Location: admin-home.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update product.";
            header("Location: admin-home.php");
            exit();
        }
        $stmt->close();
    }
}

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product deleted successfully!";
            header("Location: admin-home.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to delete product.";
            header("Location: admin-home.php");
            exit();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-start py-8 px-4">
        <div class="bg-white w-full max-w-6xl rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>

            <!-- Display message if set -->
            <?php if (isset($_SESSION['message'])): ?>
                <div id="successMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg border">
                    <?= htmlspecialchars($_SESSION['message']); ?>
                </div>
                <?php unset($_SESSION['message']); // Clear the message after displaying ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg border">
                    <?= htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); // Clear the error after displaying ?>
            <?php endif; ?>

            <!-- Add Product Button -->
            <div class="mb-6 text-right">
                <a href="add-products.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    Add New Product
                </a>
            </div>

            <!-- Products Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300 rounded-lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">#</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Product Name</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Price</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Quantity</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Image</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-t">
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= $row['id']; ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['product_name']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= number_format($row['product_price'], 2); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= $row['product_quantity']; ?></td>
                                    <td class="py-3 px-4 text-sm">
                                        <img src="<?= htmlspecialchars($row['product_image']); ?>" alt="Product Image" class="w-16 h-16 object-cover rounded-lg">
                                    </td>
                                    <td class="py-3 px-4 text-sm text-center">
                                        <button 
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($row)); ?>)" 
                                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                            Edit
                                        </button>
                                        <a 
                                            href="admin-home.php?delete_id=<?= $row['id']; ?>" 
                                            onclick="return confirm('Are you sure you want to delete this product?');"
                                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-4 px-4 text-center text-sm text-gray-500">
                                    No products available.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Product</h2>
            <form action="admin-home.php" method="POST" id="editForm">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="mb-4">
                    <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="product_name" id="product_name" class="w-full border border-gray-300 rounded-lg p-2">
                </div>
                <div class="mb-4">
                    <label for="product_price" class="block text-sm font-medium text-gray-700">Product Price</label>
                    <input type="number" name="product_price" id="product_price" class="w-full border border-gray-300 rounded-lg p-2" step="0.01">
                </div>
                <div class="mb-4">
                    <label for="product_quantity" class="block text-sm font-medium text-gray-700">Product Quantity</label>
                    <input type="number" name="product_quantity" id="product_quantity" class="w-full border border-gray-300 rounded-lg p-2">
                </div>
                <div class="mb-4">
                    <label for="product_image" class="block text-sm font-medium text-gray-700">Product Image</label>
                    <input type="url" name="product_image" id="product_image" class="w-full border border-gray-300 rounded-lg p-2">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('product_name').value = product.product_name;
            document.getElementById('product_price').value = product.product_price;
            document.getElementById('product_quantity').value = product.product_quantity;
            document.getElementById('product_image').value = product.product_image;

            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Hide success message after 3 seconds
        window.onload = function() {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000); // 3 seconds
            }
        }
    </script>
</body>
</html>
