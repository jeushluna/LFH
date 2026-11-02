<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in.
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get all categories.
$categorySql = "SELECT category_id, category_name
                FROM categories
                ORDER BY category_id ASC";

$categoryStmt = $pdo->query($categorySql);

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available products.
$productSql = "SELECT
                    product_id,
                    category_id,
                    product_name,
                    price
               FROM products
               WHERE is_available = 1
               ORDER BY category_id ASC, product_name ASC";

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

    <title>New Order - Lorie's Food Hub POS</title>

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
                New Order
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

    <!-- ========================= -->
    <!-- CATEGORIES -->
    <!-- ========================= -->

    <section class="section">

    <h3>Categories</h3>

    <div id="category-list">

        <?php foreach ($categories as $category): ?>

            <button
                type="button"
                class="category-button"
                data-category-id="<?php
                    echo $category["category_id"];
                ?>"
            >

                <?php
                echo htmlspecialchars(
                    $category["category_name"]
                );
                ?>

            </button>

        <?php endforeach; ?>

    </div>

    </section>


    <!-- ========================= -->
    <!-- PRODUCTS -->
    <!-- ========================= -->

   <section class="section">

    <h3>Products</h3>

    <div id="product-list">

        <?php foreach ($products as $product): ?>

            <button
                type="button"
                class="product-button"
                data-product-id="<?php
                    echo $product["product_id"];
                ?>"
                data-product-name="<?php
                    echo htmlspecialchars(
                        $product["product_name"],
                        ENT_QUOTES
                    );
                ?>"
                data-price="<?php
                    echo $product["price"];
                ?>"
                data-category-id="<?php
                    echo $product["category_id"];
                ?>"
            >

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $product["product_name"]
                    );
                    ?>
                </strong>

                <br>

                ₱<?php
                echo number_format(
                    $product["price"],
                    2
                );
                ?>

            </button>

        <?php endforeach; ?>

        </div>

</section>


    <!-- ========================= -->
    <!-- CURRENT ORDER -->
    <!-- ========================= -->

    <section id="current-order" class="section">

        <h3>Current Order</h3>

        <div id="order-items">

            <p id="empty-order">
                No items added yet.
            </p>

        </div>

        <div id="order-total">
            Total: ₱0.00
        </div>

        <button
            type="button"
            id="clear-order"
        >
            Clear Order
        </button>

        <button
    type="button"
    id="confirm-order"
>
    Confirm Order
</button>

    </div>

</section>

    <!-- ==========================================
     ORDER REVIEW
     ========================================== -->

<section
    id="order-review"
    class="section"
    style="display: none;"
>

    <h3>Confirm Order</h3>

    <div id="review-items"></div>

    <hr>

    <div id="review-total">
        Total: ₱0.00
    </div>

    <br>

    <button
        type="button"
        id="back-to-order"
    >
        ← Back
    </button>

    <button
        type="button"
        id="final-confirm"
    >
        Confirm
    </button>

</section>


<!-- ==========================================
     ORDER COMPLETED
     ========================================== -->

<section
    id="order-completed"
    class="section"
    style="display: none;"
>

    <h3>Order Completed</h3>

    <p>
        Order Reference:
        <strong id="completed-reference"></strong>
    </p>

    <p>
        Total:
        <strong id="completed-total">₱0.00</strong>
    </p>

    <br>

    <button
        type="button"
        id="new-order-button"
    >
        New Order
    </button>

</section>


    <!-- ========================= -->
    <!-- JAVASCRIPT -->
    <!-- ========================= -->

    <script>

        // Stores the products currently in the order.
        const currentOrder = {};


        // Get important elements from the page.
        const productButtons =
            document.querySelectorAll(".product-button");

        const categoryButtons =
            document.querySelectorAll(".category-button");

        const orderItemsContainer =
            document.getElementById("order-items");

        const orderTotal =
            document.getElementById("order-total");

        const clearOrderButton =
            document.getElementById("clear-order");

            const confirmOrderButton =
    document.getElementById("confirm-order");

const currentOrderContainer =
    document.getElementById("current-order");

const orderReview =
    document.getElementById("order-review");

