<?php

include_once 'dbconnection.php';
include_once 'products.php';

header('Content-Type: application/json');


$requestURI = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];


$requestPath = parse_url($requestURI, PHP_URL_PATH);
$requestURL = trim(str_replace($scriptName, '', $requestPath), '/');


if ($requestURL === 'get-all-products') {
    header('Content-Type: application/json; charset=utf-8');

    $response = GetAllProducts();
    echo json_encode([
        "status" => "success",
        "data" => $response
    ]);
} elseif ($requestURL === 'get-product-by-id') {
    if (isset($_GET['id'])) {
        
        $productId = intval($_GET['id']);

        
        $response = GetProductById($productId);

        if ($response) {
            echo json_encode([
                "status" => "success",
                "data" => $response
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Product not found"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Product ID is required"
        ]);
    }
} else {
    
    echo json_encode([
        "status" => "error",
        "message" => "Invalid endpoint"
    ]);
}
?>
