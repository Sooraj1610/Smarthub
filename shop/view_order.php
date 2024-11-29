<?php
session_start();
include("db.php");
include("menu.php"); 


$isLoggedIn = isset($_SESSION['user_id']);

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch order details
$order_query = $conn->prepare("SELECT o.*, c.name AS customer_name FROM orders o 
                                JOIN customers c ON o.customer_id = c.id 
                                WHERE o.id = ?");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows === 0) {
    echo "Order not found!";
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch order items
$order_items_query = $conn->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi 
                                      JOIN products p ON oi.product_id = p.id 
                                      WHERE oi.order_id = ?");
$order_items_query->bind_param("i", $order_id);
$order_items_query->execute();
$order_items_result = $order_items_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mobile Store</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .order-details-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3 {
            text-align: center;
            color: #333;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn-back {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <h1>Order Details</h1>
        <h2>Order ID: <?= htmlspecialchars($order['id']) ?></h2>
        <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>

        <h3>Ordered Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price per unit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $order_items_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="admin.php" class="btn-back">Back to Admin Dashboard</a>
    </div>
</body>
</html>
