<?php
require_once __DIR__ . '/../config/app.php';

$pageTitle = $pageTitle ?? SITE_NAME;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <meta name="description" content="Discover freshly baked breads, pastries, and sweet treats at Sweet Spot.">
    <title><?= htmlspecialchars($pageTitle) ?> | Sweet Spot</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= BASE_URL ?>/index.php" class="logo" aria-label="Sweet Spot home">
                <img src="<?= BASE_URL ?>/assets/images/sweet-spot-logo.png" alt="Sweet Spot">
            </a>

            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation">
                <span></span>
                <span></span>
            </button>

            <nav class="main-nav" id="main-navigation" aria-label="Main navigation">
                <a href="<?= BASE_URL ?>/index.php#home" class="nav-link">Home</a>
                <a href="<?= BASE_URL ?>/index.php#products" class="nav-link">Menu</a>
                <a href="<?= BASE_URL ?>/index.php#promotions" class="nav-link">Promotions</a>
                <a href="<?= BASE_URL ?>/index.php#announcements" class="nav-link">Announcements</a>
                <a href="<?= basename($_SERVER['PHP_SELF']) === 'product.php' ? '#reviews' : BASE_URL . '/index.php#products' ?>" class="nav-link">Reviews</a>
                <a href="<?= BASE_URL ?>/index.php#about" class="nav-link">About</a>
                <a href="<?= BASE_URL ?>/index.php#contact" class="nav-link">Contact</a>
                <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-small nav-cta">Owner Login <span aria-hidden="true">↗</span></a>
            </nav>
        </div>
    </header>

    <main>