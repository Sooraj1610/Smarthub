<?php
// Database configuration
$host = 'localhost'; // Database host
$db_name = 'shop';
$username = 'root';
$password = ''; 


$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
