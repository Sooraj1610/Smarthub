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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mobile Store</title>
    <link rel="stylesheet" href="css/order_details.css">
</head>
<body>
    <div class="order-details-container">
        <h1>Order Details</h1>
        
        <h2>Customer Information</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($order_details['customer_name']) ?></p>
        <p><strong>Order ID:</strong> <?= htmlspecialchars($order_details['id']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($order_details['order_date']))) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order_details['status']) ?></p>
        <p><strong>Total Paid:</strong> $<?= number_format($order_details['total'], 2) ?></p>

        <h2>Items Purchased</h2>
        <ul>
            <?php foreach ($items_purchased as $item): ?>
                <li><?= htmlspecialchars($item['name']) ?> x<?= intval($item['quantity']) ?> - $<?= number_format($item['price'] * $item['quantity'], 2) ?></li>
            <?php endforeach; ?>
        </ul>

        <a href="my_orders.php" class="btn">Back to My Orders</a>
        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
