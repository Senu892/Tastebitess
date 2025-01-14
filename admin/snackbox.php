<?php
include_once '../dbconnection.php';

$message = "";
$products = [];

$sql = "SELECT id, product_name, product_price FROM products";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $snackbox_name = isset($_POST['snackbox_name']) ? trim($_POST['snackbox_name']) : '';
    $snackbox_size = isset($_POST['snackbox_size']) ? $_POST['snackbox_size'] : '';
    $snacks_selected = isset($_POST['snacks_selected']) ? $_POST['snacks_selected'] : '';
    $snackboximage_url = isset($_POST['snackboximage_url']) ? trim($_POST['snackboximage_url']) : ''; 

    if (empty($snackbox_name) || empty($snackbox_size) || empty($snacks_selected) || empty($snackboximage_url)) {
        $message = "All fields are required, and you must select snacks.";
    } else {
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

        $sql = "INSERT INTO snackboxes (snackbox_name, snackbox_size, snacks_selected, snackbox_price, snackboximage_url) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssds", $snackbox_name, $snackbox_size, $snacks_selected, $total_price, $snackboximage_url);

            if ($stmt->execute()) {
                $message = "Snackbox added successfully!";
                session_start();
                $_SESSION['message'] = $message;
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

session_start();
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
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
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 8px;
            display: none;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-lg w-full">
            <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">Add New Snackbox</h1>

            <?php if (!empty($message)): ?>
                <div id="message-box" class="mb-4 p-4 <?= strpos($message, 'success') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded-lg border">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

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
                    <label for="snackboximage_url" class="block text-sm font-medium text-gray-700">Snackbox Image URL</label>
                    <input type="url" id="snackboximage_url" name="snackboximage_url" class="mt-1 p-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" 
                           placeholder="https://example.com/image.jpg" required onchange="previewImage(this)">
                    
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

        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            const imageUrl = input.value;
            
            if (imageUrl) {
                preview.src = imageUrl;
                preview.style.display = 'block';
                
                // Handle load error
                preview.onerror = function() {
                    preview.style.display = 'none';
                    alert('Failed to load image. Please check the URL.');
                };
                
                // Show preview if load successful
                preview.onload = function() {
                    preview.style.display = 'block';
                };
            } else {
                preview.style.display = 'none';
            }
        }

        function updateSnackFields() {
            const size = document.getElementById('snackbox_size').value;

            if (size === 'small') {
                maxSnacks = 5;
            } else if (size === 'medium') {
                maxSnacks = 7;
            } else if (size === 'large') {
                maxSnacks = 9;
            }

            document.getElementById('selected_snacks').innerHTML = '';
            document.getElementById('snacks_selected').value = '';
            document.querySelectorAll('.add-snack-btn').forEach(button => {
                button.disabled = false;
            });

            totalPrice = 0;
            updateTotalPrice();
        }

        document.querySelectorAll('.add-snack-btn').forEach(button => {
            button.addEventListener('click', function() {
                const selectedSnacksContainer = document.getElementById('selected_snacks');
                if (selectedSnacksContainer.children.length < maxSnacks) {
                    const snackId = this.closest('.snack-list-item').getAttribute('data-snack-id');
                    const snackName = this.closest('.snack-list-item').getAttribute('data-snack-name');
                    const snackPrice = parseFloat(this.closest('.snack-list-item').getAttribute('data-snack-price'));

                    const snackElement = document.createElement('div');
                    snackElement.classList.add('selected-snack');
                    snackElement.innerHTML = `${snackName} ($${snackPrice}) <span class="remove-btn text-white bg-red-500 px-2 py-1 rounded-full">X</span>`;
                    snackElement.dataset.snackId = snackId;
                    snackElement.dataset.snackPrice = snackPrice;
                    selectedSnacksContainer.appendChild(snackElement);

                    let selectedSnacks = document.getElementById('snacks_selected').value;
                    selectedSnacks += selectedSnacks ? `,${snackId}` : snackId;
                    document.getElementById('snacks_selected').value = selectedSnacks;

                    totalPrice += snackPrice;
                    updateTotalPrice();

                    this.disabled = true;

                    snackElement.querySelector('.remove-btn').addEventListener('click', function() {
                        totalPrice -= snackPrice;
                        updateTotalPrice();

                        snackElement.remove();
                        document.querySelector(`.snack-list-item[data-snack-id="${snackId}"] .add-snack-btn`).disabled = false;

                        let selectedSnacks = document.getElementById('snacks_selected').value.split(',');
                        selectedSnacks = selectedSnacks.filter(id => id !== snackId);
                        document.getElementById('snacks_selected').value = selectedSnacks.join(',');
                    });
                } else {
                    alert(`You can select a maximum of ${maxSnacks} snacks for this size.`);
                }
            });
        });

        function updateTotalPrice() {
            document.getElementById('total_price').textContent = `$${totalPrice.toFixed(2)}`;
        }

        document.addEventListener('DOMContentLoaded', updateSnackFields);
    </script>
</body>
</html>