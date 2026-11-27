<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Announcements';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$announcements = [];
$loadError = false;

try {
    $announcementQuery = $pdo->query(
        'SELECT id, title, content, status, start_date, end_date, created_at, updated_at
         FROM announcements
         ORDER BY created_at DESC, id DESC'
    );
    $announcements = $announcementQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot announcement listing failed: ' . $exception->getMessage());
    $loadError = true;
}

function announcement_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function announcement_effective_status(array $announcement): string
{
    if ($announcement['status'] !== 'published') {
        return $announcement['status'];
    }

    $now = new DateTimeImmutable();

    if (!empty($announcement['start_date'])) {
        $startDate = new DateTimeImmutable($announcement['start_date']);
        if ($startDate > $now) {
            return 'scheduled';
        }
    }

    if (!empty($announcement['end_date'])) {
        $endDate = new DateTimeImmutable($announcement['end_date']);
        if ($endDate < $now) {
            return 'expired';
        }
    }

    return 'published';
}

$statusCounts = [
    'draft' => 0,
    'published' => 0,
    'scheduled' => 0,
    'expired' => 0,
    'archived' => 0,
];

foreach ($announcements as $announcement) {
    $effectiveStatus = announcement_effective_status($announcement);
    if (isset($statusCounts[$effectiveStatus])) {
        $statusCounts[$effectiveStatus]++;
    }
}

