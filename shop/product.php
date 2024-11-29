<?php
session_start();
include("db.php");
include("menu.php");

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Prepare statement to retrieve the product
    $query = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                             JOIN categories c ON p.category_id = c.id 
                             WHERE p.id = ?");
    if (!$query) {
        die("SQL Error: " . $conn->error); // Added error handling
    }

    $query->bind_param("i", $product_id);
    $query->execute();
    $product = $query->get_result()->fetch_assoc();

    // Check if the product exists
    if (!$product) {
        echo "Product not found.";
        exit;
    }

    // Prepare statement to retrieve comments
    $comments_query = $conn->prepare("SELECT c.*, cu.name as customer_name 
                                      FROM comments c 
                                      JOIN customers cu ON c.customer_id = cu.id 
                                      WHERE c.product_id = ?");
    if (!$comments_query) {
        die("SQL Error: " . $conn->error); // Added error handling
    }

    $comments_query->bind_param("i", $product_id);
    $comments_query->execute();
    $comments = $comments_query->get_result();

    // Check if the user has purchased the product
    $hasPurchased = false;
    if ($isLoggedIn) {
        $customer_id = $_SESSION['user_id'];
        $purchase_check_query = $conn->prepare("SELECT * FROM orders o 
                                                JOIN order_items oi ON o.id = oi.order_id 
                                                WHERE o.customer_id = ? AND oi.product_id = ?");
        if (!$purchase_check_query) {
            die("SQL Error: " . $conn->error); // Added error handling
        }

        $purchase_check_query->bind_param("ii", $customer_id, $product_id);
        $purchase_check_query->execute();
        $purchase_result = $purchase_check_query->get_result();
        if ($purchase_result->num_rows > 0) {
            $hasPurchased = true; 
        }
    }

    // Handle add to cart
    if (isset($_POST['add_to_cart'])) {
        if (!$isLoggedIn) {
            header("Location: login.php");
            exit();
        }

        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        // Check stock availability
        if (isset($product['stock']) && $product['stock'] < $quantity) {
            $error = "This product is out of stock.";
        } else {
            // Check if product is already in the cart
            $cart_query = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $cart_query->bind_param("ii", $_SESSION['user_id'], $product_id);
            $cart_query->execute();
            $cart_result = $cart_query->get_result();

            if ($cart_result->num_rows > 0) {
                $error = "This product is already in your cart.";
            } else {
                // Add product to cart
                $add_cart_query = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                if (!$add_cart_query) {
                    die("SQL Error: " . $conn->error); // Added error handling
                }
                $add_cart_query->bind_param("iii", $_SESSION['user_id'], $product_id, $quantity);
                if ($add_cart_query->execute()) {
                    $success = "Product added to cart successfully.";
                } else {
                    $error = "Failed to add product to cart.";
                }
            }
        }
    }

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment']) && $hasPurchased) {
        $customer_id = $_SESSION['user_id'];
        $comment = trim($_POST['comment']);

        $stmt = $conn->prepare("INSERT INTO comments (product_id, customer_id, comment) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("SQL Error: " . $conn->error); // Added error handling
        }
        $stmt->bind_param("iis", $product_id, $customer_id, $comment);
        if ($stmt->execute()) {
            header("Location: product.php?id=" . $product_id);
            exit(); // Added exit to prevent further execution
        } else {
            $error = "Failed to submit comment.";
        }
    }
} else {
    echo "Product not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="product-container">
        <div class="product-image">
            <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="product-details">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <p class="category">Category: <?= htmlspecialchars($product['category_name']) ?></p>
            <p class="category">Stock: <?= isset($product['stock']) ? htmlspecialchars($product['stock']) : 'N/A' ?></p>
            <p class="description"><?= isset($product['description']) ? htmlspecialchars($product['description']) : 'No description available.' ?></p>
            <p class="price">$<?= htmlspecialchars($product['price']) ?></p>

            <?php if (isset($product['stock']) && $product['stock'] <= 0): ?>
                <p class="error">This product is out of stock.</p>
            <?php else: ?>
                <form method="POST" action="product.php?id=<?= $product_id ?>">
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= isset($product['stock']) ? htmlspecialchars($product['stock']) : 1 ?>" required>
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php elseif (isset($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="comments-section">
        <h2>Customer Reviews</h2>

        <?php if ($comments->num_rows > 0): ?>
            <div class="comments-list">
                <?php while ($comment = $comments->fetch_assoc()): ?>
                    <div class="comment">
                        <h4><?= htmlspecialchars($comment['customer_name']) ?></h4>
                        <p><?= htmlspecialchars($comment['comment']) ?></p>
                        <span class="comment-date"><?= htmlspecialchars($comment['comment_date']) ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No comments yet. Be the first to review this product!</p>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
            <?php if ($hasPurchased): ?>
                <form action="product.php?id=<?= $product_id ?>" method="POST" class="comment-form">
                    <textarea name="comment" placeholder="Leave your review here..." required></textarea>
                    <button type="submit" class="btn">Submit Comment</button>
                </form>
            <?php else: ?>
                <p>You need to purchase this product before leaving a review.</p>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="login.php">Log in</a> to leave a comment.</p>
        <?php endif; ?>
    </div>
</body>
</html>
