<?php

date_default_timezone_set("Asia/Manila");

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Selected Date Range
|--------------------------------------------------------------------------
*/

$today = date("Y-m-d");

$startDate = $today;
$endDate = $today;


if (
    isset($_GET["start_date"]) &&
    isset($_GET["end_date"])
) {

    $requestedStartDate = $_GET["start_date"];
    $requestedEndDate = $_GET["end_date"];


    /*
    |--------------------------------------------------------------------------
    | Validate Start Date
    |--------------------------------------------------------------------------
    */

    $validStartDate = false;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedStartDate)) {

        $parts = explode("-", $requestedStartDate);

        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];

        if (checkdate($month, $day, $year)) {
            $validStartDate = true;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate End Date
    |--------------------------------------------------------------------------
    */

    $validEndDate = false;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedEndDate)) {

        $parts = explode("-", $requestedEndDate);

        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];

        if (checkdate($month, $day, $year)) {
            $validEndDate = true;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Use Valid Dates
    |--------------------------------------------------------------------------
    */

    if ($validStartDate && $validEndDate) {

        $startDate = $requestedStartDate;
        $endDate = $requestedEndDate;


        /*
        |--------------------------------------------------------------------------
        | Automatically Correct Reversed Dates
        |--------------------------------------------------------------------------
        */

        if ($startDate > $endDate) {

            $temporaryDate = $startDate;

            $startDate = $endDate;
            $endDate = $temporaryDate;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Sales Summary
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders
    WHERE order_status = 'COMPLETED'
    AND DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$summary = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Total Items Sold
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM order_items oi
    INNER JOIN orders o
        ON oi.order_id = o.order_id
    WHERE o.order_status = 'COMPLETED'
    AND DATE(o.created_at) BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$itemsResult = $stmt->fetch(PDO::FETCH_ASSOC);


$totalOrders = (int) $summary["total_orders"];
$totalSales = (float) $summary["total_sales"];
$totalItems = (int) $itemsResult["total_items"];

/*
|--------------------------------------------------------------------------
| Best-Selling Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
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
    ORDER BY quantity_sold DESC
");

$stmt->execute([
    $startDate,
    $endDate
]);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Custom Date-Range Sales Report</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <link rel="manifest" href="../manifest.json">

</head>

<body>

    <main class="container">

        <header class="page-header">

            <h1>Lorie's Food Hub POS</h1>

            <h2>Custom Date-Range Sales Report</h2>

            <div class="nav-links">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    ← Back to Dashboard
                </a>

            </div>

        </header>


    <!-- Date Range Selection -->

    <section class="section">

    <form method="GET">

        <div class="form-group">

            <label for="start_date">
                Start Date:
            </label>

            <input
                type="date"
                id="start_date"
                name="start_date"
                value="<?= htmlspecialchars($startDate) ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="end_date">
                End Date:
            </label>

            <input
                type="date"
                id="end_date"
                name="end_date"
                value="<?= htmlspecialchars($endDate) ?>"
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

    <hr>


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


    <section class="section">

    <h3>Sales Summary</h3>

    <div class="report-summary">

        <div class="summary-item">

            <span class="summary-label">
                Completed Orders
            </span>

            <strong class="summary-value">
                <?= $totalOrders ?>
            </strong>

        </div>

        <div class="summary-item">

            <span class="summary-label">
                Items Sold
            </span>

            <strong class="summary-value">
                <?= $totalItems ?>
            </strong>

        </div>

        <div class="summary-item">

            <span class="summary-label">
                Total Sales
            </span>

            <strong class="summary-value">
                ₱<?= number_format(
                    $totalSales,
                    2
                ) ?>
            </strong>

        </div>

    </div>

</section>

<section class="section">

    <h3>Best-Selling Products</h3>

    <?php if (count($products) > 0): ?>

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

                    <?php foreach ($products as $product): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $product[
                                        "product_name_snapshot"
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= (int)
                                    $product["quantity_sold"] ?>
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

    <?php else: ?>

        <p>
            No completed sales for this date range.
        </p>

    <?php endif; ?>

</section>

<script src="../assets/js/app.js"></script>

</body>

</html>