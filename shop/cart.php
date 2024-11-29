<?php
session_start();
include("db.php");
include("menu.php");

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// Initialize cart
$cart = [];

// Fetch cart items for logged-in user
if ($isLoggedIn) {
    $cart_query = $conn->prepare("SELECT p.*, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    if ($cart_query === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $cart_query->bind_param("i", $user_id);
    if (!$cart_query->execute()) {
        die("Error executing query: " . $cart_query->error);
    }
    $cart_result = $cart_query->get_result();

    while ($item = $cart_result->fetch_assoc()) {
        $cart[] = $item; // Store each cart item
    }
}

// Remove item from cart
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    $remove_query = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    if ($remove_query === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $remove_query->bind_param("ii", $user_id, $product_id);
    if (!$remove_query->execute()) {
        die("Error executing query: " . $remove_query->error);
    }
    header("Location: cart.php");
    exit();
}

// Add item to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Check if product is in stock
    $stock_query = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    if ($stock_query === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stock_query->bind_param("i", $product_id);
    if (!$stock_query->execute()) {
        die("Error executing query: " . $stock_query->error);
    }
    $stock_result = $stock_query->get_result();
    $stock = $stock_result->fetch_assoc()['stock'];

    // Check if product already exists in cart
    $check_cart_query = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    if ($check_cart_query === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $check_cart_query->bind_param("ii", $user_id, $product_id);
    if (!$check_cart_query->execute()) {
        die("Error executing query: " . $check_cart_query->error);
    }
    $cart_check_result = $check_cart_query->get_result();

    if ($stock >= $quantity && $cart_check_result->num_rows == 0) {
        // Insert into cart
        $insert_cart_query = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        if ($insert_cart_query === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $insert_cart_query->bind_param("iii", $user_id, $product_id, $quantity);
        if (!$insert_cart_query->execute()) {
            die("Error executing query: " . $insert_cart_query->error);
        }
        header("Location: cart.php");
    } else {
        $error = "This product is out of stock or already in your cart.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Mobile Store</title>
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    

    <div class="cart-container">
        <h2>Your Cart</h2>

        <?php if (!empty($cart)): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($cart as $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="50"></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>$<?= number_format($subtotal, 2) ?></td>
                        <td><a href="cart.php?remove=<?= $item['id'] ?>" class="btn">Remove</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-total">
                <h3>Total: $<?= number_format($total, 2) ?></h3>
                <?php if ($isLoggedIn): ?>
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="total" value="<?= $total ?>">
                        <?php foreach ($cart as $item): ?>
                            <input type="hidden" name="products[]" value="<?= htmlspecialchars(json_encode($item)) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn">Proceed to Checkout</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php">log in</a> to proceed to checkout.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Your cart is empty. Start adding products!</p>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
