<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Customer Feedback';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$feedbackItems = [];
$loadError = false;

try {
    $feedbackQuery = $pdo->query(
        "SELECT id, customer_name, email, subject, message, status, created_at
         FROM feedback
         ORDER BY created_at DESC, id DESC"
    );
    $feedbackItems = $feedbackQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot feedback listing failed: ' . $exception->getMessage());
    $loadError = true;
}

function feedback_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$statusCounts = [
    'new' => 0,
    'read' => 0,
    'replied' => 0,
    'archived' => 0,
];

foreach ($feedbackItems as $item) {
    if (isset($statusCounts[$item['status']])) {
        $statusCounts[$item['status']]++;
    }
}

$csrfToken = admin_csrf_token();
$feedbackFlash = $_SESSION['feedback_toast'] ?? null;
unset($_SESSION['feedback_toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= feedback_escape($pageTitle) ?> | Sweet Spot Admin</title>
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
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon">☆</span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link"><span class="side-icon">▤</span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/feedback/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= feedback_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Community <span>/</span> <strong>Feedback</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CUSTOMER EXPERIENCE</span>
                <div class="admin-profile"><span class="profile-avatar"><?= feedback_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= feedback_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading reviews-page-heading">
                <div>
                    <p class="admin-kicker">MESSAGES FROM YOUR CUSTOMERS</p>
                    <h1>Customer <em>feedback.</em></h1>
                    <p>Read questions, suggestions, and messages sent through your website.</p>
                </div>
            </div>

            <?php if ($feedbackFlash && is_array($feedbackFlash)): ?>
                <div class="category-message <?= ($feedbackFlash['type'] ?? '') === 'error' ? 'category-message-error' : '' ?>" role="status">
                    <strong><?= feedback_escape(($feedbackFlash['type'] ?? '') === 'error' ? 'Action not completed.' : 'Feedback updated.') ?></strong>
                    <span><?= feedback_escape($feedbackFlash['message'] ?? '') ?></span>
                </div>
            <?php endif; ?>

            <div class="review-stat-grid feedback-stat-grid">
                <article class="review-stat-card"><span class="review-stat-icon">✉</span><div><small>Total messages</small><strong><?= count($feedbackItems) ?></strong></div></article>
                <article class="review-stat-card"><span class="review-stat-icon review-stat-pending">◷</span><div><small>New</small><strong><?= $statusCounts['new'] ?></strong></div></article>
                <article class="review-stat-card"><span class="review-stat-icon review-stat-approved">✓</span><div><small>Read</small><strong><?= $statusCounts['read'] ?></strong></div></article>
                <article class="review-stat-card"><span class="review-stat-icon" style="background:#EAF0F4;color:#59788C">↗</span><div><small>Replied</small><strong><?= $statusCounts['replied'] ?></strong></div></article>
                <article class="review-stat-card"><span class="review-stat-icon" style="background:#F0ECE8;color:#82766B">▣</span><div><small>Archived</small><strong><?= $statusCounts['archived'] ?></strong></div></article>
            </div>

            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">
                    <strong>Feedback could not be loaded.</strong>
                    <span>Please check your database connection and confirm that the feedback table exists.</span>
                </div>
            <?php else: ?>
                <section class="review-manager-card">
                    <div class="review-toolbar">
                        <div>
                            <span class="card-overline">CUSTOMER INBOX</span>
                            <h2>All messages <span>(<?= count($feedbackItems) ?>)</span></h2>
                        </div>
                        <?php if ($feedbackItems): ?>
                            <div class="review-controls">
                                <label class="admin-search review-search">
                                    <span>⌕</span>
                                    <input type="search" id="feedback-search" placeholder="Search messages..." aria-label="Search feedback">
                                </label>
                                <select id="feedback-status-filter" aria-label="Filter feedback by status">
                                    <option value="all">All statuses</option>
                                    <option value="new">New</option>
                                    <option value="read">Read</option>
                                    <option value="replied">Replied</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$feedbackItems): ?>
                        <div class="category-empty-state">
                            <span class="category-empty-icon">✉</span>
                            <h2>Your inbox is empty</h2>
                            <p>When customers send a message through the Contact Us form, it will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="product-table review-table feedback-table">
                                <thead>
                                    <tr>
                                        <th>CUSTOMER</th>
                                        <th>SUBJECT</th>
                                        <th>MESSAGE</th>
                                        <th>STATUS</th>
                                        <th>RECEIVED</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="feedback-table-body">
                                    <?php foreach ($feedbackItems as $item): ?>
                                        <?php
                                            $feedbackStatus = in_array($item['status'], ['new', 'read', 'replied', 'archived'], true)
                                                ? $item['status']
                                                : 'new';
                                            $customerName = trim((string) ($item['customer_name'] ?? ''));
                                            $subject = trim((string) ($item['subject'] ?? ''));
                                        ?>
                                        <tr data-feedback-row data-status="<?= feedback_escape($feedbackStatus) ?>">
                                            <td>
                                                <div class="feedback-customer-cell">
                                                    <strong><?= feedback_escape($customerName !== '' ? $customerName : 'Anonymous') ?></strong>
                                                    <small><?= feedback_escape($item['email']) ?></small>
                                                </div>
                                            </td>
                                            <td class="feedback-subject"><?= feedback_escape($subject !== '' ? $subject : 'No subject') ?></td>
                                            <td><p class="feedback-preview"><?= feedback_escape($item['message']) ?></p></td>
                                            <td><span class="review-status feedback-status-<?= feedback_escape($feedbackStatus) ?>"><?= feedback_escape(ucfirst($feedbackStatus)) ?></span></td>
                                            <td class="review-date"><?= $item['created_at'] ? feedback_escape(date('M j, Y', strtotime($item['created_at']))) : '—' ?></td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="review-action-btn feedback-view-btn"
                                                    data-feedback-view
                                                    data-name="<?= feedback_escape($customerName !== '' ? $customerName : 'Anonymous') ?>"
                                                    data-email="<?= feedback_escape($item['email']) ?>"
                                                    data-subject="<?= feedback_escape($subject !== '' ? $subject : 'No subject') ?>"
                                                    data-message="<?= feedback_escape($item['message']) ?>"
                                                    data-date="<?= feedback_escape($item['created_at'] ?? '') ?>"
                                                >View message</button>
                                                <form class="feedback-status-form" action="<?= BASE_URL ?>/admin/feedback/update-status.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= feedback_escape($csrfToken) ?>">
                                                    <input type="hidden" name="feedback_id" value="<?= (int) $item['id'] ?>">
                                                    <label class="visually-hidden" for="feedback-status-<?= (int) $item['id'] ?>">Change status</label>
                                                    <select id="feedback-status-<?= (int) $item['id'] ?>" name="status" aria-label="Change status for feedback #<?= (int) $item['id'] ?>">
                                                        <?php foreach (['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'] as $statusValue => $statusLabel): ?>
                                                            <option value="<?= feedback_escape($statusValue) ?>" <?= $feedbackStatus === $statusValue ? 'selected' : '' ?>><?= feedback_escape($statusLabel) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="feedback-update-btn">Save</button>
                                                </form>
                                                <button type="button" class="feedback-delete-btn" data-feedback-delete data-id="<?= (int) $item['id'] ?>" data-name="<?= feedback_escape($customerName !== '' ? $customerName : 'Anonymous') ?>">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="category-table-footer">
                            <span id="feedback-result-count">Showing <?= count($feedbackItems) ?> messages</span>
                            <span>Feedback records · Live database</span>
                        </div>
                        <div class="category-no-results" id="feedback-no-results" hidden>No messages match your search or filter.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> FEEDBACK INBOX</span></footer>
    </main>
</div>

<div class="feedback-delete-modal" id="feedback-delete-modal" aria-hidden="true">
    <div class="feedback-modal-backdrop" data-feedback-delete-close></div>
    <section class="feedback-modal-dialog feedback-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="feedback-delete-title">
        <button type="button" class="feedback-modal-close" data-feedback-delete-close aria-label="Close confirmation">×</button>
        <p class="admin-kicker">CONFIRM DELETION</p>
        <h2 id="feedback-delete-title">Delete this message?</h2>
        <p class="feedback-delete-copy">This will permanently remove the feedback message from <strong id="feedback-delete-name">this customer</strong>. This action cannot be undone.</p>
        <form action="<?= BASE_URL ?>/admin/feedback/delete.php" method="POST" class="feedback-delete-form">
            <input type="hidden" name="csrf_token" value="<?= feedback_escape($csrfToken) ?>">
            <input type="hidden" name="feedback_id" id="feedback-delete-id" value="">
            <button type="button" class="feedback-cancel-btn" data-feedback-delete-close>Cancel</button>
            <button type="submit" class="feedback-confirm-delete-btn">Delete message</button>
        </form>
    </section>
</div>

<div class="feedback-message-modal" id="feedback-message-modal" aria-hidden="true">
    <div class="feedback-modal-backdrop" data-feedback-close></div>
    <section class="feedback-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="feedback-modal-title">
        <button type="button" class="feedback-modal-close" data-feedback-close aria-label="Close message">×</button>
        <p class="admin-kicker">CUSTOMER MESSAGE</p>
        <h2 id="feedback-modal-title">Feedback details</h2>
        <div class="feedback-modal-meta">
            <div><small>FROM</small><strong id="feedback-modal-name"></strong><a id="feedback-modal-email" href="#"></a></div>
            <div><small>SUBJECT</small><strong id="feedback-modal-subject"></strong></div>
            <div><small>RECEIVED</small><strong id="feedback-modal-date"></strong></div>
        </div>
        <div class="feedback-modal-message" id="feedback-modal-message"></div>
    </section>
</div>

<script>
const feedbackSearch = document.querySelector('#feedback-search');
const feedbackStatusFilter = document.querySelector('#feedback-status-filter');
const feedbackRows = [...document.querySelectorAll('[data-feedback-row]')];

function filterFeedback() {
    const query = (feedbackSearch?.value || '').trim().toLowerCase();
    const selectedStatus = feedbackStatusFilter?.value || 'all';
    let visibleCount = 0;

    feedbackRows.forEach((row) => {
        const matchesQuery = row.textContent.toLowerCase().includes(query);
        const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
        const visible = matchesQuery && matchesStatus;
        row.hidden = !visible;
        if (visible) visibleCount++;
    });

    const resultCount = document.querySelector('#feedback-result-count');
    if (resultCount) resultCount.textContent = 'Showing ' + visibleCount + ' of ' + feedbackRows.length + ' messages';
    const noResults = document.querySelector('#feedback-no-results');
    if (noResults) noResults.hidden = visibleCount !== 0;
}

feedbackSearch?.addEventListener('input', filterFeedback);
feedbackStatusFilter?.addEventListener('change', filterFeedback);

const feedbackModal = document.querySelector('#feedback-message-modal');
const feedbackModalName = document.querySelector('#feedback-modal-name');
const feedbackModalEmail = document.querySelector('#feedback-modal-email');
const feedbackModalSubject = document.querySelector('#feedback-modal-subject');
const feedbackModalDate = document.querySelector('#feedback-modal-date');
const feedbackModalMessage = document.querySelector('#feedback-modal-message');
let feedbackPreviousFocus = null;

function closeFeedbackModal() {
    feedbackModal?.classList.remove('is-open');
    feedbackModal?.setAttribute('aria-hidden', 'true');
    feedbackPreviousFocus?.focus();
}

document.querySelectorAll('[data-feedback-view]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!feedbackModal) return;
        feedbackPreviousFocus = button;
        feedbackModalName.textContent = button.dataset.name || 'Anonymous';
        feedbackModalEmail.textContent = button.dataset.email || '';
        feedbackModalEmail.href = button.dataset.email ? 'mailto:' + button.dataset.email : '#';
        feedbackModalSubject.textContent = button.dataset.subject || 'No subject';
        const date = button.dataset.date ? new Date(button.dataset.date.replace(' ', 'T')) : null;
        feedbackModalDate.textContent = date && !Number.isNaN(date.getTime())
            ? date.toLocaleString()
            : 'Date unavailable';
        feedbackModalMessage.textContent = button.dataset.message || '';
        feedbackModal.classList.add('is-open');
        feedbackModal.setAttribute('aria-hidden', 'false');
        feedbackModal.querySelector('.feedback-modal-close')?.focus();
    });
});

