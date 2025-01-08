<?php

include_once 'dbconnection.php';

function GetAllProducts() {
    global $conn;

    $sql = "SELECT * FROM products";
    $result = mysqli_query($conn, $sql);

    $products = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    } else {
        die("Error executing query: " . mysqli_error($conn));
    }

    return $products;
}

function GetProductById($product_id) {
    global $conn;

    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $product_id); 
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null; 
        }
    } else {
        die("Error preparing statement: " . $conn->error);
    }
}

?>
