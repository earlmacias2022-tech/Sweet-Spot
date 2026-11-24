<?php
require_once __DIR__ . '/../config/app.php';

$pageTitle = $pageTitle ?? SITE_NAME;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | Sweet Spot</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>

    <header class="site-header">
        <div class="container">

            <a href="<?= BASE_URL ?>/index.php" class="logo">
                Sweet Spot
            </a>

            <nav class="main-nav">
                <a href="<?= BASE_URL ?>/index.php">Home</a>
                <a href="<?= BASE_URL ?>/index.php#products">Our Products</a>
                <a href="<?= BASE_URL ?>/index.php#about">About Us</a>
                <a href="<?= BASE_URL ?>/index.php#contact">Contact</a>
            </nav>

        </div>
    </header>

    <main>