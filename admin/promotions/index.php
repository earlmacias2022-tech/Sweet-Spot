<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Promotions';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$promotions = [];
$loadError = false;

try {
    $promotionQuery = $pdo->query(
        'SELECT id, title, description, discount_value, status, start_date, end_date, created_at, updated_at
         FROM promotions
         ORDER BY created_at DESC, id DESC'
    );
    $promotions = $promotionQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot promotion listing failed: ' . $exception->getMessage());
    $loadError = true;
}

function promotion_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function promotion_effective_status(array $promotion): string
{
    if ($promotion['status'] === 'draft' || $promotion['status'] === 'archived' || $promotion['status'] === 'expired') {
        return $promotion['status'];
    }

    $now = new DateTimeImmutable();

    if (!empty($promotion['start_date'])) {
        $startDate = new DateTimeImmutable($promotion['start_date']);
        if ($startDate > $now) {
            return 'scheduled';
        }
    }

    if (!empty($promotion['end_date'])) {
        $endDate = new DateTimeImmutable($promotion['end_date']);
        if ($endDate < $now) {
            return 'expired';
        }
    }

    return 'active';
}

$statusCounts = [
    'active' => 0,
    'scheduled' => 0,
    'draft' => 0,
    'expired' => 0,
    'archived' => 0,
];

foreach ($promotions as $promotion) {
    $effectiveStatus = promotion_effective_status($promotion);
    if (isset($statusCounts[$effectiveStatus])) {
        $statusCounts[$effectiveStatus]++;
    }
}

