<?php

date_default_timezone_set("Asia/Manila");

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
// GET CURRENT DATE
// ==========================================

$today = date("Y-m-d");


// ==========================================
// GET SELECTED DATE RANGE
// ==========================================

$startDate = $today;
$endDate = $today;


// ==========================================
// VALIDATE START DATE
// ==========================================

if (
    isset($_GET["start_date"]) &&
    preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $_GET["start_date"]
    )
) {

    $dateParts = explode("-", $_GET["start_date"]);

    if (
        checkdate(
            (int) $dateParts[1],
            (int) $dateParts[2],
            (int) $dateParts[0]
        )
    ) {

        $startDate = $_GET["start_date"];

    }

}


// ==========================================
// VALIDATE END DATE
// ==========================================

if (
    isset($_GET["end_date"]) &&
    preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $_GET["end_date"]
    )
) {

    $dateParts = explode("-", $_GET["end_date"]);

    if (
        checkdate(
            (int) $dateParts[1],
            (int) $dateParts[2],
            (int) $dateParts[0]
        )
    ) {

        $endDate = $_GET["end_date"];

    }

}


// ==========================================
// MAKE SURE START DATE IS NOT AFTER END DATE
// ==========================================

if ($startDate > $endDate) {

    $temporaryDate = $startDate;

    $startDate = $endDate;

    $endDate = $temporaryDate;

}


// ==========================================
// GET COMPLETED ORDER SUMMARY
// ==========================================

$orderStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_sales
     FROM orders
     WHERE order_status = 'COMPLETED'
     AND DATE(created_at) BETWEEN ? AND ?"
);

$orderStmt->execute([
    $startDate,
    $endDate
]);

$orderSummary =
    $orderStmt->fetch(PDO::FETCH_ASSOC);


// ==========================================
// GET TOTAL ITEMS SOLD
// ==========================================

$itemStmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(oi.quantity), 0) AS total_items
     FROM order_items oi
     INNER JOIN orders o
        ON oi.order_id = o.order_id
     WHERE o.order_status = 'COMPLETED'
     AND DATE(o.created_at) BETWEEN ? AND ?"
);

$itemStmt->execute([
    $startDate,
    $endDate
]);

$itemSummary =
    $itemStmt->fetch(PDO::FETCH_ASSOC);

    // ==========================================
// GET BEST-SELLING PRODUCTS
// ==========================================

$productStmt = $pdo->prepare(
    "SELECT
        oi.product_name_snapshot,
        SUM(oi.quantity) AS quantity_sold,
        SUM(oi.line_total) AS product_sales
     FROM order_items oi
     INNER JOIN orders o
        ON oi.order_id = o.order_id
     WHERE o.order_status = 'COMPLETED'
     AND DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY
        oi.product_id,
        oi.product_name_snapshot
     ORDER BY quantity_sold DESC"
);

$productStmt->execute([
    $startDate,
    $endDate
]);

$bestSellingProducts =
    $productStmt->fetchAll(PDO::FETCH_ASSOC);


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
        Weekly Sales Report - Lorie's Food Hub POS
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <link rel="manifest" href="../manifest.json">

</head>

<body>

    <main class="container">

        <header class="page-header">

            <h1>Lorie's Food Hub POS</h1>

            <h2>Weekly Sales Report</h2>

            <div class="nav-links">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    ← Back to Dashboard
                </a>

            </div>

        </header>


    <section class="section">

    <form method="GET">

        <div class="form-group">

            <label for="start-date">
                Start Date:
            </label>

            <input
                type="date"
                id="start-date"
                name="start_date"
                value="<?= escape($startDate) ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="end-date">
                End Date:
            </label>

            <input
                type="date"
                id="end-date"
                name="end_date"
                value="<?= escape($endDate) ?>"
                required
            >

        </div>

        <button
            type="submit"
            class="btn btn-primary"
        >
            View Report
        </button>

    </form>

</section>


    <section class="section">

    <h3>Report Period</h3>

    <p>
        <?= date(
            "F j, Y",
            strtotime($startDate)
        ) ?>

        -

        <?= date(
            "F j, Y",
            strtotime($endDate)
        ) ?>
    </p>

</section>


    <!-- ==========================================
         SALES SUMMARY
         ========================================== -->

    <section class="section">

    <h3>Sales Summary</h3>

    <div class="report-summary">

        <div class="summary-item">

            <span class="summary-label">
                Completed Orders
            </span>

            <strong class="summary-value">
                <?= escape(
                    $orderSummary["total_orders"]
                ) ?>
            </strong>

        </div>

        <div class="summary-item">

            <span class="summary-label">
                Items Sold
            </span>

            <strong class="summary-value">
                <?= escape(
                    $itemSummary["total_items"]
                ) ?>
            </strong>

        </div>

        <div class="summary-item">

            <span class="summary-label">
                Total Sales
            </span>

            <strong class="summary-value">
                ₱<?= number_format(
                    (float) $orderSummary["total_sales"],
                    2
                ) ?>
            </strong>

        </div>

    </div>

</section>


<!-- ==========================================
     BEST-SELLING PRODUCTS
     ========================================== -->

<section class="section">

    <h3>Best-Selling Products</h3>

    <?php if (count($bestSellingProducts) === 0): ?>

        <p>
            No completed orders recorded
            for this period.
        </p>

    <?php else: ?>

        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Quantity Sold</th>
                        <th>Sales</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $bestSellingProducts
                        as $product
                    ): ?>

                        <tr>

                            <td>
                                <?= escape(
                                    $product[
                                        "product_name_snapshot"
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= escape(
                                    $product["quantity_sold"]
                                ) ?>
                            </td>

                            <td>
                                ₱<?= number_format(
                                    (float)
                                    $product["product_sales"],
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

<script src="../assets/js/app.js"></script>

</body>

</html>