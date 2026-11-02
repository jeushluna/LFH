<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in.
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$error = "";
$product = null;

// Get product ID from URL.
$product_id = $_GET["id"] ?? "";

if (!is_numeric($product_id)) {
    die("Invalid product ID.");
}

// Get the product.
$sql = "SELECT
            product_id,
            product_name,
            price,
            category_id
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

// Handle update.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $price = $_POST["price"] ?? "";
    $category_id = $_POST["category_id"] ?? "";

    if (
        $product_name === "" ||
        $price === "" ||
        $category_id === ""
    ) {

        $error = "Please complete all fields.";

    } elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    } else {

        $updateSql = "UPDATE products
                      SET
                          product_name = :product_name,
                          price = :price,
                          category_id = :category_id
                      WHERE product_id = :product_id";

        $updateStmt = $pdo->prepare($updateSql);

        $updateStmt->execute([
            "product_name" => $product_name,
            "price" => $price,
            "category_id" => $category_id,
            "product_id" => $product_id
        ]);

        header("Location: products.php");
        exit;
    }
}

// Get categories.
$categorySql = "SELECT category_id, category_name
                FROM categories
                ORDER BY category_id ASC";

$categoryStmt = $pdo->query($categorySql);

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Product - Lorie's Food Hub POS</title>

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
                Edit Product
            </h2>

            <div class="nav-links">

                <a
                    href="products.php"
                    class="btn btn-secondary"
                >
                    ← Back to Products
                </a>

            </div>

        </header>


        <!-- Edit Product Form -->

        <section class="section">

            <h2>Product Information</h2>


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
                        value="<?= htmlspecialchars($product["product_name"]) ?>"
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
                        value="<?= htmlspecialchars($product["price"]) ?>"
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

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category["category_id"] ?>"
                                <?= $category["category_id"] == $product["category_id"] ? "selected" : "" ?>
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
                    Save Changes
                </button>

            </form>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

</body>

</html>