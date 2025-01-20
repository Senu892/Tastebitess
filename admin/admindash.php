<?php
session_start();
include_once '../dbconnection.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin-login.php');
    exit();
}

// Fetch all products
$products_sql = "SELECT * FROM products";
$products_result = $conn->query($products_sql);

// Fetch all snackboxes
$snackboxes_sql = "SELECT * FROM snackboxes";
$snackboxes_result = $conn->query($snackboxes_sql);

// Fetch all orders
$orders_sql = "SELECT * FROM orders";
$orders_result = $conn->query($orders_sql);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['order_status'])) {
    $order_id = intval($_POST['order_id']);
    $order_status = $_POST['order_status'];
    
    $update_sql = "UPDATE Orders SET order_status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("si", $order_status, $order_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Order status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update order status.";
        }
        $stmt->close();
    }
    header("Location: admindash.php");
    exit();
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product_id'])) {
    $edit_id = intval($_POST['edit_product_id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = floatval($_POST['product_price']);
    $product_quantity = intval($_POST['product_quantity']);
    $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);

    $update_sql = "UPDATE products SET 
                   product_name = ?, 
                   product_price = ?, 
                   product_quantity = ?, 
                   product_image = ? 
                   WHERE id = ?";
                   
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("sdisi", $product_name, $product_price, $product_quantity, $product_image, $edit_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Statement preparation failed: " . $conn->error;
    }
    header("Location: admindash.php");
    exit();
}

// Handle snackbox update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_snackbox_id'])) {
    $edit_id = intval($_POST['edit_snackbox_id']);
    $snackbox_name = mysqli_real_escape_string($conn, $_POST['snackbox_name']);
    $snackbox_size = mysqli_real_escape_string($conn, $_POST['snackbox_size']);
    $snacks_selected = mysqli_real_escape_string($conn, $_POST['snacks_selected']);
    $snackboximage_url = mysqli_real_escape_string($conn, $_POST['snackboximage_url']);

    // Calculate total price based on selected snacks
    $total_price = 0;
    if (!empty($snacks_selected)) {
        $snack_ids = explode(',', $snacks_selected);
        foreach ($snack_ids as $snack_id) {
            $price_query = "SELECT product_price FROM products WHERE id = ?";
            $price_stmt = $conn->prepare($price_query);
            if ($price_stmt) {
                $price_stmt->bind_param("i", $snack_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                if ($price_result && $price_row = $price_result->fetch_assoc()) {
                    $total_price += $price_row['product_price'];
                }
                $price_stmt->close();
            }
        }
    }

    $update_sql = "UPDATE snackboxes SET 
                   snackbox_name = ?, 
                   snackbox_size = ?, 
                   snacks_selected = ?, 
                   snackbox_price = ?, 
                   snackboximage_url = ? 
                   WHERE id = ?";

    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("sssdsi", $snackbox_name, $snackbox_size, $snacks_selected, $total_price, $snackboximage_url, $edit_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Snackbox updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update snackbox: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Statement preparation failed: " . $conn->error;
    }
    header("Location: admindash.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete_product_id'])) {
    $delete_id = intval($_GET['delete_product_id']);
    
    // First check if the product exists
    $check_sql = "SELECT id FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $delete_sql = "DELETE FROM products WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            if ($stmt) {
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Product deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete product: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = "Product not found!";
        }
        $check_stmt->close();
    }
    header("Location: admindash.php");
    exit();
}