$csrfToken = admin_csrf_token();
$promotionToast = $_SESSION['promotion_toast'] ?? null;
unset($_SESSION['promotion_toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= promotion_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($promotionToast): ?>
    <div class="auth-toast <?= ($promotionToast['type'] ?? '') === 'success' ? 'toast-success' : '' ?> is-visible" role="status" aria-live="polite">
        <span class="auth-toast-icon"><?= ($promotionToast['type'] ?? '') === 'success' ? '✓' : '!' ?></span>
        <span><?= promotion_escape($promotionToast['message'] ?? 'Promotion action completed.') ?></span>
        <button type="button" class="auth-toast-close" aria-label="Dismiss notification">×</button>
    </div>
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
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link"><span class="side-icon">▦</span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link"><span class="side-icon">◉</span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon">✳</span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon">☆</span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#polls" class="sidebar-link"><span class="side-icon">▤</span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#feedback" class="sidebar-link"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= promotion_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Workspace <span>/</span> <strong>Promotions</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CONTENT MANAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= promotion_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= promotion_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading promotions-page-heading">
                <div>
                    <p class="admin-kicker">A LITTLE EXTRA SWEETNESS</p>
                    <h1>Promotions<em>.</em></h1>
                    <p>Share special offers and limited-time discounts with your customers.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/promotions/add.php" class="admin-primary-btn">＋ Add promotion</a>
            </div>

            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">
                    <strong>Promotions could not be loaded.</strong>
                    <span>Please check your database connection and confirm that the promotions table exists with the expected columns.</span>
                </div>
            <?php else: ?>
                <div class="promotion-stat-grid">
                    <article class="promotion-stat-card">
                        <span class="promotion-stat-icon">✳</span>
                        <div><small>Total promotions</small><strong><?= count($promotions) ?></strong></div>
                    </article>
                    <article class="promotion-stat-card">
                        <span class="promotion-stat-icon promotion-stat-live">✓</span>
                        <div><small>Active offers</small><strong><?= $statusCounts['active'] ?></strong></div>
                    </article>
                    <article class="promotion-stat-card">
                        <span class="promotion-stat-icon promotion-stat-scheduled">◷</span>
                        <div><small>Scheduled</small><strong><?= $statusCounts['scheduled'] ?></strong></div>
                    </article>
                    <article class="promotion-stat-card">
                        <span class="promotion-stat-icon promotion-stat-draft">✎</span>
                        <div><small>Drafts</small><strong><?= $statusCounts['draft'] ?></strong></div>
                    </article>
                </div>

                <section class="promotion-manager-card">
                    <div class="promotion-toolbar">
                        <div>
                            <span class="card-overline">SPECIAL OFFERS</span>
                            <h2>All promotions <span>(<?= count($promotions) ?>)</span></h2>
                        </div>
                        <?php if ($promotions): ?>
                            <div class="promotion-controls">
                                <label class="admin-search promotion-search">
                                    <span>⌕</span>
                                    <input type="search" id="promotion-search" placeholder="Search promotions..." aria-label="Search promotions">
                                </label>
                                <select id="promotion-status-filter" aria-label="Filter promotions by status">
                                    <option value="all">All statuses</option>
                                    <option value="active">Active</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="draft">Draft</option>
                                    <option value="expired">Expired</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$promotions): ?>
                        <div class="category-empty-state">
                            <span class="category-empty-icon">✳</span>
                            <h2>No promotions yet</h2>
                            <p>Your special offers will appear here once you create your first promotion.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="product-table promotion-table">
                                <thead>
                                    <tr>
                                        <th>PROMOTION</th>
                                        <th>OFFER</th>
                                        <th>STATUS</th>
                                        <th>STARTS</th>
                                        <th>ENDS</th>
                                        <th>DATE CREATED</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="promotion-table-body">
                                    <?php foreach ($promotions as $promotion): ?>
                                        <?php $effectiveStatus = promotion_effective_status($promotion); ?>
                                        <tr data-promotion-row data-status="<?= promotion_escape($effectiveStatus) ?>">
                                            <td>
                                                <div class="promotion-title-cell">
                                                    <span class="promotion-row-icon">✳</span>
                                                    <div>
                                                        <strong><?= promotion_escape($promotion['title']) ?></strong>
                                                        <small><?= promotion_escape(mb_strimwidth(strip_tags($promotion['description'] ?? ''), 0, 105, '…', 'UTF-8')) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="promotion-discount">
                                                <?= $promotion['discount_value'] !== null ? promotion_escape(rtrim(rtrim(number_format((float) $promotion['discount_value'], 2, '.', ''), '0'), '.') . '% OFF') : '—' ?>
                                            </td>
                                            <td><span class="promotion-status promotion-status-<?= promotion_escape($effectiveStatus) ?>"><i></i><?= promotion_escape(ucfirst($effectiveStatus)) ?></span></td>
                                            <td class="promotion-date"><?= $promotion['start_date'] ? promotion_escape(date('M j, Y · g:i A', strtotime($promotion['start_date']))) : 'Immediately' ?></td>
                                            <td class="promotion-date"><?= $promotion['end_date'] ? promotion_escape(date('M j, Y · g:i A', strtotime($promotion['end_date']))) : 'No end date' ?></td>
                                            <td class="promotion-date"><?= promotion_escape(date('M j, Y', strtotime($promotion['created_at']))) ?></td>
<td>
                                                <a class="table-action-btn table-edit-btn" href="<?= BASE_URL ?>/admin/promotions/edit.php?id=<?= (int) $promotion['id'] ?>">Edit</a>
                                                <button class="table-action-btn table-delete-btn" type="button" data-open-promotion-delete data-promotion-id="<?= (int) $promotion['id'] ?>" data-promotion-title="<?= promotion_escape($promotion['title']) ?>">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="category-table-footer">
                            <span id="promotion-result-count">Showing <?= count($promotions) ?> promotions</span>
                            <span>Promotion records · Live database</span>
                        </div>
                        <div class="category-no-results" id="promotion-no-results" hidden>No promotions match your search or filter.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> PROMOTION MANAGEMENT</span></footer>
    </main>
</div>


<div class="delete-modal-backdrop" id="promotion-delete-modal" hidden>
    <section class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="promotion-delete-title" aria-describedby="promotion-delete-description">
        <div class="delete-modal-icon" aria-hidden="true">!</div>
        <span class="delete-modal-eyebrow">PLEASE CONFIRM</span>
        <h2 id="promotion-delete-title">Delete this promotion?</h2>
        <p id="promotion-delete-description">You're about to permanently remove <strong id="delete-promotion-title">this promotion</strong>.</p>
        <div class="delete-modal-note"><span>↳</span><p>This promotion will be permanently deleted from your records. This action cannot be undone.</p></div>
        <form id="promotion-delete-confirm-form" action="<?= BASE_URL ?>/admin/promotions/delete.php" method="POST" class="delete-modal-actions">
            <input type="hidden" name="csrf_token" value="<?= promotion_escape($csrfToken) ?>">
            <input type="hidden" name="id" id="delete-promotion-id" value="">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="delete-modal-cancel" data-close-promotion-delete>Cancel</button>
            <button type="submit" class="delete-modal-confirm">Yes, delete promotion</button>
        </form>
    </section>
</div>

<script>
const sidebarToggle = document.querySelector('#sidebar-toggle');
sidebarToggle?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});

