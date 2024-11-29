<?php
session_start();
include("db.php");
include("menu.php");

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// Initialize variables
$total = 0;
$cartItems = [];

// Function to update stock
function updateStock($conn, $productId, $quantity) {
    $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $productId);
    return $stmt->execute();
}

// Function to insert order
function createOrder($conn, $userId, $total) {
    // Prepare the SQL statement with the correct syntax
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, total, status) VALUES (?, ?, ?)");
    $status = "Pending"; // Set the status value

    // Bind the parameters (userId as integer, total as decimal, status as string)
    $stmt->bind_param("ids", $userId, $total, $status);

    // Execute the statement
    if ($stmt->execute()) {
        return $conn->insert_id; // Return the last inserted ID
    } else {
        // Handle the error (optional)
        echo "Error: " . $stmt->error;
        return false; // Indicate failure
    }
}


// Function to insert order items
function createOrderItems($conn, $orderId, $productId, $quantity, $price) {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
    return $stmt->execute();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve products from the form
    $products = isset($_POST['products']) ? $_POST['products'] : [];
    // Decode products from JSON
    $cartItems = array_map('json_decode', $products);

    // Retrieve total amount
    $total = isset($_POST['total']) ? $_POST['total'] : 0;

    // Process payment and order confirmation
    if (isset($_POST['card_number'], $_POST['expiry_date'], $_POST['cvv'], $_POST['name'])) {
        $card_number = preg_replace('/\D/', '', $_POST['card_number']); // Remove non-digit characters
        $expiry_date = $_POST['expiry_date'];
        $cvv = $_POST['cvv'];
        $name = $_POST['name'];

        // Basic validation
        if (empty($card_number) || empty($expiry_date) || empty($cvv) || empty($name)) {
            $error = "Please fill in all payment fields.";
        } else {
            // Validate expiry date
            $expiry_month = intval(substr($expiry_date, 5, 2));
            $expiry_year = intval(substr($expiry_date, 0, 4));
            $current_month = intval(date('m'));
            $current_year = intval(date('Y'));

            if ($expiry_year < $current_year || ($expiry_year === $current_year && $expiry_month < $current_month)) {
                $error = "Card has expired.";
            }

            // Validate CVV
            if (!preg_match('/^\d{3,4}$/', $cvv)) {
                $error = "Invalid CVV.";
            }

            // If all validations pass, process the payment
            if (!isset($error)) {
                // Check stock and update
                foreach ($cartItems as $item) {
                    $productId = $item->id;
                    $orderedQuantity = $item->quantity;

                    // Fetch current stock
                    $stockResult = $conn->query("SELECT stock FROM products WHERE id = $productId");
                    $stockRow = $stockResult->fetch_assoc();
                    $currentStock = $stockRow['stock'];

                    // Check if there is enough stock
                    if ($currentStock < $orderedQuantity) {
                        $error = "Not enough stock for " . htmlspecialchars($item->name) . ".";
                        break;
                    }
                }

                // If stock is sufficient, create order and order items
                if (!isset($error)) {
                    // Create a new order
                    $orderId = createOrder($conn, $user_id, $total);

                    // Insert order items
                    foreach ($cartItems as $item) {
                        // Update stock in the database
                        updateStock($conn, $item->id, $item->quantity);
                        // Create order items
                        createOrderItems($conn, $orderId, $item->id, $item->quantity, $item->price);
                    }

                    // Clear the cart
                    $conn->query("DELETE FROM cart WHERE user_id = $user_id");

                    // Redirect to loading page with order_id
                    header("Location: loading.php?order_id=$orderId");
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mobile Store</title>
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="css/index.css">
   
</head>
<body>
   

    <div class="checkout-container">
        <div class="payment-form">
            <h2>Payment Information</h2>

            <?php if (!empty($cartItems)): ?>
                <form action="checkout.php" method="POST">
                    <label for="name">Name on Card:</label>
                    <input type="text" id="name" name="name" required>

                    <label for="card_number">Card Number:</label>
                    <input type="text" id="card_number" name="card_number" maxlength="19" required>

                    <label for="expiry_date">Expiry Date (YYYY-MM):</label>
                    <input type="month" id="expiry_date" name="expiry_date" required>

                    <label for="cvv">CVV:</label>
                    <input type="text" id="cvv" name="cvv" maxlength="3" required>

                    <input type="hidden" name="total" value="<?= htmlspecialchars($total) ?>">
                    <?php foreach ($cartItems as $item): ?>
                        <input type="hidden" name="products[]" value="<?= htmlspecialchars(json_encode($item)) ?>">
                    <?php endforeach; ?>

                    <button type="submit" class="btn">Confirm Payment</button>
                </form>

                <?php if (isset($error)): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Your cart is empty. Go back and add some products!</p>
            <?php endif; ?>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <ul>
                <?php foreach ($cartItems as $item): ?>
                    <li>
                        <?= htmlspecialchars($item->name) ?> (x<?= htmlspecialchars($item->quantity) ?>) - $<?= htmlspecialchars(number_format($item->price, 2)) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total: $<?= htmlspecialchars(number_format($total, 2)) ?></strong></p>
        </div>
    </div>
</body>
</html>