$csrfToken = admin_csrf_token();
$announcementToast = $_SESSION['announcement_toast'] ?? null;
unset($_SESSION['announcement_toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= announcement_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($announcementToast): ?>
    <div class="auth-toast <?= ($announcementToast['type'] ?? '') === 'success' ? 'toast-success' : '' ?> is-visible" role="status" aria-live="polite">
        <span class="auth-toast-icon"><?= ($announcementToast['type'] ?? '') === 'success' ? '✓' : '!' ?></span>
        <span><?= announcement_escape($announcementToast['message'] ?? 'Announcement action completed.') ?></span>
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
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#home"></use></svg></span> Overview</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#package"></use></svg></span> Products</a>
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#layers"></use></svg></span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#megaphone"></use></svg></span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#tag"></use></svg></span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#star"></use></svg></span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chart"></use></svg></span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/feedback/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#message"></use></svg></span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#sparkles"></use></svg></span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#arrow-up-right"></use></svg> <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= announcement_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#log-out"></use></svg> <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#menu"></use></svg></button>
                <div class="breadcrumb">Workspace <span>/</span> <strong>Announcements</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CONTENT MANAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= announcement_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= announcement_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chevron-down"></use></svg></span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading announcements-page-heading">
                <div>
                    <p class="admin-kicker">KEEP CUSTOMERS INFORMED</p>
                    <h1>Announcements<em>.</em></h1>
                    <p>Share bakery news, schedule updates, and important notices.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/announcements/add.php" class="admin-primary-btn">＋ Create announcement <span>↗</span></a>
            </div>

            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">
                    <strong>Announcements could not be loaded.</strong>
                    <span>Please check your database connection and confirm that the announcements table exists.</span>
                </div>
            <?php else: ?>
                <div class="announcement-stat-grid">
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#megaphone"></use></svg></span>
                        <div><small>Total announcements</small><strong><?= count($announcements) ?></strong></div>
                    </article>
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon announcement-stat-live"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#check"></use></svg></span>
                        <div><small>Published</small><strong><?= $statusCounts['published'] ?></strong></div>
                    </article>
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon announcement-stat-scheduled"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#clock"></use></svg></span>
                        <div><small>Scheduled</small><strong><?= $statusCounts['scheduled'] ?></strong></div>
                    </article>
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon announcement-stat-draft">✎</span>
                        <div><small>Drafts</small><strong><?= $statusCounts['draft'] ?></strong></div>
                    </article>
                </div>

                <section class="announcement-manager-card">
                    <div class="announcement-toolbar">
                        <div>
                            <span class="card-overline">BAKERY UPDATES</span>
                            <h2>All announcements <span>(<?= count($announcements) ?>)</span></h2>
                        </div>
                        <?php if ($announcements): ?>
                            <div class="announcement-controls">
                                <label class="admin-search announcement-search">
                                    <span>⌕</span>
                                    <input type="search" id="announcement-search" placeholder="Search announcements..." aria-label="Search announcements">
                                </label>
                                <select id="announcement-status-filter" aria-label="Filter announcements by status">
                                    <option value="all">All statuses</option>
                                    <option value="published">Published</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="draft">Draft</option>
                                    <option value="expired">Expired</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$announcements): ?>
                        <div class="category-empty-state">
                            <span class="category-empty-icon">◉</span>
                            <h2>No announcements yet</h2>
                            <p>Share your first bakery update. You can publish it immediately or schedule it for a later date.</p>
                            <a href="<?= BASE_URL ?>/admin/announcements/add.php" class="admin-primary-btn">＋ Create your first announcement <span>↗</span></a>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="product-table announcement-table">
                                <thead>
                                    <tr>
                                        <th>ANNOUNCEMENT</th>
                                        <th>STATUS</th>
                                        <th>STARTS</th>
                                        <th>ENDS</th>
                                        <th>DATE CREATED</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="announcement-table-body">
                                    <?php foreach ($announcements as $announcement): ?>
                                        <?php $effectiveStatus = announcement_effective_status($announcement); ?>
                                        <tr data-announcement-row data-status="<?= announcement_escape($effectiveStatus) ?>">
                                            <td>
                                                <div class="announcement-title-cell">
                                                    <span class="announcement-row-icon">◉</span>
                                                    <div>
                                                        <strong><?= announcement_escape($announcement['title']) ?></strong>
                                                        <small><?= announcement_escape(mb_strimwidth(strip_tags($announcement['content']), 0, 110, '…', 'UTF-8')) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="announcement-status announcement-status-<?= announcement_escape($effectiveStatus) ?>"><i></i><?= announcement_escape(ucfirst($effectiveStatus)) ?></span></td>
                                            <td class="announcement-date"><?= $announcement['start_date'] ? announcement_escape(date('M j, Y · g:i A', strtotime($announcement['start_date']))) : 'Immediately' ?></td>
                                            <td class="announcement-date"><?= $announcement['end_date'] ? announcement_escape(date('M j, Y · g:i A', strtotime($announcement['end_date']))) : 'No end date' ?></td>
                                            <td class="announcement-date"><?= announcement_escape(date('M j, Y', strtotime($announcement['created_at']))) ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a class="table-action-btn table-edit-btn" href="<?= BASE_URL ?>/admin/announcements/edit.php?id=<?= (int) $announcement['id'] ?>"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#edit"></use></svg> Edit</a>
                                                    <button class="table-action-btn table-delete-btn" type="button" data-open-announcement-delete data-announcement-id="<?= (int) $announcement['id'] ?>" data-announcement-title="<?= announcement_escape($announcement['title']) ?>"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#x"></use></svg> Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="category-table-footer">
                            <span id="announcement-result-count">Showing <?= count($announcements) ?> announcements</span>
                            <span>Announcement records · Live database</span>
                        </div>
                        <div class="category-no-results" id="announcement-no-results" hidden>No announcements match your search or filter.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> ANNOUNCEMENT MANAGEMENT</span></footer>
    </main>
</div>

<div class="delete-modal-backdrop" id="announcement-delete-modal" hidden>
    <section class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="announcement-delete-title" aria-describedby="announcement-delete-description">
        <div class="delete-modal-icon" aria-hidden="true">!</div>
        <span class="delete-modal-eyebrow">PLEASE CONFIRM</span>
        <h2 id="announcement-delete-title">Delete this announcement?</h2>
        <p id="announcement-delete-description">You're about to permanently remove <strong id="delete-announcement-title">this announcement</strong>.</p>
        <div class="delete-modal-note"><span>↳</span><p>This announcement will be permanently deleted from your records. This action cannot be undone.</p></div>
        <form id="announcement-delete-confirm-form" action="<?= BASE_URL ?>/admin/announcements/delete.php" method="POST" class="delete-modal-actions">
            <input type="hidden" name="csrf_token" value="<?= announcement_escape($csrfToken) ?>">
            <input type="hidden" name="id" id="delete-announcement-id" value="">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="delete-modal-cancel" data-close-announcement-delete>Cancel</button>
            <button type="submit" class="delete-modal-confirm">Yes, delete announcement</button>
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

const announcementDeleteModal = document.querySelector('#announcement-delete-modal');
let announcementDeleteTrigger = null;

const closeAnnouncementDeleteModal = () => {
    if (!announcementDeleteModal) return;
    announcementDeleteModal.hidden = true;
    document.body.classList.remove('delete-modal-open');
    announcementDeleteTrigger?.focus();
};

document.querySelectorAll('[data-open-announcement-delete]').forEach((button) => {
    button.addEventListener('click', () => {
        announcementDeleteTrigger = button;
        document.querySelector('#delete-announcement-id').value = button.dataset.announcementId || '';
        document.querySelector('#delete-announcement-title').textContent = button.dataset.announcementTitle || 'this announcement';
        announcementDeleteModal.hidden = false;
        document.body.classList.add('delete-modal-open');
        announcementDeleteModal.querySelector('[data-close-announcement-delete]')?.focus();
    });
});

document.querySelectorAll('[data-close-announcement-delete]').forEach((button) => {
    button.addEventListener('click', closeAnnouncementDeleteModal);
});

announcementDeleteModal?.addEventListener('click', (event) => {
    if (event.target === announcementDeleteModal) closeAnnouncementDeleteModal();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && announcementDeleteModal && !announcementDeleteModal.hidden) {
        closeAnnouncementDeleteModal();
    }
});

const announcementSearch = document.querySelector('#announcement-search');
const announcementStatusFilter = document.querySelector('#announcement-status-filter');
const announcementRows = [...document.querySelectorAll('[data-announcement-row]')];

function filterAnnouncements() {
    const query = (announcementSearch?.value || '').trim().toLowerCase();
    const selectedStatus = announcementStatusFilter?.value || 'all';
    let visibleCount = 0;

    announcementRows.forEach((row) => {
        const matchesQuery = row.textContent.toLowerCase().includes(query);
        const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
        const visible = matchesQuery && matchesStatus;
        row.hidden = !visible;
        if (visible) visibleCount++;
    });

    const resultCount = document.querySelector('#announcement-result-count');
    if (resultCount) {
        resultCount.textContent = 'Showing ' + visibleCount + ' of ' + announcementRows.length + ' announcements';
    }

    const noResults = document.querySelector('#announcement-no-results');
    if (noResults) noResults.hidden = visibleCount !== 0;
}

announcementSearch?.addEventListener('input', filterAnnouncements);
announcementStatusFilter?.addEventListener('change', filterAnnouncements);
</script>
</body>
</html>
