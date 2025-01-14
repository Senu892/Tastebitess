<?php
include_once '../dbconnection.php'; // Include your database connection file

$message = ""; // Initialize message variable
$products = []; // Array to hold available products (snacks)

// Query to fetch all available products (snacks)
$sql = "SELECT id, product_name, product_price FROM products"; // Updated column name
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row; // Add each product to the products array
    }
}

// Handle form submission to add a snackbox
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $snackbox_name = isset($_POST['snackbox_name']) ? trim($_POST['snackbox_name']) : '';
    $snackbox_size = isset($_POST['snackbox_size']) ? $_POST['snackbox_size'] : '';
    $snacks_selected = isset($_POST['snacks_selected']) ? $_POST['snacks_selected'] : '';

    // Validate the input
    if (empty($snackbox_name) || empty($snackbox_size) || empty($snacks_selected)) {
        $message = "All fields are required, and you must select snacks.";
    } else {
        // Calculate the total price of the selected snacks
        $snack_ids = explode(',', $snacks_selected);
        $total_price = 0;

        foreach ($snack_ids as $snack_id) {
            $price_query = "SELECT product_price FROM products WHERE id = ?";
            $price_stmt = $conn->prepare($price_query);
            $price_stmt->bind_param("i", $snack_id);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result();

            if ($price_result->num_rows > 0) {
                $price_row = $price_result->fetch_assoc();
                $total_price += $price_row['product_price'];
            }

            $price_stmt->close();
        }

        // Prepare the SQL query to insert the new snackbox
        $sql = "INSERT INTO snackboxes (snackbox_name, snackbox_size, snacks_selected, snackbox_price) 
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind parameters for the statement
            $stmt->bind_param("sssd", $snackbox_name, $snackbox_size, $snacks_selected, $total_price);

            if ($stmt->execute()) {
                // Success message
                $message = "Snackbox added successfully!";
                // Set a session variable to keep the message after redirect
                session_start();
                $_SESSION['message'] = $message;
                // Redirect to prevent re-submission
                header("Location: snackbox.php");
                exit();
            } else {
                $message = "Failed to add snackbox: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Failed to prepare the statement: " . $conn->error;
        }
    }
}

// Retrieve the message from session if set
session_start();
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the session message after showing it
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Snackbox</title>
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
        .snack-list-item:hover {
            background-color: #f0f0f0;
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
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-lg w-full">
            <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">Add New Snackbox</h1>

            <!-- Display message if set -->
            <?php if (!empty($message)): ?>
                <div id="message-box" class="mb-4 p-4 <?= strpos($message, 'success') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded-lg border">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Snackbox Form -->
            <form action="snackbox.php" method="POST" class="space-y-6">
                <div>
                    <label for="snackbox_name" class="block text-sm font-medium text-gray-700">Snackbox Name</label>
                    <input type="text" id="snackbox_name" name="snackbox_name" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label for="snackbox_size" class="block text-sm font-medium text-gray-700">Snackbox Size</label>
                    <select id="snackbox_size" name="snackbox_size" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" onchange="updateSnackFields()" required>
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>

                <div>
                    <label for="snacks_selected" class="block text-sm font-medium text-gray-700">Select Snacks</label>
                    <div id="available_snacks" class="space-y-2 mt-2">
                        <?php foreach ($products as $product): ?>
                            <div class="snack-list-item" data-snack-id="<?= $product['id'] ?>" data-snack-name="<?= $product['product_name'] ?>" data-snack-price="<?= $product['product_price'] ?>">
                                <span><?= htmlspecialchars($product['product_name']) ?> ($<?= htmlspecialchars($product['product_price']) ?>)</span>
                                <button type="button" class="add-snack-btn text-green-500">+ Add</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Selected Snacks</label>
                    <div id="selected_snacks" class="selected-snacks mb-4"></div>
                    <input type="hidden" name="snacks_selected" id="snacks_selected">
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Total Price</label>
                    <div id="total_price" class="text-lg font-bold text-green-600">$0.00</div>
                </div>

                <div class="text-center">
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        Add Snackbox
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let maxSnacks = 0;
        let totalPrice = 0;

        // Function to dynamically update snack selection fields based on selected size
        function updateSnackFields() {
            const size = document.getElementById('snackbox_size').value;

            // Set the max snacks allowed based on size
            if (size === 'small') {
                maxSnacks = 5;
            } else if (size === 'medium') {
                maxSnacks = 7;
            } else if (size === 'large') {
                maxSnacks = 9;
            }

            // Reset the selected snacks and input field
            document.getElementById('selected_snacks').innerHTML = '';
            document.getElementById('snacks_selected').value = '';
            document.querySelectorAll('.add-snack-btn').forEach(button => {
                button.disabled = false;
            });

            // Reset the total price
            totalPrice = 0;
            updateTotalPrice();
        }

        // Add snack to the selected snacks list
        document.querySelectorAll('.add-snack-btn').forEach(button => {
            button.addEventListener('click', function() {
                const selectedSnacksContainer = document.getElementById('selected_snacks');
                if (selectedSnacksContainer.children.length < maxSnacks) {
                    const snackId = this.closest('.snack-list-item').getAttribute('data-snack-id');
                    const snackName = this.closest('.snack-list-item').getAttribute('data-snack-name');
                    const snackPrice = parseFloat(this.closest('.snack-list-item').getAttribute('data-snack-price'));

                    // Add the snack to the selected snacks display
                    const snackElement = document.createElement('div');
                    snackElement.classList.add('selected-snack');
                    snackElement.innerHTML = `${snackName} ($${snackPrice}) <span class="remove-btn text-white bg-red-500 px-2 py-1 rounded-full">X</span>`;
                    snackElement.dataset.snackId = snackId;
                    snackElement.dataset.snackPrice = snackPrice;
                    selectedSnacksContainer.appendChild(snackElement);

                    // Add the snack ID to the hidden input field
                    let selectedSnacks = document.getElementById('snacks_selected').value;
                    selectedSnacks += selectedSnacks ? `,${snackId}` : snackId;
                    document.getElementById('snacks_selected').value = selectedSnacks;

                    // Update the total price
                    totalPrice += snackPrice;
                    updateTotalPrice();

                    // Disable the add button for this snack
                    this.disabled = true;

                    // Attach event listener to remove button
                    snackElement.querySelector('.remove-btn').addEventListener('click', function() {
                        // Remove the snack from the selected list
                        totalPrice -= snackPrice;
                        updateTotalPrice();

                        snackElement.remove();
                        document.querySelector(`.snack-list-item[data-snack-id="${snackId}"] .add-snack-btn`).disabled = false;

                        // Update the hidden input field
                        let selectedSnacks = document.getElementById('snacks_selected').value.split(',');
                        selectedSnacks = selectedSnacks.filter(id => id !== snackId);
                        document.getElementById('snacks_selected').value = selectedSnacks.join(',');
                    });
                } else {
                    alert(`You can select a maximum of ${maxSnacks} snacks for this size.`);
                }
            });
        });

        // Function to update the displayed total price
        function updateTotalPrice() {
            document.getElementById('total_price').textContent = `$${totalPrice.toFixed(2)}`;
        }

        // Initialize snack fields on page load
        document.addEventListener('DOMContentLoaded', updateSnackFields);
    </script>
</body>
</html>