// Handle snackbox deletion
if (isset($_GET['delete_snackbox_id'])) {
    $delete_id = intval($_GET['delete_snackbox_id']);
    
    // First check if the snackbox exists
    $check_sql = "SELECT id FROM snackboxes WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $delete_sql = "DELETE FROM snackboxes WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            if ($stmt) {
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Snackbox deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete snackbox: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = "Snackbox not found!";
        }
        $check_stmt->close();
    }
    header("Location: admindash.php");
    exit();
}

        // Fetch all products for snackbox selection
        $all_products_sql = "SELECT id, product_name, product_price FROM products";
        $all_products_result = $conn->query($all_products_sql);
        $all_products = [];
        if ($all_products_result && $all_products_result->num_rows > 0) {
            while ($row = $all_products_result->fetch_assoc()) {
                $all_products[] = $row;
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
    <style>
        .snack-list-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 6px;
            cursor: pointer;
        }
        .selected-snacks {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .selected-snack {
            background-color: #007BFF;
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            display: flex;
            align-items: center;
        }
        .selected-snack .remove-btn {
            margin-left: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-start py-8 px-4">
        <div class="bg-white w-full max-w-6xl rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>

            <!-- Display messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div id="successMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg border">
                    <?= htmlspecialchars($_SESSION['message']); ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg border">
                    <?= htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <div class="mb-6 border-b">
                <div class="flex space-x-4">
                    <button onclick="showTab('products')" class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500 focus:outline-none" id="productsTab">
                        Products
                    </button>
                    <button onclick="showTab('snackboxes')" class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500 focus:outline-none" id="snackboxesTab">
                        Snackboxes
                    </button>
                    <button onclick="showTab('orders')" class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500 focus:outline-none" id="ordersTab">
                        Orders
                    </button>
                </div>
            </div>

            <!-- Add Buttons -->
            <div class="mb-6 text-right">
                <div id="productsAddButton">
                    <a href="add-products.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        Add New Product
                    </a>
                </div>
                <div id="snackboxesAddButton" class="hidden">
                    <a href="snackbox.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        Add New Snackbox
                    </a>
                </div>
            </div>

            <!-- Products Table -->
            <div id="productsTable" class="overflow-x-auto">
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
                        <?php if ($products_result->num_rows > 0): ?>
                            <?php while ($row = $products_result->fetch_assoc()): ?>
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
                                            onclick="openProductEditModal(<?= htmlspecialchars(json_encode($row)); ?>)" 
                                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                            Edit
                                        </button>
                                        <a 
                                            href="admindash.php?delete_product_id=<?= $row['id']; ?>" 
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

            <!-- Orders Table -->
            <div id="ordersTable" class="overflow-x-auto hidden">
                <table class="min-w-full bg-white border border-gray-300 rounded-lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Order ID</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Customer ID</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Type</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Details</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Payment</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Total</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <?php while ($row = $orders_result->fetch_assoc()): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm text-gray-800">#<?= $row['order_id']; ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['user_id']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['order_type']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800">
                                        <?php if ($row['order_type'] === 'Customized'): ?>
                                            Snackbox (<?= htmlspecialchars($row['snackbox_size']); ?>)
                                        <?php else: ?>
                                            Product #<?= $row['product_id']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['payment_type']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800">
                                        $<?= number_format($row['product_price'] * $row['product_quantity'], 2); ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-800">
                                        <?= date('Y-m-d H:i', strtotime($row['order_date'])); ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            <?php
                                            switch ($row['order_status']) {
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'confirmed':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                            }
                                            ?>">
                                            <?= ucfirst(htmlspecialchars($row['order_status'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-center">
                                        <button 
                                            onclick="openStatusModal(<?= $row['order_id']; ?>, '<?= $row['order_status']; ?>')"
                                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded-lg text-sm">
                                            Update Status
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="py-4 px-4 text-center text-sm text-gray-500">
                                    No orders available.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Snackboxes Table -->
            <div id="snackboxesTable" class="overflow-x-auto hidden">
                <table class="min-w-full bg-white border border-gray-300 rounded-lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">#</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snackbox Name</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Size</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Price</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Image</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($snackboxes_result->num_rows > 0): ?>
                            <?php while ($row = $snackboxes_result->fetch_assoc()): ?>
                                <tr class="border-t">
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= $row['id']; ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['snackbox_name']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['snackbox_size']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800">$<?= number_format($row['snackbox_price'], 2); ?></td>
                                    <td class="py-3 px-4 text-sm">
                                        <img src="<?= htmlspecialchars($row['snackboximage_url']); ?>" alt="Snackbox Image" class="w-16 h-16 object-cover rounded-lg">
                                        </td>
                                    <td class="py-3 px-4 text-sm text-center">
                                        <button 
                                            onclick="openSnackboxEditModal(<?= htmlspecialchars(json_encode($row)); ?>)" 
                                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                            Edit
                                        </button>
                                        <a 
                                            href="admindash.php?delete_snackbox_id=<?= $row['id']; ?>" 
                                            onclick="return confirm('Are you sure you want to delete this snackbox?');"
                                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-4 px-4 text-center text-sm text-gray-500">
                                    No snackboxes available.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Product Edit Modal -->
    <div id="productEditModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Product</h2>
            <form action="admindash.php" method="POST" id="productEditForm">
                <input type="hidden" name="edit_product_id" id="edit_product_id">
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
                    <label for="product_image" class="block text-sm font-medium text-gray-700">Product Image URL</label>
                    <input type="url" name="product_image" id="product_image" class="w-full border border-gray-300 rounded-lg p-2">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeProductEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Snackbox Edit Modal -->
<div id="snackboxEditModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full max-h-[90vh] flex flex-col">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Snackbox</h2>
        <form action="admindash.php" method="POST" id="snackboxEditForm" class="flex flex-col h-full overflow-hidden">
            <div class="space-y-4 flex-grow overflow-y-auto pr-2">
                <input type="hidden" name="edit_snackbox_id" id="edit_snackbox_id">
                <div class="mb-4">
                    <label for="snackbox_name" class="block text-sm font-medium text-gray-700">Snackbox Name</label>
                    <input type="text" name="snackbox_name" id="snackbox_name" class="w-full border border-gray-300 rounded-lg p-2">
                </div>
                <div class="mb-4">
                    <label for="snackbox_size" class="block text-sm font-medium text-gray-700">Snackbox Size</label>
                    <select name="snackbox_size" id="snackbox_size" class="w-full border border-gray-300 rounded-lg p-2" onchange="updateMaxSnacks()">
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="snackboximage_url" class="block text-sm font-medium text-gray-700">Snackbox Image URL</label>
                    <input type="url" name="snackboximage_url" id="snackboximage_url" class="w-full border border-gray-300 rounded-lg p-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Select Snacks</label>
                    <div id="available_snacks" class="space-y-2 mt-2">
                        <?php foreach ($all_products as $product): ?>
                            <div class="snack-list-item" data-snack-id="<?= $product['id'] ?>" data-snack-name="<?= $product['product_name'] ?>" data-snack-price="<?= $product['product_price'] ?>">
                                <span><?= htmlspecialchars($product['product_name']) ?> ($<?= htmlspecialchars($product['product_price']) ?>)</span>
                                <button type="button" class="add-snack-btn text-green-500">+ Add</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Selected Snacks</label>
                    <div id="edit_selected_snacks" class="selected-snacks"></div>
                    <input type="hidden" name="snacks_selected" id="edit_snacks_selected">
                </div>
            </div>
            <div class="flex justify-end pt-4 mt-auto border-t">
                <button type="button" onclick="closeSnackboxEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

    <!-- Order Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Update Order Status</h2>
            <form action="admindash.php" method="POST" id="statusForm">
                <input type="hidden" name="order_id" id="modal_order_id">
                <div class="mb-4">
                    <label for="order_status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="order_status" id="order_status" class="w-full border border-gray-300 rounded-lg p-2">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeStatusModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Update</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        let maxSnacks = 5;
        let totalPrice = 0;

        function showTab(tabName) {
            // Hide all tables and add buttons
            document.getElementById('productsTable').classList.add('hidden');
            document.getElementById('snackboxesTable').classList.add('hidden');
            document.getElementById('ordersTable').classList.add('hidden');
            document.getElementById('productsAddButton').classList.add('hidden');
            document.getElementById('snackboxesAddButton').classList.add('hidden');

            // Show selected table and add button
            document.getElementById(tabName + 'Table').classList.remove('hidden');
            if (tabName !== 'orders') {
                document.getElementById(tabName + 'AddButton').classList.remove('hidden');
            }

            // Update tab styles
            document.getElementById('productsTab').classList.remove('border-blue-500');
            document.getElementById('snackboxesTab').classList.remove('border-blue-500');
            document.getElementById('ordersTab').classList.remove('border-blue-500');
            document.getElementById(tabName + 'Tab').classList.add('border-blue-500');
        }

        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('order_status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function openProductEditModal(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('product_name').value = product.product_name;
            document.getElementById('product_price').value = product.product_price;
            document.getElementById('product_quantity').value = product.product_quantity;
            document.getElementById('product_image').value = product.product_image;
            document.getElementById('productEditModal').classList.remove('hidden');
        }

        function closeProductEditModal() {
            document.getElementById('productEditModal').classList.add('hidden');
        }

        function openSnackboxEditModal(snackbox) {
            document.getElementById('edit_snackbox_id').value = snackbox.id;
            document.getElementById('snackbox_name').value = snackbox.snackbox_name;
            document.getElementById('snackbox_size').value = snackbox.snackbox_size;
            document.getElementById('snackboximage_url').value = snackbox.snackboximage_url;
            
            // Clear and populate selected snacks
            const selectedSnacksContainer = document.getElementById('edit_selected_snacks');
            selectedSnacksContainer.innerHTML = '';
            document.getElementById('edit_snacks_selected').value = snackbox.snacks_selected;
            
            // Update maxSnacks based on size
            updateMaxSnacks();
            
            // Populate selected snacks
            if (snackbox.snacks_selected) {
                const selectedIds = snackbox.snacks_selected.split(',');
                selectedIds.forEach(id => {
                    const snackItem = document.querySelector(`.snack-list-item[data-snack-id="${id}"]`);
                    if (snackItem) {
                        addSnackToSelection(snackItem, 'edit_');
                    }
                });
            }

            document.getElementById('snackboxEditModal').classList.remove('hidden');
        }

        function closeSnackboxEditModal() {
            document.getElementById('snackboxEditModal').classList.add('hidden');
        }

        function updateMaxSnacks() {
            const size = document.getElementById('snackbox_size').value;
            maxSnacks = size === 'small' ? 5 : size === 'medium' ? 7 : 9;
        }

        function addSnackToSelection(snackItem, prefix = '') {
            const selectedSnacksContainer = document.getElementById(prefix + 'selected_snacks');
            const snackId = snackItem.getAttribute('data-snack-id');
            const snackName = snackItem.getAttribute('data-snack-name');
            const snackPrice = parseFloat(snackItem.getAttribute('data-snack-price'));

            if (selectedSnacksContainer.children.length < maxSnacks) {
                const snackElement = document.createElement('div');
                snackElement.classList.add('selected-snack');
                snackElement.innerHTML = `${snackName} ($${snackPrice}) <span class="remove-btn">X</span>`;
                snackElement.dataset.snackId = snackId;
                selectedSnacksContainer.appendChild(snackElement);

                let selectedSnacks = document.getElementById(prefix + 'snacks_selected').value;
                selectedSnacks = selectedSnacks ? selectedSnacks.split(',') : [];
                if (!selectedSnacks.includes(snackId)) {
                    selectedSnacks.push(snackId);
                }
                document.getElementById(prefix + 'snacks_selected').value = selectedSnacks.join(',');

                snackElement.querySelector('.remove-btn').addEventListener('click', function() {
                    snackElement.remove();
                    let updatedSnacks = document.getElementById(prefix + 'snacks_selected').value.split(',');
                    updatedSnacks = updatedSnacks.filter(id => id !== snackId);
                    document.getElementById(prefix + 'snacks_selected').value = updatedSnacks.join(',');
                });
            } else {
                alert(`You can select a maximum of ${maxSnacks} snacks for this size.`);
            }
        }

        // Initialize event listeners for add snack buttons
        document.querySelectorAll('.add-snack-btn').forEach(button => {
            button.addEventListener('click', function() {
                const snackItem = this.closest('.snack-list-item');
                addSnackToSelection(snackItem, document.getElementById('snackboxEditModal').classList.contains('hidden') ? '' : 'edit_');
            });
        });
        // Show products tab by default
        document.addEventListener('DOMContentLoaded', () => {
                    showTab('products');
                });

        // Auto-hide success message
        window.onload = function() {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000);
            }
        }
    </script>
</body>
</html>