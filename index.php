<?php
$pageTitle = 'Freshly Baked Happiness';



require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$feedbackErrors = [];
$feedbackName = '';
$feedbackEmail = '';
$feedbackSubject = '';
$feedbackMessage = '';
$feedbackFlash = $_SESSION['feedback_flash'] ?? null;
unset($_SESSION['feedback_flash']);
$feedbackCsrfToken = admin_csrf_token();

function feedback_redirect(string $type, string $message): void
{
    $_SESSION['feedback_flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . BASE_URL . '/index.php#contact');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'feedback') {
    $feedbackName = trim((string) ($_POST['name'] ?? ''));
    $feedbackEmail = trim((string) ($_POST['email'] ?? ''));
    $feedbackSubject = trim((string) ($_POST['subject'] ?? ''));
    $feedbackMessage = trim((string) ($_POST['message'] ?? ''));
    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!admin_verify_csrf_token(is_string($submittedToken) ? $submittedToken : null)) {
        $feedbackErrors[] = 'Your session expired. Please refresh the page and try again.';
    }
    if ($feedbackName === '' || mb_strlen($feedbackName) > 100) {
        $feedbackErrors[] = 'Please enter your name (up to 100 characters).';
    }
    if (!filter_var($feedbackEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($feedbackEmail) > 150) {
        $feedbackErrors[] = 'Please enter a valid email address (up to 150 characters).';
    }
    if (mb_strlen($feedbackSubject) > 180) {
        $feedbackErrors[] = 'Your subject must be 180 characters or fewer.';
    }
    if ($feedbackMessage === '' || mb_strlen($feedbackMessage) > 10000) {
        $feedbackErrors[] = 'Please enter a message of up to 10,000 characters.';
    }

    if (!$feedbackErrors) {
        try {
            $insertFeedback = $pdo->prepare(
                "INSERT INTO feedback (customer_name, email, subject, message, status)
                 VALUES (:customer_name, :email, :subject, :message, 'new')"
            );
            $insertFeedback->execute([
                'customer_name' => $feedbackName,
                'email' => $feedbackEmail,
                'subject' => $feedbackSubject !== '' ? $feedbackSubject : null,
                'message' => $feedbackMessage,
            ]);
            feedback_redirect('success', 'Thank you! Your message has been sent to Sweet Spot.');
        } catch (PDOException $exception) {
            error_log('Sweet Spot customer feedback submission failed: ' . $exception->getMessage());
            $feedbackErrors[] = 'We could not send your message right now. Please try again.';
        }
    }
}

$products = [];
$categories = [];
$announcements = [];
$promotions = [];
$catalogError = false;
$announcementError = false;
$promotionError = false;

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

try {
    $announcementNow = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $announcementQuery = $pdo->prepare(
        "SELECT id, title, content, created_at
         FROM announcements
         WHERE status = 'published'
           AND (start_date IS NULL OR start_date <= :announcement_start)
           AND (end_date IS NULL OR end_date >= :announcement_end)
         ORDER BY created_at DESC, id DESC
         LIMIT 3"
    );
    $announcementQuery->execute([
        'announcement_start' => $announcementNow,
        'announcement_end' => $announcementNow,
    ]);
    $announcements = $announcementQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot public announcements loading failed: ' . $exception->getMessage());
    $announcementError = true;
}

try {
    $promotionNow = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $promotionQuery = $pdo->prepare(
        "SELECT id, title, description, discount_value, start_date, end_date
         FROM promotions
         WHERE status = 'active'
           AND (start_date IS NULL OR start_date <= :promotion_start)
           AND (end_date IS NULL OR end_date >= :promotion_end)
         ORDER BY created_at DESC, id DESC
         LIMIT 3"
    );
    $promotionQuery->execute([
        'promotion_start' => $promotionNow,
        'promotion_end' => $promotionNow,
    ]);
    $promotions = $promotionQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot public promotions loading failed: ' . $exception->getMessage());
    $promotionError = true;
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
            <h1>Find your next<br><span class="accent-text">favorite treat.</span></h1>
            <p class="hero-description">
                Explore bakery products, discover treats, and keep up with offers and updates—all in one place.
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
                <h2>Explore the menu<br><span class="accent-text">and find a favorite.</span></h2>
            </div>
            <p class="section-intro">Browse the available products and find something that suits your taste.</p>
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
            <span class="sample-note">Browse by category</span>
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
                            <p><?= catalog_escape($product['description'] ?: 'A delicious option from this bakery.') ?></p>
                            <a href="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>" class="product-link">
                                View product details <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="catalog-disclaimer">Only currently available products are shown here.</p>
        <?php endif; ?>    </div>
</section>


<section class="promotions-section" id="promotions">
    <div class="container">
        <div class="promotions-heading">
            <div>
                <h2>Special treats<br><span class="accent-text">and little offers.</span></h2>
            </div>
            <p class="promotions-intro">Take a look at our current offers and enjoy a little extra sweetness from Sweet Spot.</p>
        </div>

        <?php if ($promotionError): ?>
            <p class="promotion-empty">Our special offers are temporarily unavailable. Please check back soon.</p>
        <?php elseif (!$promotions): ?>
            <p class="promotion-empty">There are no active promotions at the moment. Keep an eye out for our next special offer.</p>
        <?php else: ?>
            <div class="promotion-grid">
                <?php foreach ($promotions as $promotion): ?>
                    <article class="customer-promotion-card">
                        <div class="customer-promotion-top">
                            <span class="customer-promotion-label">SPECIAL OFFER</span>
                            <?php if ($promotion['discount_value'] !== null && (float) $promotion['discount_value'] > 0): ?>
                                <span class="customer-promotion-discount">
                                    <?= catalog_escape(rtrim(rtrim(number_format((float) $promotion['discount_value'], 2, '.', ''), '0'), '.') . '% OFF') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3><?= catalog_escape($promotion['title']) ?></h3>
                        <?php if (!empty($promotion['description'])): ?>
                            <p><?= nl2br(catalog_escape($promotion['description'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($promotion['end_date'])): ?>
                            <span class="customer-promotion-validity">
                                Offer ends <?= catalog_escape(date('M j, Y', strtotime($promotion['end_date']))) ?>
                            </span>
                        <?php else: ?>
                            <span class="customer-promotion-validity">While this offer is available</span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>


<section class="announcements-section" id="announcements">
    <div class="container">
        <div class="announcements-heading">
            <div>
                <h2>Bakery news<br><span class="accent-text">and updates.</span></h2>
            </div>
            <p class="announcements-intro">Find useful notices, reminders, and updates shared by the bakery.</p>
        </div>

        <?php if ($announcementError): ?>
            <p class="announcement-empty">Our latest updates are temporarily unavailable. Please check back soon.</p>
        <?php elseif (!$announcements): ?>
            <p class="announcement-empty">There are no announcements to share right now. Please check back for future updates.</p>
        <?php else: ?>
            <div class="announcement-grid">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="announcement-card">
                        <div class="announcement-card-meta">
                            <span class="announcement-card-icon" aria-hidden="true">◉</span>
                            <time datetime="<?= catalog_escape(date('Y-m-d', strtotime($announcement['created_at']))) ?>">
                                <?= catalog_escape(date('M j, Y', strtotime($announcement['created_at']))) ?>
                            </time>
                        </div>
                        <h3><?= catalog_escape($announcement['title']) ?></h3>
                        <p><?= nl2br(catalog_escape($announcement['content'])) ?></p>
                        <span class="announcement-card-line" aria-hidden="true"></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="story-section" id="about">
    <div class="container story-grid">
        <div class="story-image">
            <img src="<?= BASE_URL ?>/assets/images/hero-bg-image.jpg" alt="A warm bakery scene with freshly prepared baked goods" loading="lazy">
            <div class="story-image-caption"><span>THE SWEET SPOT WAY</span><strong>Simple joys, baked daily.</strong></div>
        </div>
        <div class="story-content">
            <h2>A simpler way<br><span class="accent-text">to explore bakeries.</span></h2>
            <p class="story-lead">Sweet Spot helps bakery owners manage their business while giving customers a simple way to explore available products and updates.</p>
            <p>Browse available products, discover bakery updates, and keep track of current offers—all in one convenient place.</p>
            <a href="#contact" class="btn btn-dark">Say hello <span aria-hidden="true">↗</span></a>
            <div class="story-signature">A better way to discover local treats <span>♡</span></div>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container">
        <div class="center-heading">
            <h2>Good things take <span class="accent-text">a little care.</span></h2>
            <p>A simple way to browse bakery offerings and stay up to date.</p>
        </div>
        <div class="values-grid">
            <article class="value-card">
                <span class="value-number">01</span>
                <span class="value-icon">✳</span>
                <h3>Easy to explore</h3>
                <p>Browse available bakery products in one convenient place.</p>
            </article>
            <article class="value-card">
                <span class="value-number">02</span>
                <span class="value-icon">♡</span>
                <h3>Discover new favorites</h3>
                <p>Explore different treats and find something for any occasion.</p>
            </article>
            <article class="value-card">
                <span class="value-number">03</span>
                <span class="value-icon">✧</span>
                <h3>Offers and updates</h3>
                <p>Keep up with bakery announcements and current promotions.</p>
            </article>
        </div>
    </div>
</section>

<section class="contact-section" id="contact">
    <div class="container contact-grid">
        <div class="contact-copy">
            <h2>Have a question<br>or a <span class="accent-text">special request?</span></h2>
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

        <form class="contact-form" id="contact-form" method="POST" action="<?= BASE_URL ?>/index.php#contact">
            <input type="hidden" name="form_type" value="feedback">
            <input type="hidden" name="csrf_token" value="<?= catalog_escape($feedbackCsrfToken) ?>">
            <div class="form-heading">
                <span>WE'RE ALL EARS</span>
                <h3>Send us a little note.</h3>
            </div>
            <?php if ($feedbackFlash): ?>
                <p class="form-feedback <?= ($feedbackFlash['type'] ?? '') === 'success' ? 'is-success' : 'is-error' ?>" role="status" aria-live="polite"><?= catalog_escape($feedbackFlash['message'] ?? '') ?></p>
            <?php endif; ?>
            <?php if ($feedbackErrors): ?>
                <div class="form-feedback is-error" role="alert">
                    <?php foreach ($feedbackErrors as $feedbackError): ?>
                        <p><?= catalog_escape($feedbackError) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="form-row">
                <label for="contact-name">Your name</label>
                <input type="text" id="contact-name" name="name" maxlength="100" placeholder="How should we call you?" value="<?= catalog_escape($feedbackName) ?>" required>
            </div>
            <div class="form-row">
                <label for="contact-email">Email address</label>
                <input type="email" id="contact-email" name="email" maxlength="150" placeholder="you@example.com" value="<?= catalog_escape($feedbackEmail) ?>" required>
            </div>
            <div class="form-row">
                <label for="contact-subject">What is this about?</label>
                <input type="text" id="contact-subject" name="subject" maxlength="180" placeholder="Product inquiry, question, etc." value="<?= catalog_escape($feedbackSubject) ?>">
            </div>
            <div class="form-row">
                <label for="contact-message">Your message</label>
                <textarea id="contact-message" name="message" rows="4" maxlength="10000" placeholder="Write your message here..." required><?= catalog_escape($feedbackMessage) ?></textarea>
            </div>
            <button type="submit" class="btn form-submit">Send message <span aria-hidden="true">↗</span></button>
        </form>
    </div>
</section>

<section class="closing-banner">
    <div class="container closing-inner">
        <p>MADE FOR THE LITTLE MOMENTS</p>
        <h2>Make today a little <span class="accent-text">sweeter.</span></h2>
        <a href="#products" class="btn btn-light">Find your favorite <span aria-hidden="true">↗</span></a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>