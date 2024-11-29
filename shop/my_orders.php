
<?php
session_start();
include("db.php");


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];


$orders_query = $conn->prepare("SELECT o.*, c.name as customer_name FROM orders o 
                                 JOIN customers c ON o.customer_id = c.id 
                                 WHERE o.customer_id = ? 
                                 ORDER BY o.order_date DESC");
$orders_query->bind_param("i", $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Mobile Store</title>
    <link rel="stylesheet" href="css/my_orders.css">
</head>
<body>
      
    <div class="orders-container">
        <h1>My Orders</h1>

        <?php if ($orders_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))) ?></td>
                            <td>$<?= number_format($order['total'], 2) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>
                                <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no orders yet.</p>
        <?php endif; ?>

        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
