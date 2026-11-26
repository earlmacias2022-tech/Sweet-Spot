<?php
$pageTitle = 'Freshly Baked Happiness';



require_once __DIR__ . '/config/database.php';

$products = [];
$categories = [];
$catalogError = false;

try {
    $categoryQuery = $pdo->query(
        "SELECT c.id, c.name
         FROM categories c
         WHERE c.status = 'active'
           AND EXISTS (
               SELECT 1 FROM products p
               WHERE p.category_id = c.id
                 AND p.status = 'published'
                 AND p.is_available = 1
           )
         ORDER BY c.name ASC"
    );
    $categories = $categoryQuery->fetchAll();

    $productQuery = $pdo->query(
        "SELECT p.id, p.name, p.description, p.price, p.image,
                p.is_featured, c.id AS category_id, c.name AS category_name
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id AND c.status = 'active'
         WHERE p.status = 'published' AND p.is_available = 1
         ORDER BY p.is_featured DESC, p.created_at DESC, p.id DESC"
    );
    $products = $productQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot public catalog loading failed: ' . $exception->getMessage());
    $catalogError = true;
}

function catalog_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function catalog_image_url(?string $image): string
{
    return $image ? BASE_URL . '/' . ltrim($image, '/') : '';
}
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" id="home">
    <div class="hero-shade"></div>
    <div class="container hero-inner">
        <div class="hero-content">
            <p class="eyebrow hero-eyebrow"><span></span> A LITTLE SOMETHING FRESHLY BAKED</p>
            <h1>Made with care.<br><em>Served with love.</em></h1>
            <p class="hero-description">
                From warm, flaky pastries to comforting everyday favorites,
                find something delicious waiting for you at Sweet Spot.
            </p>
            <div class="hero-actions">
                <a href="#products" class="btn">Explore Our Menu <span aria-hidden="true">↗</span></a>
                <a href="#about" class="text-link light-link">Get to know us <span aria-hidden="true">→</span></a>
            </div>
        </div>
        <div class="hero-note">
            <span class="hero-note-line"></span>
            <span>GOOD DAYS START WITH<br>SOMETHING FRESHLY BAKED</span>
        </div>
    </div>
    <a href="#intro" class="scroll-cue" aria-label="Scroll to discover"><span></span> SCROLL TO DISCOVER</a>
</section>

<section class="intro-strip" id="intro">
    <div class="container intro-grid">
        <div class="intro-item">
            <span class="intro-icon">✳</span>
            <div><strong>Freshly prepared</strong><small>Made with care every day</small></div>
        </div>
        <div class="intro-item">
            <span class="intro-icon">♡</span>
            <div><strong>Made with love</strong><small>Thoughtful from start to finish</small></div>
        </div>
        <div class="intro-item">
            <span class="intro-icon">✧</span>
            <div><strong>Little moments</strong><small>A treat for every kind of day</small></div>
        </div>
    </div>
</section>

