<?php
session_start();
include("db.php");


if (!isset($_GET['order_id'])) {
    header("Location: error.php");
    exit();
}

$order_id = $_GET['order_id'];


$order_query = $conn->prepare("SELECT o.*, c.name as customer_name FROM orders o 
                                JOIN customers c ON o.customer_id = c.id 
                                WHERE o.id = ?");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order_result = $order_query->get_result();


if ($order_result->num_rows === 0) {
    header("Location: error.php");
    exit();
}

$order_details = $order_result->fetch_assoc();

$items_query = $conn->prepare("SELECT oi.*, p.name as product_name FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();


$items_purchased = [];
while ($item = $items_result->fetch_assoc()) {
    $items_purchased[] = [
        'name' => $item['product_name'],
        'quantity' => $item['quantity'],
        'price' => $item['price']
    ];
}


if (empty($items_purchased)) {
    header("Location: error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Mobile Store</title>
    <link rel="stylesheet" href="css/success.css">
</head>
<body>
    <div class="success-container">
        <h1>Thank You for Your Purchase!</h1>
        <p>Your payment has been processed successfully.</p>
        <p>Your order status is currently being defined. You can check your orders for updates.</p>

        <h2>Order Invoice</h2>
        <div class="invoice">
            <p><strong>Name:</strong> <?= htmlspecialchars($order_details['customer_name']) ?></p>
            <p><strong>Total Paid:</strong> $<?= number_format($order_details['total'], 2) ?></p>
            <h3>Items Purchased</h3>
            <ul>
                <?php foreach ($items_purchased as $item): ?>
                    <li><?= htmlspecialchars($item['name']) ?> x<?= intval($item['quantity']) ?> - $<?= number_format($item['price'] * $item['quantity'], 2) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="my_orders.php" class="btn">My Orders</a>
        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
