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
// GET ORDER HISTORY
// ==========================================

$stmt = $pdo->prepare(
    "SELECT
        order_id,
        order_reference,
        total_amount,
        order_status,
        created_at
     FROM orders
     ORDER BY created_at DESC"
);

$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


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

    <title>Order History - Lorie's Food Hub POS</title>

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
                Order History
            </h2>

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

            <h3>Orders</h3>

            <?php if (count($orders) === 0): ?>

                <p>No orders have been recorded yet.</p>

            <?php else: ?>

                <div class="table-container">

                    <table>

            <thead>

    <tr>
        <th>Order Reference</th>
        <th>Date and Time</th>
        <th>Total Amount</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

</thead>

            <tbody>

                <?php foreach ($orders as $order): ?>

                    <tr>

                        <td>
                            <?= escape($order["order_reference"]) ?>
                        </td>

                        <td>
    <?= date(
        "F j, Y, g:i A",
        strtotime($order["created_at"])
    ) ?>
</td>

                        <td>
                            ₱<?= number_format(
                                (float) $order["total_amount"],
                                2
                            ) ?>
                        </td>

                        <td>

    <?php if ($order["order_status"] === "COMPLETED"): ?>

        <span class="status-badge status-completed">
            Completed
        </span>

    <?php elseif ($order["order_status"] === "CANCELLED"): ?>

        <span class="status-badge status-cancelled">
            Cancelled
        </span>

    <?php endif; ?>

</td>

                        <td>

    <a
    href="order_details.php?id=<?= (int) $order["order_id"] ?>"
    class="btn btn-secondary"
>
    View Details
</a>

</td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

                            </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

</body>

</html>