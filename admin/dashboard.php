<?php
require_once __DIR__ . '/../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard';
$loginSuccess = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);

$products = [];
$categories = [];
$productCount = 0;
$categoryCount = 0;
$activeCategoryCount = 0;
$databaseError = false;

try {
    $productQuery = $pdo->query(
        'SELECT p.id, p.name, p.slug, p.category_id, p.price, p.image, p.status,
                p.is_available, p.is_featured, p.created_at, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         ORDER BY p.created_at DESC, p.id DESC'
    );
    $products = $productQuery->fetchAll();
    $productCount = count($products);

    $categoryQuery = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');
    $categories = $categoryQuery->fetchAll();
    $categoryCount = count($categories);

    $activeCategoryQuery = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'");
    $activeCategoryCount = (int) $activeCategoryQuery->fetchColumn();
} catch (PDOException $exception) {
    error_log('Sweet Spot dashboard data loading failed: ' . $exception->getMessage());
    $databaseError = true;
}

$newFeedbackCount = 0;
try {
    $newFeedbackCount = (int) $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();
} catch (PDOException $exception) {
    error_log('Sweet Spot dashboard feedback count failed: ' . $exception->getMessage());
}

$publishedReviewCount = 0;
try {
    $publishedReviewCount = (int) $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
} catch (PDOException $exception) {
    error_log('Sweet Spot dashboard published review count failed: ' . $exception->getMessage());
}