document.querySelectorAll('.auth-toast').forEach((toast) => {
    toast.querySelector('.auth-toast-close')?.addEventListener('click', () => toast.remove());
    window.setTimeout(() => toast.remove(), 5000);
});

const promotionDeleteModal = document.querySelector('#promotion-delete-modal');
let promotionDeleteTrigger = null;

const closePromotionDeleteModal = () => {
    if (!promotionDeleteModal) return;
    promotionDeleteModal.hidden = true;
    document.body.classList.remove('delete-modal-open');
    promotionDeleteTrigger?.focus();
};

document.querySelectorAll('[data-open-promotion-delete]').forEach((button) => {
    button.addEventListener('click', () => {
        promotionDeleteTrigger = button;
        document.querySelector('#delete-promotion-id').value = button.dataset.promotionId || '';
        document.querySelector('#delete-promotion-title').textContent = button.dataset.promotionTitle || 'this promotion';
        promotionDeleteModal.hidden = false;
        document.body.classList.add('delete-modal-open');
        promotionDeleteModal.querySelector('[data-close-promotion-delete]')?.focus();
    });
});

document.querySelectorAll('[data-close-promotion-delete]').forEach((button) => {
    button.addEventListener('click', closePromotionDeleteModal);
});

promotionDeleteModal?.addEventListener('click', (event) => {
    if (event.target === promotionDeleteModal) closePromotionDeleteModal();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && promotionDeleteModal && !promotionDeleteModal.hidden) {
        closePromotionDeleteModal();
    }
});

const promotionSearch = document.querySelector('#promotion-search');
const promotionStatusFilter = document.querySelector('#promotion-status-filter');
const promotionRows = [...document.querySelectorAll('[data-promotion-row]')];

function filterPromotions() {
    const query = (promotionSearch?.value || '').trim().toLowerCase();
    const selectedStatus = promotionStatusFilter?.value || 'all';
    let visibleCount = 0;

    promotionRows.forEach((row) => {
        const matchesQuery = row.textContent.toLowerCase().includes(query);
        const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
        const visible = matchesQuery && matchesStatus;
        row.hidden = !visible;
        if (visible) visibleCount++;
    });

    const resultCount = document.querySelector('#promotion-result-count');
    if (resultCount) {
        resultCount.textContent = 'Showing ' + visibleCount + ' of ' + promotionRows.length + ' promotions';
    }

    const noResults = document.querySelector('#promotion-no-results');
    if (noResults) noResults.hidden = visibleCount !== 0;
}

promotionSearch?.addEventListener('input', filterPromotions);
promotionStatusFilter?.addEventListener('change', filterPromotions);
</script>
</body>
</html>
