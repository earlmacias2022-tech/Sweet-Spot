<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$product = null;
$loadError = false;
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$reviewErrors = [];
$reviewName = '';
$reviewText = '';
$reviewRating = 5;
$reviewSubmitted = isset($_GET['review']) && $_GET['review'] === 'submitted';
$approvedReviews = [];
$reviewCount = 0;
$averageRating = 0;
$activePoll = null;
$pollOptions = [];

if ($productId) {
    try {
        $productQuery = $pdo->prepare(
            "SELECT p.id, p.name, p.description, p.price, p.image, p.is_featured,
                    c.name AS category_name
             FROM products p
             INNER JOIN categories c
                 ON c.id = p.category_id
                AND c.status = 'active'
             WHERE p.id = :id
               AND p.status = 'published'
               AND p.is_available = 1
             LIMIT 1"
        );
        $productQuery->execute(['id' => $productId]);
        $product = $productQuery->fetch() ?: null;
    } catch (PDOException $exception) {
        error_log('Sweet Spot product detail loading failed: ' . $exception->getMessage());
        $loadError = true;
    }
}

function product_detail_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function product_detail_image_url(?string $image): string
{
    return $image ? BASE_URL . '/' . ltrim($image, '/') : '';
}

if ($product && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewName = trim((string) ($_POST['customer_name'] ?? ''));
    $reviewText = trim((string) ($_POST['review'] ?? ''));
    $reviewRating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
        $reviewErrors[] = 'Your session expired. Please refresh the page and try again.';
    }

    if ($reviewName !== '' && mb_strlen($reviewName) > 100) {
        $reviewErrors[] = 'Your name must be 100 characters or fewer.';
    }

    if (!is_int($reviewRating) || $reviewRating < 1 || $reviewRating > 5) {
        $reviewErrors[] = 'Please choose a rating from 1 to 5 stars.';
        $reviewRating = 5;
    }

    if ($reviewText === '') {
        $reviewErrors[] = 'Please write a short review before submitting.';
    } elseif (mb_strlen($reviewText) > 5000) {
        $reviewErrors[] = 'Your review must be 5,000 characters or fewer.';
    }

    if (!$reviewErrors) {
        try {
            $insertReview = $pdo->prepare(
                "INSERT INTO reviews (product_id, customer_name, rating, review, status)
                 VALUES (:product_id, :customer_name, :rating, :review, 'pending')"
            );
            $insertReview->execute([
                'product_id' => (int) $product['id'],
                'customer_name' => $reviewName !== '' ? $reviewName : null,
                'rating' => $reviewRating,
                'review' => $reviewText,
            ]);

            header('Location: ' . BASE_URL . '/product.php?id=' . (int) $product['id'] . '&review=submitted#reviews');
            exit;
        } catch (PDOException $exception) {
            error_log('Sweet Spot review submission failed: ' . $exception->getMessage());
            $reviewErrors[] = 'We could not submit your review right now. Please try again.';
        }
    }
}

if ($product) {
    try {
        $reviewsQuery = $pdo->prepare(
            "SELECT customer_name, rating, review, created_at
             FROM reviews
             WHERE product_id = :product_id AND status = 'approved'
             ORDER BY created_at DESC, id DESC"
        );
        $reviewsQuery->execute(['product_id' => (int) $product['id']]);
        $approvedReviews = $reviewsQuery->fetchAll();
        $reviewCount = count($approvedReviews);

        if ($reviewCount > 0) {
            $averageRating = array_sum(array_column($approvedReviews, 'rating')) / $reviewCount;
        }
    } catch (PDOException $exception) {
        error_log('Sweet Spot approved reviews loading failed: ' . $exception->getMessage());
    }
}


