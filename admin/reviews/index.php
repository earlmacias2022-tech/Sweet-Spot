<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Reviews';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$reviews = [];
$loadError = false;

try {
    $reviewQuery = $pdo->query(
        "SELECT r.id, r.product_id, r.customer_name, r.rating, r.review,
                r.status, r.created_at, p.name AS product_name
         FROM reviews r
         LEFT JOIN products p ON p.id = r.product_id
         ORDER BY r.created_at DESC, r.id DESC"
    );
    $reviews = $reviewQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot review listing failed: ' . $exception->getMessage());
    $loadError = true;
}

function review_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$statusCounts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];

foreach ($reviews as $review) {
    if (isset($statusCounts[$review['status']])) {
        $statusCounts[$review['status']]++;
    }
}

$csrfToken = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= review_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<div class="admin-layout">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/assets/images/sweet-spot-logo.png" alt="Sweet Spot"></a>
            <span class="sidebar-label">BAKERY MANAGEMENT</span>
        </div>
        <div class="sidebar-menu">
            <span class="sidebar-section-label">WORKSPACE</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link"><span class="side-icon">⌂</span> Overview</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="sidebar-link"><span class="side-icon">▧</span> Products</a>
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link"><span class="side-icon">▦</span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link"><span class="side-icon">◉</span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link"><span class="side-icon">✳</span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon">☆</span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#polls" class="sidebar-link"><span class="side-icon">▤</span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#feedback" class="sidebar-link"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= review_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Community <span>/</span> <strong>Reviews</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CUSTOMER EXPERIENCE</span>
                <div class="admin-profile"><span class="profile-avatar"><?= review_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= review_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading reviews-page-heading">
                <div>
                    <p class="admin-kicker">LISTEN TO YOUR CUSTOMERS</p>
                    <h1>Customer <em>reviews.</em></h1>
                    <p>Read customer experiences and manage the reviews shown on your product pages.</p>
                </div>
            </div>

            <div class="review-stat-grid">
                <article class="review-stat-card">
                    <span class="review-stat-icon">☆</span>
                    <div><small>Total reviews</small><strong><?= count($reviews) ?></strong></div>
                </article>
                <article class="review-stat-card">
                    <span class="review-stat-icon review-stat-pending">◷</span>
                    <div><small>Pending review</small><strong><?= $statusCounts['pending'] ?></strong></div>
                </article>
                <article class="review-stat-card">
                    <span class="review-stat-icon review-stat-approved">✓</span>
                    <div><small>Approved</small><strong><?= $statusCounts['approved'] ?></strong></div>
                </article>
                <article class="review-stat-card">
                    <span class="review-stat-icon review-stat-rejected">×</span>
                    <div><small>Rejected</small><strong><?= $statusCounts['rejected'] ?></strong></div>
                </article>
            </div>

            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">
                    <strong>Reviews could not be loaded.</strong>
                    <span>Please check your database connection and confirm that the reviews table exists.</span>
                </div>
            <?php else: ?>
                <section class="review-manager-card">
                    <div class="review-toolbar">
                        <div>
                            <span class="card-overline">CUSTOMER FEEDBACK</span>
                            <h2>All reviews <span>(<?= count($reviews) ?>)</span></h2>
                        </div>
                        <?php if ($reviews): ?>
                            <div class="review-controls">
                                <label class="admin-search review-search">
                                    <span>⌕</span>
                                    <input type="search" id="review-search" placeholder="Search reviews..." aria-label="Search reviews">
                                </label>
                                <select id="review-status-filter" aria-label="Filter reviews by status">
                                    <option value="all">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$reviews): ?>
                        <div class="category-empty-state">
                            <span class="category-empty-icon">☆</span>
                            <h2>No customer reviews yet</h2>
                            <p>When customers submit a review from a product page, it will appear here for you to manage.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="product-table review-table">
                                <thead>
                                    <tr>
                                        <th>PRODUCT</th>
                                        <th>CUSTOMER</th>
                                        <th>RATING</th>
                                        <th>REVIEW</th>
                                        <th>STATUS</th>
                                        <th>SUBMITTED</th>
                                    </tr>
                                </thead>
                                <tbody id="review-table-body">
                                    <?php foreach ($reviews as $review): ?>
                                        <?php
                                            $reviewStatus = in_array($review['status'], ['pending', 'approved', 'rejected'], true)
                                                ? $review['status']
                                                : 'pending';
                                            $customerName = trim((string) ($review['customer_name'] ?? ''));
                                        ?>
                                        <tr data-review-row data-status="<?= review_escape($reviewStatus) ?>">
                                            <td>
                                                <div class="review-product-cell">
                                                    <span class="review-product-icon">▧</span>
                                                    <strong><?= review_escape($review['product_name'] ?? 'Product no longer available') ?></strong>
                                                </div>
                                            </td>
                                            <td class="review-customer"><?= review_escape($customerName !== '' ? $customerName : 'Anonymous') ?></td>
                                            <td>
                                                <span class="review-stars" aria-label="<?= (int) $review['rating'] ?> out of 5 stars">
                                                    <?= str_repeat('★', max(0, min(5, (int) $review['rating']))) ?><span><?= str_repeat('☆', 5 - max(0, min(5, (int) $review['rating']))) ?></span>
                                                </span>
                                            </td>
                                            <td><p class="review-message"><?= review_escape($review['review'] ?? '') ?></p></td>
                                            <td><span class="review-status review-status-<?= review_escape($reviewStatus) ?>"><?= review_escape(ucfirst($reviewStatus)) ?></span></td>
                                            <td class="review-date"><?= $review['created_at'] ? review_escape(date('M j, Y', strtotime($review['created_at']))) : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="category-table-footer">
                            <span id="review-result-count">Showing <?= count($reviews) ?> reviews</span>
                            <span>Review records · Live database</span>
                        </div>
                        <div class="category-no-results" id="review-no-results" hidden>No reviews match your search or filter.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> REVIEW MANAGEMENT</span></footer>
    </main>
</div>

<script>
const sidebarToggle = document.querySelector('#sidebar-toggle');
sidebarToggle?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});

const reviewSearch = document.querySelector('#review-search');
const reviewStatusFilter = document.querySelector('#review-status-filter');
const reviewRows = [...document.querySelectorAll('[data-review-row]')];

function filterReviews() {
    const query = (reviewSearch?.value || '').trim().toLowerCase();
    const selectedStatus = reviewStatusFilter?.value || 'all';
    let visibleCount = 0;

    reviewRows.forEach((row) => {
        const matchesQuery = row.textContent.toLowerCase().includes(query);
        const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
        const visible = matchesQuery && matchesStatus;
        row.hidden = !visible;
        if (visible) visibleCount++;
    });

    const resultCount = document.querySelector('#review-result-count');
    if (resultCount) {
        resultCount.textContent = 'Showing ' + visibleCount + ' of ' + reviewRows.length + ' reviews';
    }

    const noResults = document.querySelector('#review-no-results');
    if (noResults) noResults.hidden = visibleCount !== 0;
}

reviewSearch?.addEventListener('input', filterReviews);
reviewStatusFilter?.addEventListener('change', filterReviews);
</script>
</body>
</html>