const reviewItems =
    document.getElementById("review-items");

const reviewTotal =
    document.getElementById("review-total");

const backToOrderButton =
    document.getElementById("back-to-order");

const finalConfirmButton =
    document.getElementById("final-confirm");

    const orderCompleted =
    document.getElementById("order-completed");

const completedReference =
    document.getElementById("completed-reference");

const completedTotal =
    document.getElementById("completed-total");

const newOrderButton =
    document.getElementById("new-order-button");

    newOrderButton.addEventListener(
    "click",
    function() {

        // Clear all items from the current order.
        Object.keys(currentOrder).forEach(
            function(productId) {

                delete currentOrder[productId];

            }
        );

        // Refresh the cart display.
        renderOrder();

        // Hide completed section.
        orderCompleted.style.display =
            "none";

        // Show current order section.
        currentOrderContainer.style.display =
            "block";

    }
);

            // ==========================================
// CATEGORY FILTERING
// ==========================================

function showCategory(categoryId) {

    productButtons.forEach(function(button) {

        const productCategoryId =
            button.dataset.categoryId;

        if (productCategoryId === categoryId) {

            button.style.display = "block";

        } else {

            button.style.display = "none";

        }

    });

}


// Add click event to every category button.
categoryButtons.forEach(function(button) {

    button.addEventListener("click", function() {

        const categoryId =
            button.dataset.categoryId;

        // Show products from selected category.
        showCategory(categoryId);


        // Remove active state from all buttons.
        categoryButtons.forEach(function(otherButton) {

            otherButton.classList.remove("active");

        });


        // Mark selected category as active.
        button.classList.add("active");

    });

});


// Show the first category by default.
if (categoryButtons.length > 0) {

    const firstCategory =
        categoryButtons[0];

    firstCategory.classList.add("active");

    showCategory(
        firstCategory.dataset.categoryId
    );

}


        // ==========================================
        // ADD PRODUCT TO ORDER
        // ==========================================

        productButtons.forEach(function(button) {

            button.addEventListener("click", function() {

                const productId =
                    button.dataset.productId;

                const productName =
                    button.dataset.productName;

                const price =
                    parseFloat(button.dataset.price);


                // If product already exists,
                // increase its quantity.
                if (currentOrder[productId]) {

                    currentOrder[productId].quantity++;

                }

                // Otherwise, create a new order item.
                else {

                    currentOrder[productId] = {

                        productId: productId,

                        productName: productName,

                        price: price,

                        quantity: 1

                    };

                }


                // Refresh the order display.
                renderOrder();

            });

        });


        // ==========================================
        // DISPLAY CURRENT ORDER
        // ==========================================

        function renderOrder() {

            orderItemsContainer.innerHTML = "";

            let total = 0;

            const orderItemIds =
                Object.keys(currentOrder);


            // No items in order.
            if (orderItemIds.length === 0) {

                orderItemsContainer.innerHTML =
                    '<p id="empty-order">' +
                    'No items added yet.' +
                    '</p>';

                orderTotal.textContent =
                    "Total: ₱0.00";

                return;

            }


            // Display every item.
            orderItemIds.forEach(function(productId) {

                const item =
                    currentOrder[productId];

                const lineTotal =
                    item.price * item.quantity;

                total += lineTotal;


                const orderItem =
                    document.createElement("div");

                orderItem.className =
                    "order-item";


                orderItem.innerHTML = `

                    <div class="item-info">

                        <strong>
                            ${item.productName}
                        </strong>

                        <br>

                        ₱${item.price.toFixed(2)}
                        each

                    </div>


                    <div class="quantity-controls">

                        <button
                            type="button"
                            class="quantity-button decrease-button"
                        >
                            −
                        </button>

                        <span>
                            ${item.quantity}
                        </span>

                        <button
                            type="button"
                            class="quantity-button increase-button"
                        >
                            +
                        </button>

                    </div>


                    <div>

                        ₱${lineTotal.toFixed(2)}

                    </div>


                    <button
                        type="button"
                        class="remove-button"
                    >
                        ✕
                    </button>

                `;


                // Decrease quantity.
                orderItem
                    .querySelector(".decrease-button")
                    .addEventListener(
                        "click",
                        function() {

                            item.quantity--;

                            if (item.quantity <= 0) {

                                delete currentOrder[
                                    productId
                                ];

                            }

                            renderOrder();

                        }
                    );


                // Increase quantity.
                orderItem
                    .querySelector(".increase-button")
                    .addEventListener(
                        "click",
                        function() {

                            item.quantity++;

                            renderOrder();

                        }
                    );


                // Remove item completely.
                orderItem
                    .querySelector(".remove-button")
                    .addEventListener(
                        "click",
                        function() {

                            delete currentOrder[
                                productId
                            ];

                            renderOrder();

                        }
                    );


                orderItemsContainer.appendChild(
                    orderItem
                );

            });


            // Display total.
            orderTotal.textContent =
                "Total: ₱" + total.toFixed(2);

        }


        // ==========================================
        // CLEAR ORDER
        // ==========================================

        clearOrderButton.addEventListener(
            "click",
            function() {

                // Remove all items.
                for (
                    const productId in currentOrder
                ) {

                    delete currentOrder[productId];

                }

                renderOrder();

            }
        );

        // ==========================================