if ($product) {
    try {
        $pollNow = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $pollQuery = $pdo->prepare(
            "SELECT id, question, description
             FROM polls
             WHERE product_id = :product_id
               AND status = 'active'
               AND (start_date IS NULL OR start_date <= :poll_start)
               AND (end_date IS NULL OR end_date >= :poll_end)
             ORDER BY created_at DESC, id DESC
             LIMIT 1"
        );
        $pollQuery->execute([
            'product_id' => (int) $product['id'],
            'poll_start' => $pollNow,
            'poll_end' => $pollNow,
        ]);
        $activePoll = $pollQuery->fetch() ?: null;

        if ($activePoll) {
            $pollOptionsQuery = $pdo->prepare(
                "SELECT id, option_text
                 FROM poll_options
                 WHERE poll_id = :poll_id
                 ORDER BY id ASC"
            );
            $pollOptionsQuery->execute(['poll_id' => (int) $activePoll['id']]);
            $pollOptions = $pollOptionsQuery->fetchAll();
        }
    } catch (PDOException $exception) {
        error_log('Sweet Spot active product poll loading failed: ' . $exception->getMessage());
        $activePoll = null;
        $pollOptions = [];
    }
}

$pageTitle = $product ? $product['name'] : 'Product not found';

if (!$product) {
    http_response_code($loadError ? 500 : 404);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="product-detail-section">
    <div class="container">
        <nav class="product-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/index.php">Home</a>
            <span aria-hidden="true">/</span>
            <a href="<?= BASE_URL ?>/index.php#products">Our menu</a>
            <?php if ($product): ?>
                <span aria-hidden="true">/</span>
                <span><?= product_detail_escape($product['name']) ?></span>
            <?php endif; ?>
        </nav>

        <?php if ($product): ?>
            <div class="product-detail-grid">
                <div class="product-detail-visual">
                    <?php if (!empty($product['image'])): ?>
                        <img
                            src="<?= product_detail_escape(product_detail_image_url($product['image'])) ?>"
                            alt="<?= product_detail_escape($product['name']) ?>"
                            class="product-detail-image"
                        >
                    <?php else: ?>
                        <div class="product-detail-placeholder" role="img" aria-label="No product image available">✧</div>
                    <?php endif; ?>

                    <?php if ((int) $product['is_featured'] === 1): ?>
                        <span class="product-detail-featured">Featured</span>
                    <?php endif; ?>
                </div>

                <div class="product-detail-content">
                    <p class="product-detail-category"><?= product_detail_escape($product['category_name']) ?></p>
                    <h1><?= product_detail_escape($product['name']) ?></h1>
                    <p class="product-detail-price">₱<?= number_format((float) $product['price'], 2) ?></p>
                    <div class="product-detail-divider"></div>
                    <p class="product-detail-description"><?= product_detail_escape($product['description'] ?: 'A freshly prepared favorite from Sweet Spot.') ?></p>

                    <div class="product-detail-actions">
                        <a href="<?= BASE_URL ?>/index.php#contact" class="btn">Get in touch <span aria-hidden="true">↗</span></a>
                        <a href="<?= BASE_URL ?>/index.php#products" class="product-back-link">← Back to menu</a>
                    </div>
                </div>
            </div>

            <?php if ($activePoll && $pollOptions): ?>
                <section class="product-poll-section" aria-labelledby="product-poll-title">
                    <div class="product-poll-heading">
                        <p class="eyebrow"><span></span> YOUR OPINION MATTERS</p>
                        <h2 id="product-poll-title"><?= product_detail_escape($activePoll['question']) ?></h2>
                        <?php if (!empty($activePoll['description'])): ?>
                            <p><?= nl2br(product_detail_escape($activePoll['description'])) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="product-poll-options" aria-label="Poll answer choices">
                        <?php foreach ($pollOptions as $pollOption): ?>
                            <div class="product-poll-option">
                                <span aria-hidden="true">✧</span>
                                <p><?= product_detail_escape($pollOption['option_text']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="product-poll-note">A quick customer poll for this product. Voting will be available soon.</p>
                </section>
            <?php endif; ?>


            <section class="reviews-section" id="reviews">
                <div class="reviews-heading">
                    <div>
                        <p class="eyebrow"><span></span> CUSTOMER NOTES</p>
                        <h2>Reviews for <em><?= product_detail_escape($product['name']) ?>.</em></h2>
                    </div>
                    <div class="reviews-summary">
                        <?php if ($reviewCount > 0): ?>
                            <strong><?= number_format($averageRating, 1) ?> <span aria-label="out of 5 stars">★</span></strong>
                            <small><?= $reviewCount ?> approved <?= $reviewCount === 1 ? 'review' : 'reviews' ?></small>
                        <?php else: ?>
                            <strong>—</strong>
                            <small>No reviews yet</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($reviewSubmitted): ?>
                    <div class="review-alert review-alert-success" role="status">
                        Thank you! Your review has been submitted and will appear after it has been approved.
                    </div>
                <?php endif; ?>

                <?php if ($reviewErrors): ?>
                    <div class="review-alert review-alert-error" role="alert">
                        <ul>
                            <?php foreach ($reviewErrors as $error): ?>
                                <li><?= product_detail_escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="reviews-layout">
                    <div class="reviews-list">
                        <?php if ($approvedReviews): ?>
                            <?php foreach ($approvedReviews as $approvedReview): ?>
                                <article class="review-card">
                                    <div class="review-card-top">
                                        <strong><?= product_detail_escape($approvedReview['customer_name'] ?: 'Guest') ?></strong>
                                        <span class="review-stars" aria-label="<?= (int) $approvedReview['rating'] ?> out of 5 stars">
                                            <?= str_repeat('★', (int) $approvedReview['rating']) ?><?= str_repeat('☆', 5 - (int) $approvedReview['rating']) ?>
                                        </span>
                                    </div>
                                    <p><?= nl2br(product_detail_escape($approvedReview['review'])) ?></p>
                                    <time datetime="<?= product_detail_escape(date('Y-m-d', strtotime($approvedReview['created_at']))) ?>">
                                        <?= date('F j, Y', strtotime($approvedReview['created_at'])) ?>
                                    </time>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="reviews-empty">
                                <span aria-hidden="true">✧</span>
                                <h3>Be the first to share a review.</h3>
                                <p>Have you tried this treat? Let others know what you think.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form class="review-form" action="<?= BASE_URL ?>/product.php?id=<?= (int) $product['id'] ?>#reviews" method="POST">
                        <div class="review-form-heading">
                            <span>YOUR EXPERIENCE</span>
                            <h3>Write a review</h3>
                            <p>Your review will be checked by our team before it appears here.</p>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= product_detail_escape(admin_csrf_token()) ?>">

                        <div class="review-field">
                            <label for="review-name">Your name <span>(optional)</span></label>
                            <input
                                type="text"
                                id="review-name"
                                name="customer_name"
                                maxlength="100"
                                value="<?= product_detail_escape($reviewName) ?>"
                                placeholder="How should we call you?"
                            >
                        </div>

                        <fieldset class="review-field review-rating-field">
                            <legend>Your rating</legend>
                            <div class="rating-options">
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <label>
                                        <input type="radio" name="rating" value="<?= $star ?>" <?= $reviewRating === $star ? 'checked' : '' ?>>
                                        <span><?= $star ?> ★</span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </fieldset>

                        <div class="review-field">
                            <label for="review-text">Your review</label>
                            <textarea
                                id="review-text"
                                name="review"
                                rows="5"
                                maxlength="5000"
                                required
                                placeholder="Tell us what you liked about this product..."
                            ><?= product_detail_escape($reviewText) ?></textarea>
                        </div>

                        <button type="submit" class="btn review-submit">Submit review <span aria-hidden="true">↗</span></button>
                    </form>
                </div>
            </section>
        <?php else: ?>
            <div class="product-not-found">
                <span aria-hidden="true">✧</span>
                <h1><?= $loadError ? 'Something went wrong.' : 'This treat is unavailable.' ?></h1>
                <p>
                    <?= $loadError
                        ? 'We could not load this product right now. Please try again later.'
                        : 'The product may have been removed or is no longer available on our menu.' ?>
                </p>
                <a href="<?= BASE_URL ?>/index.php#products" class="btn">Back to our menu <span aria-hidden="true">↗</span></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
