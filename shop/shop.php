<?php
session_start(); 
include("db.php");
include("menu.php");


// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';


$search = '';
$category_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    $category_id = $_GET['category_id'] ?? 0;
}


$query = "SELECT * FROM products WHERE 1";
if ($search) {
    $query .= " AND name LIKE '%$search%'";
}
if ($category_id) {
    $query .= " AND category_id = $category_id";
}
$result = $conn->query($query);


$best_sellers_query = "
    SELECT p.*, SUM(oi.quantity) AS total_sold 
    FROM products p 
    JOIN order_items oi ON p.id = oi.product_id 
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 5";
$best_sellers = $conn->query($best_sellers_query);

$most_commented_query = "
    SELECT p.*, COUNT(c.id) AS total_comments 
    FROM products p 
    JOIN comments c ON p.id = c.product_id 
    GROUP BY p.id 
    ORDER BY total_comments DESC 
    LIMIT 5";
$most_commented = $conn->query($most_commented_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Mobile Store</title>
    <link rel="stylesheet" href="css/shop.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
  

    <div class="container">
     
        <form method="GET" action="shop.php" class="search-form">
            <input type="text" name="search" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
            <select name="category_id">
                <option value="0">All Categories</option>
                <?php
                $categories = $conn->query("SELECT * FROM categories");
                while ($category = $categories->fetch_assoc()) {
                    echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
                }
                ?>
            </select>
            <button type="submit" class="btn">Search</button>
        </form>

      
        <div class="product-grid">
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p>Price: $<?= htmlspecialchars($product['price']) ?></p>
                    <a href="product.php?id=<?= $product['id'] ?>" class="btn">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>

     
        <h2>Best Sellers</h2>
        <div class="product-grid">
            <?php while ($product = $best_sellers->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p>Total Sold: <?= htmlspecialchars($product['total_sold']) ?></p>
                    <p>Price: $<?= htmlspecialchars($product['price']) ?></p>
                    <a href="product.php?id=<?= $product['id'] ?>" class="btn">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>

       
        <h2>Most Commented</h2>
        <div class="product-grid">
            <?php while ($product = $most_commented->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p>Total Comments: <?= htmlspecialchars($product['total_comments']) ?></p>
                    <p>Price: $<?= htmlspecialchars($product['price']) ?></p>
                    <a href="product.php?id=<?= $product['id'] ?>" class="btn">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
