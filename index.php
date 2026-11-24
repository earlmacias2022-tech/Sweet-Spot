<?php
$pageTitle = 'Home';

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <p>WELCOME TO SWEET SPOT</p>

        <h1>Freshly Baked, Made with Love.</h1>

        <p>
            Discover delicious treats and freshly baked
            favorites made to bring sweetness to your day.
        </p>

        <a href="#products" class="btn">Explore Our Products</a>
    </div>
</section>

<section id="products" class="section">
    <div class="container">
        <h2>Our Products</h2>
        <p>Explore our collection of freshly baked treats.</p>
    </div>
</section>

<section id="about" class="section">
    <div class="container">
        <h2>About Sweet Spot</h2>
        <p>Learn more about our bakery and what makes us special.</p>
    </div>
</section>

<section id="contact" class="section">
    <div class="container">
        <h2>Get in Touch</h2>
        <p>We would love to hear from you.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>