function dashboard_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dashboard_product_image(?string $image): string
{
    return $image ? BASE_URL . '/' . ltrim($image, '/') : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= htmlspecialchars($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<div class="toast-message" id="admin-toast" role="status" aria-live="polite">
    <span id="admin-toast-text"></span>
    <button type="button" id="admin-toast-close" aria-label="Dismiss notification">×</button>
</div>
<div class="admin-layout">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/assets/images/sweet-spot-logo.png" alt="Sweet Spot"></a>
            <span class="sidebar-label">BAKERY MANAGEMENT</span>
        </div>
        <div class="sidebar-menu">
            <span class="sidebar-section-label">WORKSPACE</span>
            <a href="#overview" class="sidebar-link active" data-view="overview"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#home"></use></svg></span> Overview</a>
            <a href="#products" class="sidebar-link" data-view="products"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#package"></use></svg></span> Products <span class="side-count"><?= $productCount ?></span></a>
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#layers"></use></svg></span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#megaphone"></use></svg></span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#tag"></use></svg></span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#star"></use></svg></span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chart"></use></svg></span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/feedback/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#message"></use></svg></span> Feedback <span class="side-count side-count-warm"><?= $newFeedbackCount ?></span></a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#sparkles"></use></svg></span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#arrow-up-right"></use></svg> <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form" id="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="sidebar-logout"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#log-out"></use></svg> <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#menu"></use></svg></button>
                <div class="breadcrumb">Workspace <span>/</span> <strong id="breadcrumb-current">Overview</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> ADMIN WORKSPACE</span>
                <button class="topbar-icon" type="button" aria-label="Notifications"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#bell"></use></svg><b></b></button>
                <div class="admin-profile"><span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['admin_username'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><div><strong><?= htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') ?></strong><small>Administrator</small></div><span class="profile-chevron"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chevron-down"></use></svg></span></div>
            </div>
        </header>

        <div class="admin-content">
            <section class="admin-view active" id="view-overview">
                <div class="page-heading">
                    <div><p class="admin-kicker"><?= strtoupper(date('l, F j, Y')) ?></p><h1>Good morning, <em>Sweet Spot.</em></h1><p>Here's what's happening with your bakery today.</p></div>
                    <a href="#products" class="admin-primary-btn" data-view-link="products">Manage products <span>↗</span></a>
                </div>
                <?php if ($databaseError): ?><div class="form-preview-banner"><span>!</span><p><strong>Dashboard data could not be fully loaded.</strong> Please check your database connection and refresh this page.</p></div><?php endif; ?>

                <div class="stat-grid">
                    <article class="stat-card"><div class="stat-top"><span>Total products</span><span class="stat-icon">▧</span></div><strong id="stat-products"><?= $productCount ?></strong><small><span class="stat-neutral">↗</span> Items in your menu</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Categories</span><span class="stat-icon">▦</span></div><strong><?= $categoryCount ?></strong><small><?= $activeCategoryCount ?> active categories</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Published reviews</span><span class="stat-icon">☆</span></div><strong><?= $publishedReviewCount ?></strong><small>Approved customer reviews</small></article>
                    <article class="stat-card"><div class="stat-top"><span>New feedback</span><span class="stat-icon">✉</span></div><strong><?= $newFeedbackCount ?></strong><small><a href="<?= BASE_URL ?>/admin/feedback/index.php">Open customer inbox ↗</a></small></article>
                </div>

                <div class="dashboard-grid">
                    <section class="admin-card recent-products">
                        <div class="card-heading"><div><span class="card-overline">YOUR CATALOG</span><h2>Recently added products</h2></div><a href="#products" class="subtle-link" data-view-link="products">View all ↗</a></div>
                        <div class="mini-product-list" id="recent-product-list">
                            <?php if ($products): ?>
                            <?php foreach (array_slice($products, 0, 4) as $product): ?>
                            <div class="mini-product-row"><?php if (!empty($product['image'])): ?><img src="<?= dashboard_escape(dashboard_product_image($product['image'])) ?>" alt=""><?php else: ?><span class="category-row-icon">▧</span><?php endif; ?><div class="mini-product-info"><strong><?= dashboard_escape($product['name']) ?></strong><small><?= dashboard_escape($product['category_name'] ?? 'Uncategorized') ?></small></div><b>₱<?= number_format((float) $product['price'], 2) ?></b></div>
                            <?php endforeach; ?>
                            <?php else: ?><p class="category-no-results">No products have been added yet.</p><?php endif; ?>
                        </div>
                    </section>
                    <section class="admin-card quick-actions">
                        <div class="card-heading"><div><span class="card-overline">SHORTCUTS</span><h2>Quick actions</h2></div></div>
                        <a class="quick-action" href="<?= BASE_URL ?>/admin/products/add.php"><span class="quick-icon">＋</span><span><strong>Add a product</strong><small>Create a new menu entry</small></span><b>→</b></a>
                        <a class="quick-action" href="<?= BASE_URL ?>/admin/categories/index.php"><span class="quick-icon">▦</span><span><strong>Manage categories</strong><small>Keep your menu organized</small></span><b>→</b></a>
                        <a class="quick-action" href="<?= BASE_URL ?>/admin/announcements/add.php"><span class="quick-icon">◉</span><span><strong>Post an announcement</strong><small>Share a bakery update</small></span><b>→</b></a>
                    </section>
                </div>

            </section>

            <section class="admin-view" id="view-products">
                <div class="page-heading products-page-heading"><div><p class="admin-kicker">YOUR BAKERY CATALOG</p><h1>Products <em>& menu.</em></h1><p>Add, update, and organize the treats shown on your website.</p></div><a href="<?= BASE_URL ?>/admin/products/add.php" class="admin-primary-btn">＋ Add product</a></div>
                <?php if ($databaseError): ?><div class="form-preview-banner"><span>!</span><p><strong>Product data is unavailable.</strong> Check your database connection and refresh the page.</p></div><?php endif; ?>
                <div class="product-manager-card">
                    <div class="manager-toolbar"><div class="manager-title"><span class="card-overline">MENU INVENTORY</span><h2>All products <span id="product-count">(<?= $productCount ?>)</span></h2></div><div class="manager-controls"><div class="admin-search"><span>⌕</span><input id="product-search" type="search" placeholder="Search products..." aria-label="Search products"></div><select id="category-filter" aria-label="Filter by category"><option value="all">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= dashboard_escape($category['name']) ?></option><?php endforeach; ?></select></div></div>
                    <?php if (!$products): ?><div class="category-empty-state"><span class="category-empty-icon">▧</span><h2>No products yet</h2><p>Products you add will appear here automatically.</p><a href="<?= BASE_URL ?>/admin/products/add.php" class="admin-primary-btn">＋ Add your first product</a></div><?php else: ?><div class="table-scroll"><table class="product-table"><thead><tr><th>PRODUCT</th><th>CATEGORY</th><th>PRICE</th><th>PUBLICATION / AVAILABILITY</th><th>ACTION</th></tr></thead><tbody id="product-table-body">
                    <?php foreach ($products as $product): ?>
                    <?php
                        $isPublished = $product['status'] === 'published';
                        $isAvailable = (int) $product['is_available'] === 1;
                        $statusClass = !$isAvailable ? 'unavailable' : ($isPublished ? 'available' : 'low-stock');
                        $statusText = ucfirst($product['status']) . ($isAvailable ? ' · Available' : ' · Unavailable');
                    ?>
                    <tr data-category-id="<?= (int) ($product['category_id'] ?? 0) ?>">
                        <td><div class="table-product"><?php if (!empty($product['image'])): ?><img src="<?= dashboard_escape(dashboard_product_image($product['image'])) ?>" alt=""><?php else: ?><span class="category-row-icon">▧</span><?php endif; ?><div><strong><?= dashboard_escape($product['name']) ?></strong><small><?= dashboard_escape($product['slug']) ?></small></div></div></td>
                        <td><span class="category-pill"><?= dashboard_escape($product['category_name'] ?? 'Uncategorized') ?></span></td>
                        <td class="table-price">₱<?= number_format((float) $product['price'], 2) ?></td>
                        <td><span class="product-status <?= $statusClass ?>"><i></i><?= dashboard_escape($statusText) ?></span></td>
                        <td>
                            <div class="table-actions">
                                <a class="table-action-btn table-edit-btn" href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= (int) $product['id'] ?>"><span>✎</span> Edit</a>
                                <button class="table-action-btn table-delete-btn" type="button" data-open-delete-modal data-product-id="<?= (int) $product['id'] ?>" data-product-name="<?= dashboard_escape($product['name']) ?>"><span>×</span> Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <div class="table-footer"><span id="table-result-count">Showing <?= $productCount ?> products</span><span>Live database records</span></div><?php endif; ?>
                </div>
            </section>

        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> DATABASE CONNECTED</span></footer>
    </main>
</div>



<?php
$productToast = $_SESSION['product_toast'] ?? null;
unset($_SESSION['product_toast']);
?>
<script>
document.querySelector('#admin-toast-close')?.addEventListener('click', () => {
    document.querySelector('#admin-toast')?.classList.remove('show');
});
</script>

<?php if ($productToast): ?>
<script>
window.addEventListener("DOMContentLoaded", () => {
    const toast = document.querySelector("#admin-toast");
    toast.querySelector('#admin-toast-text').textContent = <?= json_encode($productToast['message'] ?? 'Product updated.', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    toast.classList.add("show");
    <?php if (($productToast['type'] ?? '') === 'success'): ?>
    toast.classList.add("toast-success");
    <?php endif; ?>
    window.setTimeout(() => toast.classList.remove("show"), 5000);
});
</script>
<?php endif; ?>

<?php if ($loginSuccess !== ''): ?>
<script>
window.addEventListener("DOMContentLoaded", () => {
    const toast = document.querySelector("#admin-toast");
    toast.querySelector('#admin-toast-text').textContent = <?= json_encode($loginSuccess, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    toast.classList.add("show", "toast-success");
    window.setTimeout(() => toast.classList.remove("show"), 5000);
});
</script>
<?php endif; ?>

<div class="delete-modal-backdrop" id="product-delete-modal" hidden>
    <section class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="product-delete-title" aria-describedby="product-delete-description">
        <div class="delete-modal-icon" aria-hidden="true">!</div>
        <span class="delete-modal-eyebrow">PLEASE CONFIRM</span>
        <h2 id="product-delete-title">Delete this product?</h2>
        <p id="product-delete-description">You're about to permanently remove <strong id="delete-product-name">this product</strong> from your bakery catalog.</p>
        <div class="delete-modal-note"><span>↳</span><p>This action cannot be undone. The product's uploaded image will also be removed.</p></div>
        <form id="product-delete-confirm-form" action="<?= BASE_URL ?>/admin/products/delete.php" method="POST" class="delete-modal-actions">
            <input type="hidden" name="csrf_token" value="<?= dashboard_escape(admin_csrf_token()) ?>">
            <input type="hidden" name="id" id="delete-product-id" value="">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="delete-modal-cancel" data-close-delete-modal>Keep product</button>
            <button type="submit" class="delete-modal-confirm">Yes, delete product</button>
        </form>
    </section>
</div>
<script>
(() => {
    const modal = document.querySelector('#product-delete-modal');
    const name = document.querySelector('#delete-product-name');
    const id = document.querySelector('#delete-product-id');
    let previousFocus = null;

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('delete-modal-open');
        previousFocus?.focus();
    };

    document.querySelectorAll('[data-open-delete-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            previousFocus = button;
            name.textContent = button.dataset.productName || 'this product';
            id.value = button.dataset.productId || '';
            modal.hidden = false;
            document.body.classList.add('delete-modal-open');
            modal.querySelector('[data-close-delete-modal]').focus();
        });
    });

    modal.querySelectorAll('[data-close-delete-modal]').forEach((button) => button.addEventListener('click', closeModal));
    modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>

<div class="delete-modal-backdrop" id="signout-confirm-modal" hidden>
    <section class="delete-modal signout-modal" role="dialog" aria-modal="true" aria-labelledby="signout-modal-title" aria-describedby="signout-modal-description">
        <div class="delete-modal-icon signout-modal-icon" aria-hidden="true">↗</div>
        <span class="delete-modal-eyebrow">LEAVING YOUR WORKSPACE</span>
        <h2 id="signout-modal-title">Sign out of Sweet Spot?</h2>
        <p id="signout-modal-description">You will be returned to the admin login page. You can sign in again whenever you need to manage the bakery website.</p>
        <div class="delete-modal-actions">
            <button type="button" class="delete-modal-cancel" data-close-signout-modal>Stay signed in</button>
            <button type="button" class="signout-modal-confirm" id="confirm-signout">Yes, sign out</button>
        </div>
    </section>
</div>
<script>
(() => {
    const form = document.querySelector('#sidebar-logout-form');
    const modal = document.querySelector('#signout-confirm-modal');
    const confirmButton = document.querySelector('#confirm-signout');
    if (!form || !modal || !confirmButton) return;

    let previousFocus = null;

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('delete-modal-open');
        previousFocus?.focus();
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        previousFocus = form.querySelector('button[type="submit"]');
        modal.hidden = false;
        document.body.classList.add('delete-modal-open');
        modal.querySelector('[data-close-signout-modal]').focus();
    });

    modal.querySelectorAll('[data-close-signout-modal]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    confirmButton.addEventListener('click', () => {
        confirmButton.disabled = true;
        form.submit();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>