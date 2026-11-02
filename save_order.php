<?php

session_start();

require_once "../config/database.php";


// ==========================================
// AUTHENTICATION
// ==========================================

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);

    exit;
}


// ==========================================
// RESPONSE TYPE
// ==========================================

header("Content-Type: application/json");


// ==========================================
// REQUEST METHOD
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


// ==========================================
// GET JSON DATA
// ==========================================

$input = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid order data."
    ]);

    exit;
}


// ==========================================
// CHECK ITEMS
// ==========================================

if (
    !isset($input["items"]) ||
    !is_array($input["items"]) ||
    count($input["items"]) === 0
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "The order is empty."
    ]);

    exit;
}


// ==========================================
// SERVER-SIDE ORDER VALIDATION
// ==========================================

$validatedItems = [];

$serverTotal = 0;


// Prepare product query.
$productStmt = $pdo->prepare(
    "SELECT
        product_id,
        product_name,
        price,
        is_available
     FROM products
     WHERE product_id = ?"
);


foreach ($input["items"] as $item) {


    // Make sure required fields exist.
    if (
        !isset($item["productId"]) ||
        !isset($item["quantity"])
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid item data."
        ]);

        exit;

    }


    $productId =
        (int) $item["productId"];

    $quantity =
        (int) $item["quantity"];


    // Product ID must be valid.
    if ($productId <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid product ID."
        ]);

        exit;

    }


    // Quantity must be at least 1.
    if ($quantity <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid product quantity."
        ]);

        exit;

    }


    // Get the real product from MySQL.
    $productStmt->execute([
        $productId
    ]);

    $product =
        $productStmt->fetch(PDO::FETCH_ASSOC);


    // Product does not exist.
    if (!$product) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Product ID " .
                $productId .
                " does not exist."
        ]);

        exit;

    }


    // Product is disabled.
    if ((int) $product["is_available"] !== 1) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                $product["product_name"] .
                " is currently unavailable."
        ]);

        exit;

    }


    // Use the REAL price from MySQL.
    $realPrice =
        (float) $product["price"];


    // Calculate line total on the server.
    $lineTotal =
        $realPrice * $quantity;


    // Add to server-side total.
    $serverTotal += $lineTotal;


    // Store validated item.
    $validatedItems[] = [

        "product_id" =>
            $productId,

        "product_name" =>
            $product["product_name"],

        "unit_price" =>
            $realPrice,

        "quantity" =>
            $quantity,

        "line_total" =>
            $lineTotal

    ];

}

// ==========================================
// GENERATE ORDER REFERENCE
// ==========================================

$orderReference =
    "LFH-" .
    date("Ymd-His") .
    "-" .
    strtoupper(
        substr(
            bin2hex(random_bytes(2)),
            0,
            4
        )
    );


// ==========================================
// SAVE ORDER TO DATABASE
// ==========================================

try {

    // Start database transaction.
    $pdo->beginTransaction();


    // ------------------------------------------
    // INSERT INTO ORDERS
    // ------------------------------------------

    $orderStmt = $pdo->prepare(
        "INSERT INTO orders
        (
            order_reference,
            total_amount,
            order_status
        )
        VALUES
        (
            ?,
            ?,
            'COMPLETED'
        )"
    );

    $orderStmt->execute([
        $orderReference,
        $serverTotal
    ]);


    // Get the newly created order ID.
    $orderId =
        $pdo->lastInsertId();


    // ------------------------------------------
    // INSERT ORDER ITEMS
    // ------------------------------------------

    $itemStmt = $pdo->prepare(
        "INSERT INTO order_items
        (
            order_id,
            product_id,
            product_name_snapshot,
            unit_price,
            quantity,
            line_total
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )"
    );


    foreach ($validatedItems as $item) {

        $itemStmt->execute([

            $orderId,

            $item["product_id"],

            $item["product_name"],

            $item["unit_price"],

            $item["quantity"],

            $item["line_total"]

        ]);

    }


    // ------------------------------------------
    // COMMIT TRANSACTION
    // ------------------------------------------

    $pdo->commit();


} catch (Exception $e) {

    // Undo everything if something failed.
    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Failed to save the order."

    ]);

    exit;

}


// ==========================================
// RETURN SUCCESS RESPONSE
// ==========================================

echo json_encode([

    "success" => true,

    "message" =>
        "Order saved successfully.",

    "order_id" =>
        $orderId,

    "order_reference" =>
        $orderReference,

    "items" =>
        $validatedItems,

    "total" =>
        $serverTotal

]);

?>