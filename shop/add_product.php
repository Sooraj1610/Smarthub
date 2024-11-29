<?php
session_start();
include("db.php");
include("menu.php");

// Check if the user is logged in and is an administrator
$isLoggedIn = isset($_SESSION['user_id']);
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

// Fetch categories from the database
$category_query = $conn->prepare("SELECT * FROM categories");
$category_query->execute();
$category_result = $category_query->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle product addition
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    // Handle file upload
    $target_dir = "images/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (limit to 5MB)
    if ($_FILES["image"]["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk === 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        // If everything is ok, try to upload the file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Insert product information into database
            $image_name = basename($_FILES["image"]["name"]); // Only store the file name
            $stmt = $conn->prepare("INSERT INTO products (name, price, stock, description, image, category_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsssi", $name, $price, $stock, $description, $image_name, $category_id); // Use $image_name here
            $stmt->execute();
            header("Location: admin.php?message=Product added successfully.");
            exit();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Add New Product</h1>
        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" name="price" id="price" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" name="stock" id="stock" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Select a category</option>
                    <?php while ($category = $category_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" name="image" id="image" accept="image/*" required>
            </div>
            <button type="submit" class="btn-submit">Add Product</button>
            <a href="admin.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
