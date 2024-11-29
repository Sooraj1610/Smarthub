<?php 
session_start(); 
include("db.php"); 
include("menu.php");

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_form'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        $contact_message = "Message sent successfully!";
    } else {
        $contact_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch random products
$productQuery = "SELECT * FROM products ORDER BY RAND() LIMIT 3";
$result = $conn->query($productQuery);
$hasProducts = $result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mobile Store</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
   
    <!-- Hero Section with Video Banner -->
    <section class="video-banner">
        <div class="video-container">
            <video autoplay muted loop playsinline>
                <source src="banner.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay">
                <div class="video-content">
                    <h2>Explore the Best Mobile Deals</h2>
                    <p>Get your hands on the latest smartphones with incredible discounts.</p>
                    <a href="shop.php" class="btn">Shop Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-mobile-alt"></i>
        <h2 class="section-title-large">Our Top-Selling Phones</h2>
    </div>

    <!-- Products Section -->
    <section class="products" id="products">
        <div class="container">
            <div class="product-grid">
                <?php if ($hasProducts): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="card">
                            <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p>$<?php echo htmlspecialchars(number_format($row['price'], 2)); ?></p>
                            <a href="product.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn">Buy Now</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No products available for sale at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-question-circle"></i>
        <h2 class="section-title-large">Frequently Asked Questions</h2>
    </div>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="container">
            <div class="faq-item">
                <h3>What is the return policy?</h3>
                <p>You can return the product within 30 days of purchase with a valid receipt.</p>
            </div>
            <div class="faq-item">
                <h3>Do you offer free shipping?</h3>
                <p>Yes, free shipping is available for orders over $500.</p>
            </div>
            <div class="faq-item">
                <h3>Can I buy in installments?</h3>
                <p>Yes, we offer installment plans with select payment options.</p>
            </div>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-envelope"></i>
        <h2 class="section-title-large">Contact Us</h2>
    </div>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <form class="contact-form" action="" method="POST">
                <input type="hidden" name="contact_form" value="1">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="5" required></textarea>

                <button type="submit" class="btn">Send Message</button>
            </form>
            <?php if (isset($contact_message)): ?>
                <p><?php echo htmlspecialchars($contact_message); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-map-marker-alt"></i>
        <h2 class="section-title-large">Our Store Location</h2>
    </div>

    <!-- Google Maps Section -->
    <section class="location">
        <div class="container">
            <div id="map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3151.8354345093633!2d144.95373531531658!3d-37.81627944202192!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad642af0f11fd81%3A0xf577db5701c3b9ff!2sMobile%20Store!5e0!3m2!1sen!2sau!4v1601482801598!5m2!1sen!2sau" width="100%" height="450" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
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
