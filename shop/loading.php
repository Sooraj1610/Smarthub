<?php
session_start();
include("db.php");

// Check if order_id is set in the URL
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    // Fetch order details using the order_id
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();

    // Check if the order exists
    if ($order_result->num_rows > 0) {
        // Store the order_id in a session if needed
        $_SESSION['order_id'] = $order_id; 
        
        // Redirect to success page with order_id after 3 seconds
        header("Refresh: 3; url=success.php?order_id=$order_id");
        $message = "Redirecting to success page...";
    } else {
        // Redirect to error page if the order_id is invalid
        header("Location: error.php");
        exit();
    }
} else {
    // Redirect to error page if order_id is not set
    header("Location: error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Mobile Store</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: white;
        }
        .container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }
        .spinner {
            width: 80px;
            height: 80px;
            border: 7px solid #ff8800;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spinner 0.7s linear infinite;
        }
        @keyframes spinner {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
    </div>
</body>
</html>
