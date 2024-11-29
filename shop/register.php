<?php
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $address = $_POST['address'];
    $city = $_POST['city'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("INSERT INTO customers (name, email, password, address, city, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $password, $address, $city, $phone);

    if ($stmt->execute()) {
        header("Location: login.php");
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mobile Store</title>
    <link rel="stylesheet" href="css/login-register.css">
</head>
<body>
    <div class="form-container">
        <h2>Register</h2>
        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="name">Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="address">Address:</label>
                <input type="text" name="address">
            </div>
            <div class="input-group">
                <label for="city">City:</label>
                <input type="text" name="city">
            </div>
            <div class="input-group">
                <label for="phone">Phone:</label>
                <input type="text" name="phone">
            </div>
            <button type="submit" class="btn">Register</button>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
        <p><a href="index.php" class="btn-secondary">Back to index</a></p>
    </div>
</body>
</html>
