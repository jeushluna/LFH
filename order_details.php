<?php

session_start();

require_once "../config/database.php";


// ==========================================
// AUTHENTICATION
// ==========================================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");
    exit;

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
// GET ORDER
// ==========================================

$orderStmt = $pdo->prepare(
    "SELECT
        order_id,
        order_reference,
        total_amount,
        order_status,
        created_at
     FROM orders
     WHERE order_id = ?"
);

$orderStmt->execute([
    $orderId
]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);


// ==========================================
// CHECK IF ORDER EXISTS
// ==========================================

if (!$order) {

    die("Order not found.");

}


// ==========================================
// GET ORDER ITEMS
// ==========================================

$itemStmt = $pdo->prepare(
    "SELECT
        order_item_id,
        product_id,
        product_name_snapshot,
        unit_price,
        quantity,
        line_total
     FROM order_items
     WHERE order_id = ?
     ORDER BY order_item_id ASC"
);

$itemStmt->execute([
    $orderId
]);

$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// HELPER FOR SAFE HTML OUTPUT
// ==========================================

function escape($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Order Details - Lorie's Food Hub POS
    </title>

    <link
    rel="stylesheet"
    href="../assets/css/style.css"
>

<link rel="manifest" href="../manifest.json">

</head>

<body>

    <main class="container">

        <header class="page-header">

            <h1>
                Lorie's Food Hub POS
            </h1>

            <h2>
                Order Details
            </h2>

            <div class="nav-links">

                <a
                    href="order_history.php"
                    class="btn btn-secondary"
                >
                    ← Back to Order History
                </a>

            </div>

        </header>


    <!-- ==========================================
     ORDER INFORMATION
     ========================================== -->

<section class="section">

    <h3>
        <?= escape($order["order_reference"]) ?>
    </h3>

    <p>
    <strong>Order Date:</strong>
    <?= date(
        "F j, Y, g:i A",
        strtotime($order["created_at"])
    ) ?>
</p>

    <p>
        <strong>Status:</strong>

        <?php if ($order["order_status"] === "COMPLETED"): ?>

            <span class="status-badge status-completed">
                Completed
            </span>

        <?php elseif ($order["order_status"] === "CANCELLED"): ?>

            <span class="status-badge status-cancelled">
                Cancelled
            </span>

        <?php endif; ?>

    </p>

    <?php if ($order["order_status"] === "COMPLETED"): ?>

        <p>

            <a
                href="cancel_order.php?id=<?= (int) $order["order_id"] ?>"
                class="btn btn-danger"
                onclick="return confirm('Are you sure you want to cancel this order?');"
            >
                Cancel Order
            </a>

        </p>

    <?php endif; ?>

</section>


    <!-- ==========================================
         ORDER ITEMS
         ========================================== -->

    <!-- ==========================================
     ORDER ITEMS
     ========================================== -->

<section class="section">

    <h3>Items</h3>

    <?php if (count($items) === 0): ?>

        <p>No items found for this order.</p>

    <?php else: ?>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Product</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Line Total</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($items as $item): ?>

                        <tr>

                            <td>
                                <?= escape(
                                    $item["product_name_snapshot"]
                                ) ?>
                            </td>

                            <td>
                                ₱<?= number_format(
                                    (float) $item["unit_price"],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= escape(
                                    $item["quantity"]
                                ) ?>
                            </td>

                            <td>
                                ₱<?= number_format(
                                    (float) $item["line_total"],
                                    2
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


    <hr>


    <!-- ==========================================
     TOTAL
     ========================================== -->

<section class="section order-total-section">

    <h3>
        Total:
        ₱<?= number_format(
            (float) $order["total_amount"],
            2
        ) ?>
    </h3>

</section>

</main>

<script src="../assets/js/app.js"></script>

</body>

</html>