// ORDER REVIEW
// ==========================================

confirmOrderButton.addEventListener(
    "click",
    function() {

        const orderItemIds =
            Object.keys(currentOrder);


        // Don't allow an empty order.
        if (orderItemIds.length === 0) {

            alert("Please add at least one product.");

            return;

        }


        reviewItems.innerHTML = "";

        let total = 0;


        // Display each order item.
        orderItemIds.forEach(function(productId) {

            const item =
                currentOrder[productId];

            const lineTotal =
                item.price * item.quantity;

            total += lineTotal;


            const reviewItem =
                document.createElement("p");


            reviewItem.innerHTML = `
                <strong>
                    ${item.productName}
                </strong>

                × ${item.quantity}

                — ₱${lineTotal.toFixed(2)}
            `;


            reviewItems.appendChild(
                reviewItem
            );

        });


        // Display total.
        reviewTotal.textContent =
            "Total: ₱" + total.toFixed(2);


        // Hide current order.
        currentOrderContainer.style.display =
            "none";


        // Show review section.
        orderReview.style.display =
            "block";

    }
);

// ==========================================
// BACK TO CURRENT ORDER
// ==========================================

backToOrderButton.addEventListener(
    "click",
    function() {

        orderReview.style.display =
            "none";

        currentOrderContainer.style.display =
            "block";

    }
);

// ==========================================
// FINAL CONFIRMATION
// ==========================================

finalConfirmButton.addEventListener(
    "click",
    async function() {

        const orderItemIds =
            Object.keys(currentOrder);


        // Make sure the order is not empty.
        if (orderItemIds.length === 0) {

            alert("The order is empty.");

            return;

        }


        // Prepare the items that will be sent to PHP.
        const items = orderItemIds.map(
            function(productId) {

                const item =
                    currentOrder[productId];

                return {

                    productId: item.productId,

                    productName: item.productName,

                    price: item.price,

                    quantity: item.quantity

                };

            }
        );


        // Calculate the total again.
        let total = 0;

        items.forEach(function(item) {

            total +=
                item.price * item.quantity;

        });


        try {

            // Send the order to PHP.
            const response = await fetch(
                "save_order.php",
                {

                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    body: JSON.stringify({

                        items: items,

                        total: total

                    })

                }
            );


            // Convert PHP's response to JavaScript.
            const result =
                await response.json();


            // Display PHP's response.
            if (result.success) {

    // Show order reference.
    completedReference.textContent =
        result.order_reference;

    // Show final server-calculated total.
    completedTotal.textContent =
        "₱" +
        Number(result.total).toFixed(2);

    // Hide the order/review sections.
    currentOrderContainer.style.display =
        "none";

    orderReview.style.display =
        "none";

    // Show completed section.
    orderCompleted.style.display =
        "block";

} else {

    alert(
        "Order failed:\n" +
        result.message
    );

}

        } catch (error) {

            console.error(error);

            alert(
                "Could not connect to the server."
            );

        }

    }
);

    </script>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

</body>
</html>