<?php

session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $sql = "SELECT user_id, username, password
                FROM users
                WHERE username = :username
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "username" => $username
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];

            header("Location: ../dashboard/index.php");
            exit;

        } else {

            $error = "Invalid username or password.";

        }
    }
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

    <title>Login - Lorie's Food Hub POS</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link rel="manifest" href="../manifest.json">

    <meta name="theme-color" content="#E9B511">

</head>

<body>

    <main class="login-container">

        <section class="login-card">

            <div class="login-header">

                <h1>
                    Lorie's Food Hub
                </h1>

                <p>
                    POS System
                </p>

            </div>


            <h2>Login</h2>


            <?php if ($error !== ""): ?>

                <p class="error-message">
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endif; ?>


            <form method="POST">

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary login-button"
                >
                    Login
                </button>

            </form>

        </section>

    </main>

    <script src="../assets/js/app.js"></script>

</body>
</html>