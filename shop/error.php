<?php
session_start();
include("menu.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Mobile Store</title>
    <link rel="stylesheet" href="css/error.css"> 
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            text-align: center;
            padding: 50px;
        }
        .error-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        h1 {
            color: #FF0000; /* Red color for error */
        }
        p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #ff6600;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="error-container">
    <h1>An error occurred</h1>
        <p>Sorry, something went wrong while processing your order.</p>
        <p>Please try again or contact support.</p>

        <!-- Optional: link back to home or previous page -->
        <a href="index.php" class="btn">Return to home page</a>
    </div>
</body>
</html>
