<?php
// Database connection file
$db_name = "mysql:host=localhost;dbname=GroceryStoreDB;charset=utf8mb4";
$username = "root";
$password = "Turki123456";

try {
    $conn = new PDO($db_name, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Enable error handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Fetch results as an associative array
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage()); // Display error if connection fails
}
?>
