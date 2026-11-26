<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$product = null;
$loadError = false;
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

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

$pageTitle = $product ? $product['name'] : 'Product not found';

function product_detail_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function product_detail_image_url(?string $image): string
{
    return $image ? BASE_URL . '/' . ltrim($image, '/') : '';
}

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