<section class="section products-section" id="products">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow"><span></span> THE GOOD STUFF</p>
                <h2>A little something<br><em>for every craving.</em></h2>
            </div>
            <p class="section-intro">Discover a few of the treats that make an ordinary day feel a little more special.</p>
        </div>

        <div class="product-toolbar">
            <div class="filter-list" role="group" aria-label="Filter products">
                <button class="filter-btn active" type="button" data-filter="all">All treats</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" type="button" data-filter="<?= (int) $category['id'] ?>">
                        <?= catalog_escape($category['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <span class="sample-note">Our current bakery menu</span>
        </div>

        <?php if ($catalogError): ?>
            <p class="catalog-disclaimer">Our menu is temporarily unavailable. Please check back in a little while.</p>
        <?php elseif (!$products): ?>
            <p class="catalog-disclaimer">We're preparing our menu. Check back soon for freshly baked favorites.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card" data-category="<?= (int) $product['category_id'] ?>">
                        <div class="product-image-wrap">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= catalog_escape(catalog_image_url($product['image'])) ?>"
                                     alt="<?= catalog_escape($product['name']) ?>"
                                     class="product-image" loading="lazy">
                            <?php else: ?>
                                <div class="product-image-placeholder" aria-label="No product image available">✧</div>
                            <?php endif; ?>
                            <?php if ((int) $product['is_featured'] === 1): ?>
                                <span class="product-tag">Featured</span>
                            <?php endif; ?>
                            <span class="product-category"><?= catalog_escape($product['category_name']) ?></span>
                        </div>
                        <div class="product-info">
                            <div class="product-title-row">
                                <h3><?= catalog_escape($product['name']) ?></h3>
                                <span class="product-price">₱<?= number_format((float) $product['price'], 2) ?></span>
                            </div>
                            <p><?= catalog_escape($product['description'] ?: 'A freshly prepared favorite from Sweet Spot.') ?></p>
                            <a href="#contact" class="product-link" data-product="<?= catalog_escape($product['name']) ?>">
                                Ask about this treat <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="catalog-disclaimer">Only published and available products from our bakery catalog are shown here.</p>
        <?php endif; ?>    </div>
</section>

<section class="story-section" id="about">
    <div class="container story-grid">
        <div class="story-image">
            <img src="<?= BASE_URL ?>/assets/images/hero-bg-image.jpg" alt="A warm bakery scene with freshly prepared baked goods" loading="lazy">
            <div class="story-image-caption"><span>THE SWEET SPOT WAY</span><strong>Simple joys, baked daily.</strong></div>
        </div>
        <div class="story-content">
            <p class="eyebrow"><span></span> A LITTLE ABOUT US</p>
            <h2>More than a bakery.<br><em>A place for life's sweet moments.</em></h2>
            <p class="story-lead">At Sweet Spot, we believe the best moments are often the simplest ones.</p>
            <p>It's the first bite of a warm pastry, a loaf shared around the table, or a little treat picked up just because. Our goal is to make those everyday moments feel special with baked favorites made with care.</p>
            <a href="#contact" class="btn btn-dark">Say hello <span aria-hidden="true">↗</span></a>
            <div class="story-signature">Baked with a little extra love <span>♡</span></div>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container">
        <div class="center-heading">
            <p class="eyebrow"><span></span> THE SWEET SPOT DIFFERENCE <span></span></p>
            <h2>Good things take <em>a little care.</em></h2>
            <p>It's the little details that make every visit feel worth it.</p>
        </div>
        <div class="values-grid">
            <article class="value-card">
                <span class="value-number">01</span>
                <span class="value-icon">✳</span>
                <h3>Made thoughtfully</h3>
                <p>We keep the experience simple, welcoming, and focused on the things that matter.</p>
            </article>
            <article class="value-card">
                <span class="value-number">02</span>
                <span class="value-icon">♡</span>
                <h3>For sharing</h3>
                <p>From a quick afternoon treat to something for the whole table, there's joy in sharing.</p>
            </article>
            <article class="value-card">
                <span class="value-number">03</span>
                <span class="value-icon">✧</span>
                <h3>Made for your day</h3>
                <p>Everyday favorites and little indulgences to make an ordinary day feel brighter.</p>
            </article>
        </div>
    </div>
</section>

<section class="contact-section" id="contact">
    <div class="container contact-grid">
        <div class="contact-copy">
            <p class="eyebrow"><span></span> LET'S TALK ABOUT SOMETHING SWEET</p>
            <h2>Have a question<br>or a <em>special request?</em></h2>
            <p>We'd love to hear from you. Send us a message about our menu, product inquiries, or anything you'd like to know.</p>
            <div class="contact-detail">
                <span class="contact-detail-icon">✉</span>
                <div><small>GET IN TOUCH</small><strong>Our contact details will be added soon.</strong></div>
            </div>
            <div class="contact-detail">
                <span class="contact-detail-icon">⌖</span>
                <div><small>FIND US</small><strong>Store location coming soon.</strong></div>
            </div>
        </div>

        <form class="contact-form" id="contact-form">
            <div class="form-heading">
                <span>WE'RE ALL EARS</span>
                <h3>Send us a little note.</h3>
            </div>
            <div class="form-row">
                <label for="contact-name">Your name</label>
                <input type="text" id="contact-name" name="name" placeholder="How should we call you?" required>
            </div>
            <div class="form-row">
                <label for="contact-email">Email address</label>
                <input type="email" id="contact-email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-row">
                <label for="contact-subject">What is this about?</label>
                <input type="text" id="contact-subject" name="subject" placeholder="Product inquiry, question, etc.">
            </div>
            <div class="form-row">
                <label for="contact-message">Your message</label>
                <textarea id="contact-message" name="message" rows="4" placeholder="Write your message here..." required></textarea>
            </div>
            <button type="submit" class="btn form-submit">Prepare message <span aria-hidden="true">↗</span></button>
            <p class="form-note">Preview form only. Message submission will be connected during backend development.</p>
            <p class="form-feedback" id="form-feedback" role="status" aria-live="polite"></p>
        </form>
    </div>
</section>

<section class="closing-banner">
    <div class="container closing-inner">
        <p>MADE FOR THE LITTLE MOMENTS</p>
        <h2>Make today a little <em>sweeter.</em></h2>
        <a href="#products" class="btn btn-light">Find your favorite <span aria-hidden="true">↗</span></a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>