<?php

session_start();

require_once "../config/database.php";


// ==========================================
// AUTHENTICATION
// ==========================================

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    die("You are not logged in.");

}


// ==========================================
// GET ORDER ID
// ==========================================

if (
    !isset($_GET["id"]) ||
    !ctype_digit($_GET["id"])
) {

    die("Invalid order ID.");

}

$orderId = (int) $_GET["id"];


// ==========================================
// CHECK ORDER
// ==========================================

$stmt = $pdo->prepare(
    "SELECT
        order_id,
        order_reference,
        order_status
     FROM orders
     WHERE order_id = ?"
);

$stmt->execute([
    $orderId
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);


// ==========================================
// CHECK IF ORDER EXISTS
// ==========================================

if (!$order) {

    die("Order not found.");

}


// ==========================================
// CHECK ORDER STATUS
// ==========================================

if ($order["order_status"] !== "COMPLETED") {

    die(
        "Only completed orders can be cancelled."
    );

}


// ==========================================
// CANCEL ORDER
// ==========================================

$updateStmt = $pdo->prepare(
    "UPDATE orders
     SET order_status = 'CANCELLED'
     WHERE order_id = ?"
);

$updateStmt->execute([
    $orderId
]);


// ==========================================
// REDIRECT TO ORDER DETAILS
// ==========================================

header(
    "Location: order_details.php?id=" .
    $orderId
);

exit;

?>