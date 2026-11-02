<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in.
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$product_id = $_GET["id"] ?? "";

if (!is_numeric($product_id)) {
    die("Invalid product ID.");
}

// Get the current availability.
$sql = "SELECT is_available
        FROM products
        WHERE product_id = :product_id
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "product_id" => $product_id
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

// Reverse the current availability.
$new_status = $product["is_available"] ? 0 : 1;

// Update the product.
$updateSql = "UPDATE products
              SET is_available = :is_available
              WHERE product_id = :product_id";

$updateStmt = $pdo->prepare($updateSql);

$updateStmt->execute([
    "is_available" => $new_status,
    "product_id" => $product_id
]);

header("Location: products.php");
exit;

?>