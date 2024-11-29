<?php
session_start();
include("db.php");
include("menu.php");

// Check if the user is logged in and is an administrator
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$admin_check_query = $conn->prepare("SELECT * FROM admins WHERE customer_id = ?");
$admin_check_query->bind_param("i", $customer_id);
$admin_check_query->execute();
$admin_result = $admin_check_query->get_result();

if ($admin_result->num_rows === 0) {
    header("Location: unauthorized.php");
    exit();
}

// Fetch data from the database
function getProducts($conn) {
    $query = $conn->prepare("SELECT * FROM products");
    $query->execute();
    return $query->get_result();
}

function getOrders($conn, $status = null) {
    $query_str = "SELECT o.*, c.name AS customer_name FROM orders o 
                  JOIN customers c ON o.customer_id = c.id";
    if ($status) {
        $query_str .= " WHERE o.status = ?";
    }
    $query_str .= " ORDER BY o.order_date DESC";

    $query = $conn->prepare($query_str);
    if ($status) {
        $query->bind_param("s", $status);
    }
    $query->execute();
    return $query->get_result();
}

function getUsers($conn) {
    $query = $conn->prepare("SELECT * FROM customers");
    $query->execute();
    return $query->get_result();
}

function getAdmins($conn) {
    $query = $conn->prepare("SELECT a.id, c.name, c.email FROM admins a 
                              JOIN customers c ON a.customer_id = c.id");
    $query->execute();
    return $query->get_result();
}

function getContacts($conn) {
    $query = $conn->prepare("SELECT * FROM contact");
    $query->execute();
    return $query->get_result();
}


function hasOrders($conn, $product_id) {
    $query = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
    $query->bind_param("i", $product_id);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();
    return $count > 0; 
}

// Handling actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Promote User to Administrator
    if (isset($_POST['promote_user'])) {
        $user_id = $_POST['user_id'];

        // Check if the user is already an admin
        $check_admin_query = $conn->prepare("SELECT * FROM admins WHERE customer_id = ?");
        $check_admin_query->bind_param("i", $user_id);
        $check_admin_query->execute();
        $check_result = $check_admin_query->get_result();

        if ($check_result->num_rows > 0) {
            header("Location: admin.php?error=User is already an admin.");
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO admins (customer_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            header("Location: admin.php?message=User promoted to admin successfully.");
            exit();
        }
    }

    // Remove Admin
    if (isset($_POST['remove_admin'])) {
        $admin_id = $_POST['admin_id'];
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        header("Location: admin.php?message=Admin removed successfully.");
        exit();
    }

    // Update Order
    if (isset($_POST['update_order'])) {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        header("Location: admin.php?message=Order status updated successfully.");
        exit();
    }

    // Delete Product
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];

        if (hasOrders($conn, $product_id)) {
            header("Location: admin.php?error=It is not possible to delete the product because it has already been ordered.");
            exit();
        } else {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            header("Location: admin.php?message=Product successfully deleted.");
            exit();
        }
    }
}

$order_status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$products = getProducts($conn);
$orders = getOrders($conn, $order_status_filter);
$users = getUsers($conn);
$admins = getAdmins($conn);
$contacts = getContacts($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mobile Store</title>
    <link rel="stylesheet" href="css/admin.css">
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this item?");
        }

        // Prevent page scroll to top on form submission
        window.onload = function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.onsubmit = function(e) {
                    e.preventDefault(); // Prevent default form submission
                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        document.body.innerHTML = data; // Replace the body content with response
                    })
                    .catch(error => console.error('Error:', error));
                };
            });
        };
    </script>
</head>
<body>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>
        <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>

        <!-- Display success or error messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Product List -->
        <section class="section">
            <h2>Product List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['id']) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>$<?= number_format($product['price'], 2) ?></td>
                            <td><?= htmlspecialchars($product['stock']) ?></td>
                            <td>
                                <a href="edit_product.php?product_id=<?= $product['id'] ?>" class="btn-edit">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <a href="add_product.php" class="btn-add">Add New Product</a>
        </section>

        <!-- Divider -->
        <div class="divider"></div>

        <!-- Order List with Filter -->
<section class="section">
    <h2>Order List</h2>
    <form method="GET" action="admin.php" class="form-filter">
        <label for="status">Filter by Status:</label>
        <select name="status" id="status">
            <option value="">All</option>
            <option value="Pending" <?= $order_status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Completed" <?= $order_status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Canceled" <?= $order_status_filter === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
        </select>
        <button type="submit" class="btn-filter">Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($order['id']) ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= htmlspecialchars($order['order_date']) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status">
                                <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Canceled" <?= $order['status'] === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                            </select>
                            <button type="submit" name="update_order" class="btn-update">Update</button>
                        </form>
                        <!-- Button to view order details -->
                        <a href="view_order.php?order_id=<?= $order['id'] ?>" class="btn-view">View Details</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>


        <!-- Divider -->
        <div class="divider"></div>

        <!-- User Management -->
        <section class="section">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="promote_user" class="btn-promote">Promote to Admin</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- Divider -->
        <div class="divider"></div>

        <!-- Admin Management -->
        <section class="section">
            <h2>Admin Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['id']) ?></td>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" name="remove_admin" class="btn-remove">Remove Admin</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- Divider -->
        <div class="divider"></div>

        <!-- Contact Messages -->
        <section class="section">
            <h2>Contact Messages</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($contact = $contacts->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($contact['id']) ?></td>
                            <td><?= htmlspecialchars($contact['name']) ?></td>
                            <td><?= htmlspecialchars($contact['email']) ?></td>
                            <td><?= htmlspecialchars($contact['message']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
