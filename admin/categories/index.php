<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Categories';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$categories = [];
$loadError = false;

try {
    $categoryQuery = $pdo->query(
        "SELECT c.id, c.name, c.slug, c.description, c.status, c.created_at,
                COUNT(p.id) AS product_count
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.id, c.name, c.slug, c.description, c.status, c.created_at
         ORDER BY c.name ASC"
    );
    $categories = $categoryQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot category listing failed: ' . $exception->getMessage());
    $loadError = true;
}

$activeCount = 0;
$inactiveCount = 0;
foreach ($categories as $category) {
    if ($category['status'] === 'active') {
        $activeCount++;
    } else {
        $inactiveCount++;
    }
}

function category_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$csrfToken = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= category_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if (!empty($_SESSION['category_toast'])): ?>
    <?php $categoryToast = $_SESSION['category_toast']; unset($_SESSION['category_toast']); ?>
    <div class="auth-toast <?= ($categoryToast['type'] ?? '') === 'success' ? 'toast-success' : '' ?> is-visible" role="status" aria-live="polite">
        <span class="auth-toast-icon"><?= ($categoryToast['type'] ?? '') === 'success' ? '✓' : '!' ?></span>
        <span><?= category_escape($categoryToast['message'] ?? 'Category action completed.') ?></span>
        <button type="button" class="auth-toast-close" aria-label="Dismiss notification">×</button>
    </div>
    <?php unset($_SESSION['category_toast']); ?>
<?php endif; ?>
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
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon">▦</span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link"><span class="side-icon">◉</span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link"><span class="side-icon">✳</span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon">☆</span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link"><span class="side-icon">▤</span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/feedback/index.php" class="sidebar-link"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= category_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Workspace <span>/</span> <strong>Categories</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CATEGORY MANAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= category_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= category_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading categories-page-heading">
                <div>
                    <p class="admin-kicker">YOUR BAKERY CATALOG</p>
                    <h1>Product <em>categories.</em></h1>
                    <p>Keep your bakery menu organized into clear collections.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/categories/add.php" class="admin-primary-btn">＋ Add category <span>↗</span></a>
            </div>

            <div class="category-stat-grid">
                <article class="category-stat-card">
                    <span class="category-stat-icon">▦</span>
                    <div><small>Total categories</small><strong><?= count($categories) ?></strong></div>
                </article>
                <article class="category-stat-card">
                    <span class="category-stat-icon category-stat-active">✓</span>
                    <div><small>Active categories</small><strong><?= $activeCount ?></strong></div>
                </article>
                <article class="category-stat-card">
                    <span class="category-stat-icon category-stat-inactive">Ⅱ</span>
                    <div><small>Inactive categories</small><strong><?= $inactiveCount ?></strong></div>
                </article>
            </div>

            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">
                    <strong>Categories could not be loaded.</strong>
                    <span>Please check your database connection and refresh this page.</span>
                </div>
            <?php else: ?>
                <section class="category-manager-card">
                    <div class="category-toolbar">
                        <div>
                            <span class="card-overline">MENU ORGANIZATION</span>
                            <h2>All categories <span>(<?= count($categories) ?>)</span></h2>
                        </div>
                        <?php if ($categories): ?>
                            <label class="admin-search category-search">
                                <span>⌕</span>
                                <input type="search" id="category-search" placeholder="Search categories..." aria-label="Search categories">
                            </label>
                        <?php endif; ?>
                    </div>

                    <?php if (!$categories): ?>
                        <div class="category-empty-state">
                            <span class="category-empty-icon">▦</span>
                            <h2>No categories yet</h2>
                            <p>Your category list is currently empty. Create your first category to start organizing your bakery products.</p>
                            <a href="<?= BASE_URL ?>/admin/categories/add.php" class="admin-primary-btn">＋ Create first category <span>↗</span></a>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="product-table category-table">
                                <thead>
                                    <tr>
                                        <th>CATEGORY</th>
                                        <th>SLUG</th>
                                        <th>PRODUCTS</th>
                                        <th>STATUS</th>
                                        <th>DATE CREATED</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="category-table-body">
                                    <?php foreach ($categories as $category): ?>
                                        <tr data-category-row>
                                            <td>
                                                <div class="category-name-cell">
                                                    <span class="category-row-icon">▦</span>
                                                    <div>
                                                        <strong><?= category_escape($category['name']) ?></strong>
                                                        <small><?= category_escape($category['description'] ?: 'No description added') ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><code class="category-slug"><?= category_escape($category['slug']) ?></code></td>
                                            <td><span class="category-product-count"><?= (int) $category['product_count'] ?></span></td>
                                            <td><span class="category-status <?= $category['status'] === 'active' ? 'is-active' : 'is-inactive' ?>"><i></i><?= category_escape(ucfirst((string) ($category['status'] ?? 'inactive'))) ?></span></td>
                                            <td class="category-date"><?= $category['created_at'] ? category_escape(date('M j, Y', strtotime($category['created_at']))) : '—' ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a class="table-action-btn table-edit-btn" href="<?= BASE_URL ?>/admin/categories/edit.php?id=<?= (int) $category['id'] ?>"><span>✎</span> Edit</a>
                                                    <button class="table-action-btn table-delete-btn" type="button" data-open-category-delete data-category-id="<?= (int) $category['id'] ?>" data-category-name="<?= category_escape($category['name']) ?>" data-product-count="<?= (int) $category['product_count'] ?>"><span>×</span> Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="category-table-footer">
                            <span id="category-result-count">Showing <?= count($categories) ?> categories</span>
                            <span>Category records · Live database</span>
                        </div>
                        <div class="category-no-results" id="category-no-results" hidden>No categories match your search.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> CATEGORY MANAGEMENT</span></footer>
    </main>