document.querySelectorAll('[data-feedback-close]').forEach((button) => {
    button.addEventListener('click', closeFeedbackModal);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && feedbackModal?.classList.contains('is-open')) {
        closeFeedbackModal();
    }
});

const feedbackDeleteModal = document.querySelector('#feedback-delete-modal');
const feedbackDeleteId = document.querySelector('#feedback-delete-id');
const feedbackDeleteName = document.querySelector('#feedback-delete-name');
let feedbackDeletePreviousFocus = null;

function closeFeedbackDeleteModal() {
    feedbackDeleteModal?.classList.remove('is-open');
    feedbackDeleteModal?.setAttribute('aria-hidden', 'true');
    feedbackDeletePreviousFocus?.focus();
}

document.querySelectorAll('[data-feedback-delete]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!feedbackDeleteModal) return;
        feedbackDeletePreviousFocus = button;
        feedbackDeleteId.value = button.dataset.id || '';
        feedbackDeleteName.textContent = button.dataset.name || 'this customer';
        feedbackDeleteModal.classList.add('is-open');
        feedbackDeleteModal.setAttribute('aria-hidden', 'false');
        feedbackDeleteModal.querySelector('.feedback-modal-close')?.focus();
    });
});

document.querySelectorAll('[data-feedback-delete-close]').forEach((button) => {
    button.addEventListener('click', closeFeedbackDeleteModal);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && feedbackDeleteModal?.classList.contains('is-open')) {
        closeFeedbackDeleteModal();
    }
});

const sidebarToggle = document.querySelector('#sidebar-toggle');
sidebarToggle?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});
</script>
</body>
</html>
