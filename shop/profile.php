<?php
session_start();
include 'db.php'; 


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


$order_query = "SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC LIMIT 5";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Mobile Store</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <!-- Include the menu -->
    <?php include 'menu.php'; ?>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
            <div class="profile-details">
                <div class="profile-card">
                    <h2>Personal Information</h2>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                    <a href="edit_profile.php" class="btn-edit">Edit Profile</a>
                </div>

                <div class="order-history">
                    <h2>Recent Orders</h2>
                    <ul>
                        <?php while ($order = $order_result->fetch_assoc()): ?>
                            <li>
                                <p>Order ID: <?php echo $order['id']; ?> | Total: $<?php echo $order['total']; ?> | Status: <?php echo $order['status']; ?></p>
                                <p><small>Order Date: <?php echo $order['order_date']; ?></small></p>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <a href="my_orders.php" class="btn-view">View All Orders</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2024 Mobile Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
