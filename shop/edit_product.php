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

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $query = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $query->bind_param("i", $product_id);
    $query->execute();
    $product = $query->get_result()->fetch_assoc();

    if (!$product) {
        header("Location: admin.php?error=Product not found.");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}

// Fetch categories from the database
$category_query = $conn->prepare("SELECT * FROM categories");
$category_query->execute();
$category_result = $category_query->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle product update
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, category_id = ? WHERE id = ?");
    $stmt->bind_param("sdissi", $name, $price, $stock, $description, $category_id, $product_id);

    // Check if an image is uploaded
    if (!empty($_FILES['image']['name'])) {
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
                $image_name = basename($_FILES["image"]["name"]); // Only store the file name
                // Update product with new image
                $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, category_id = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sdisssi", $name, $price, $stock, $description, $category_id, $image_name, $product_id);
                $stmt->execute();
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        // If no new image is uploaded, just update the other fields
        $stmt->execute();
    }

    header("Location: admin.php?message=Product updated successfully.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Edit Product</h1>
        <form method="POST" action="edit_product.php?product_id=<?= $product_id ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" name="price" id="price" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" name="stock" id="stock" min="0" value="<?= htmlspecialchars($product['stock']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Select a category</option>
                    <?php while ($category = $category_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Product Image (optional)</label>
                <input type="file" name="image" id="image" accept="image/*">
            </div>
            <button type="submit" class="btn-submit">Update Product</button>
            <a href="admin.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
