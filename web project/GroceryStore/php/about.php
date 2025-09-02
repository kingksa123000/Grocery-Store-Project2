<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$website_name = "Al-Rakah Store"; 
$about_content = "Welcome to Al-Rakah Store, your trusted online grocery destination in the heart of Al-Rakah! We're more than just a website; we're your neighbors committed to providing you with a seamless, convenient, and enjoyable grocery shopping experience from the comfort of your home. Our journey began with a simple vision: to make high-quality groceries easily accessible to everyone in our community. We understand the demands of modern life, and we believe that grocery shopping shouldn't be a time-consuming chore. That's why we've curated a wide selection of fresh produce, pantry staples, household essentials, and specialty items, all just a few clicks away..."; 
$our_promises = [
    "Quality and Freshness: We partner with local farmers and trusted suppliers...",
    "Convenience at Your Fingertips: Say goodbye to crowded aisles...",
    "Community Focus: We are deeply rooted in the Al-Rakah community...",
    "Wide Selection: Whether you're looking for everyday essentials...",
    "Reliable Delivery: We understand the importance of timely delivery...",
];
$contact_phone = "[Phone Number :0560926611]";
$contact_email = "[Email Address:Al-Rakah_store@gmail.com]";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?= htmlspecialchars($website_name) ?></title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="dashboard">
        <h1 class="title">About <?= htmlspecialchars($website_name) ?></h1>

        <div class="user_product_container about-page-container">
            <div class="about-us-section">
                <h2>Our Story</h2>
                <p><?= nl2br(htmlspecialchars($about_content)) ?></p>
            </div>

            <div class="our-promises-section">
                <h2>Our Promises</h2>
                <ul>
                    <?php foreach ($our_promises as $promise): ?>
                        <li><?= htmlspecialchars($promise) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="contact-us-section">
                <h2>Contact Us</h2>
                <p>Have questions or feedback? We'd love to hear from you!</p>
                <p>Visit our <a href="contact.php">Contact Us</a> page or reach out to us directly at:</p>
                <p>Phone: <?= htmlspecialchars($contact_phone) ?></p>
                <p>Email: <a href="mailto:<?= htmlspecialchars($contact_email) ?>"><?= htmlspecialchars($contact_email) ?></a></p>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>