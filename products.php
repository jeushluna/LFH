<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in.
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

// Handle adding a new product.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $price = $_POST["price"] ?? "";
    $category_id = $_POST["category_id"] ?? "";

    if ($product_name === "" || $price === "" || $category_id === "") {

        $error = "Please complete all fields.";

    } elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    } else {

        $sql = "INSERT INTO products
                (category_id, product_name, price)
                VALUES
                (:category_id, :product_name, :price)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            "category_id" => $category_id,
            "product_name" => $product_name,
            "price" => $price
        ]);

        $message = "Product added successfully.";
    }
}

// Get the three fixed categories.
$categorySql = "SELECT category_id, category_name
                FROM categories
                ORDER BY category_id ASC";

$categoryStmt = $pdo->query($categorySql);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products.
$productSql = "SELECT
                    p.product_id,
                    p.product_name,
                    p.price,
                    p.is_available,
                    c.category_name
               FROM products p
               INNER JOIN categories c
                    ON p.category_id = c.category_id
               ORDER BY p.product_id ASC";

$productStmt = $pdo->query($productSql);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products - Lorie's Food Hub POS</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link rel="manifest" href="../manifest.json">

</head>

<body>

    <main class="container">

        <!-- Page Header -->

        <header class="page-header">

            <h1>
                Lorie's Food Hub POS
            </h1>

            <h2>
                Product Management
            </h2>

            <p>
                Welcome,
                <strong>
                    <?= htmlspecialchars($_SESSION["username"]) ?>
                </strong>!
            </p>

            <div class="nav-links">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    ← Back to Dashboard
                </a>

            </div>

        </header>


        <!-- Add Product -->

        <section class="section">

            <h2>Add Product</h2>


            <?php if ($message !== ""): ?>

                <p class="success-message">
                    <?= htmlspecialchars($message) ?>
                </p>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <p class="error-message">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>


            <form method="POST">

                <div class="form-group">

                    <label for="product_name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="price">
                        Price
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category["category_id"] ?>"
                            >
                                <?= htmlspecialchars($category["category_name"]) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Add Product
                </button>

            </form>

        </section>


        <!-- Products List -->

        <section class="section">

            <h2>Products</h2>


            <?php if (count($products) === 0): ?>

                <p>
                    No products have been added yet.
                </p>

            <?php else: ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Product</th>

                                <th>Category</th>

                                <th>Price</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($products as $product): ?>

                                <tr>

                                    <td>
                                        <?= $product["product_id"] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($product["product_name"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($product["category_name"]) ?>
                                    </td>

                                    <td>
                                        ₱<?= number_format($product["price"], 2) ?>
                                    </td>

                                    <td>

                                        <?php if ($product["is_available"]): ?>

                                            <span class="status-badge status-available">
                                                Available
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge status-unavailable">
                                                Unavailable
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="action-links">

                                            <a
                                                href="edit_product.php?id=<?= $product["product_id"] ?>"
                                                class="btn btn-secondary"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                href="toggle_product.php?id=<?= $product["product_id"] ?>"
                                                class="btn btn-secondary"
                                            >
                                                <?php if ($product["is_available"]): ?>

                                                    Disable

                                                <?php else: ?>

                                                    Enable

                                                <?php endif; ?>
                                            </a>

                                        </div>

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