</div>
<div class="delete-modal-backdrop" id="category-delete-modal" hidden>
    <section class="delete-modal delete-modal-category" role="dialog" aria-modal="true" aria-labelledby="category-delete-title" aria-describedby="category-delete-description">
        <div class="delete-modal-icon" aria-hidden="true">!</div>
        <span class="delete-modal-eyebrow">PLEASE CONFIRM</span>
        <h2 id="category-delete-title">Delete this category?</h2>
        <p id="category-delete-description">You're about to permanently remove <strong id="delete-category-name">this category</strong>.</p>
        <div class="delete-modal-warning" id="delete-category-impact" hidden>
            <div class="delete-warning-heading"><span>!</span> Products will also be deleted</div>
            <p>This category currently contains <strong id="delete-category-product-count">0</strong> product(s). Deleting this category will also permanently delete every associated product and its uploaded image.</p>
        </div>
        <div class="delete-modal-note" id="delete-category-empty-note" hidden><span>✓</span><p>This category has no associated products.</p></div>
        <div class="delete-modal-note"><span>↳</span><p>This action cannot be undone.</p></div>
        <form id="category-delete-confirm-form" action="<?= BASE_URL ?>/admin/categories/delete.php" method="POST" class="delete-modal-actions">
            <input type="hidden" name="csrf_token" value="<?= category_escape($csrfToken) ?>">
            <input type="hidden" name="id" id="delete-category-id" value="">
            <input type="hidden" name="expected_product_count" id="delete-category-count" value="">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="delete-modal-cancel" data-close-category-delete>Cancel</button>
            <button type="submit" class="delete-modal-confirm" id="delete-category-confirm-label">Yes, delete category</button>
        </form>
    </section>
</div>

<script>
const sidebarToggle = document.querySelector('#sidebar-toggle');
sidebarToggle?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar').classList.toggle('is-open');
});

document.querySelectorAll('.auth-toast').forEach((toast) => {
    toast.querySelector('.auth-toast-close')?.addEventListener('click', () => toast.remove());
    window.setTimeout(() => toast.remove(), 5000);
});

const categoryDeleteModal = document.querySelector('#category-delete-modal');
let categoryDeleteTrigger = null;

const closeCategoryDeleteModal = () => {
    categoryDeleteModal.hidden = true;
    document.body.classList.remove('delete-modal-open');
    categoryDeleteTrigger?.focus();
};

document.querySelectorAll('[data-open-category-delete]').forEach((button) => {
    button.addEventListener('click', () => {
        categoryDeleteTrigger = button;
        const productCount = Number(button.dataset.productCount || 0);
        document.querySelector('#delete-category-name').textContent = button.dataset.categoryName || 'this category';
        document.querySelector('#delete-category-id').value = button.dataset.categoryId || '';
        document.querySelector('#delete-category-count').value = String(productCount);
        document.querySelector('#delete-category-product-count').textContent = String(productCount);
        document.querySelector('#delete-category-impact').hidden = productCount === 0;
        document.querySelector('#delete-category-empty-note').hidden = productCount !== 0;
        document.querySelector('#delete-category-confirm-label').textContent =
            productCount > 0 ? 'Yes, delete everything' : 'Yes, delete category';

        categoryDeleteModal.hidden = false;
        document.body.classList.add('delete-modal-open');
        categoryDeleteModal.querySelector('[data-close-category-delete]').focus();
    });
});

categoryDeleteModal.querySelectorAll('[data-close-category-delete]').forEach((button) => {
    button.addEventListener('click', closeCategoryDeleteModal);
});
categoryDeleteModal.addEventListener('click', (event) => {
    if (event.target === categoryDeleteModal) closeCategoryDeleteModal();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !categoryDeleteModal.hidden) closeCategoryDeleteModal();
});

const categorySearch = document.querySelector('#category-search');
categorySearch?.addEventListener('input', () => {
    const query = categorySearch.value.trim().toLowerCase();
    const rows = [...document.querySelectorAll('[data-category-row]')];
    let visibleCount = 0;

    rows.forEach((row) => {
        const matches = row.textContent.toLowerCase().includes(query);
        row.hidden = !matches;
        if (matches) visibleCount++;
    });

    document.querySelector('#category-result-count').textContent =
        'Showing ' + visibleCount + ' of ' + rows.length + ' categories';
    document.querySelector('#category-no-results').hidden = visibleCount !== 0;
});
</script>
</body>
</html>
