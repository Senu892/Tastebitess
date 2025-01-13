<?php
session_start();
include_once '../dbconnection.php';

// Update order status
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
    header("Location: admin-orders.php");
    exit();
}

// Fetch all orders with user information
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-start py-8 px-4">
        <div class="bg-white w-full max-w-7xl rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Order Management</h1>

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

            <!-- Orders Table -->
            <div class="overflow-x-auto">
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
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm text-gray-800">#<?= $row['order_id']; ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-800"><?= htmlspecialchars($row['user_id'] ?? 'Unknown'); ?></td>
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
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Update Order Status</h2>
            <form action="admin-orders.php" method="POST" id="statusForm">
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
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('order_status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

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