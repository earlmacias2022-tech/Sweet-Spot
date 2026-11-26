<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Polls';
$toast = $_SESSION['poll_toast'] ?? null;
unset($_SESSION['poll_toast']);
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$polls = [];
$loadError = false;

try {
    $pollQuery = $pdo->query(
        "SELECT p.id, p.product_id, p.question, p.description, p.status,
                p.start_date, p.end_date, p.created_at,
                pr.name AS product_name,
                COUNT(DISTINCT po.id) AS option_count,
                COUNT(DISTINCT pv.id) AS vote_count
         FROM polls p
         LEFT JOIN products pr ON pr.id = p.product_id
         LEFT JOIN poll_options po ON po.poll_id = p.id
         LEFT JOIN poll_votes pv ON pv.poll_id = p.id
         GROUP BY p.id, p.product_id, p.question, p.description, p.status,
                  p.start_date, p.end_date, p.created_at, pr.name
         ORDER BY p.created_at DESC, p.id DESC"
    );
    $polls = $pollQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot poll listing failed: ' . $exception->getMessage());
    $loadError = true;
}

function poll_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function poll_effective_status(array $poll): string
{
    if ($poll['status'] !== 'active') {
        return $poll['status'];
    }

    $now = new DateTimeImmutable();

    if (!empty($poll['start_date'])) {
        $startDate = new DateTimeImmutable($poll['start_date']);
        if ($startDate > $now) {
            return 'scheduled';
        }
    }

    if (!empty($poll['end_date'])) {
        $endDate = new DateTimeImmutable($poll['end_date']);
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
    'closed' => 0,
    'expired' => 0,
];

foreach ($polls as $poll) {
    $effectiveStatus = poll_effective_status($poll);
    if (isset($statusCounts[$effectiveStatus])) {
        $statusCounts[$effectiveStatus]++;
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
    <title><?= poll_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($toast): ?>
    <div class="auth-toast is-visible <?= ($toast['type'] ?? '') === 'success' ? 'auth-toast-success' : '' ?>" role="status" aria-live="polite">
        <span class="auth-toast-icon"><?= ($toast['type'] ?? '') === 'success' ? '✓' : '!' ?></span>
        <span><?= poll_escape($toast['message'] ?? '') ?></span>
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
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link"><span class="side-icon">✳</span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon">☆</span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon">▤</span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#feedback" class="sidebar-link"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= poll_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Community <span>/</span> <strong>Polls</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CUSTOMER ENGAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= poll_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= poll_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading polls-page-heading">
                <div>
                    <p class="admin-kicker">LET CUSTOMERS HAVE A SAY</p>
                    <h1>Product <em>polls.</em></h1>
                    <p>Create product-related questions, collect votes, and learn what your customers prefer.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/polls/add.php" class="admin-primary-btn">Create poll <span>↗</span></a>
            </div>

            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">
                    <strong>Polls could not be loaded.</strong>
                    <span>Please check your database connection and confirm that the polls, poll_options, and poll_votes tables exist.</span>
                </div>
            <?php else: ?>
                <div class="announcement-stat-grid poll-stat-grid">
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon">▤</span>
                        <div><small>Total polls</small><strong><?= count($polls) ?></strong></div>
                    </article>
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon poll-stat-active">✓</span>
                        <div><small>Active polls</small><strong><?= $statusCounts['active'] ?></strong></div>
                    </article>
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon announcement-stat-scheduled">◷</span>
                        <div><small>Scheduled</small><strong><?= $statusCounts['scheduled'] ?></strong></div>
                    </article>
                    <article class="announcement-stat-card">
                        <span class="announcement-stat-icon announcement-stat-draft">✎</span>
                        <div><small>Draft polls</small><strong><?= $statusCounts['draft'] ?></strong></div>
                    </article>
                </div>

                <section class="announcement-manager-card">
                    <div class="announcement-toolbar">
                        <div>
                            <span class="card-overline">CUSTOMER ENGAGEMENT</span>
                            <h2>All polls <span>(<?= count($polls) ?>)</span></h2>
                        </div>
                        <?php if ($polls): ?>
                            <div class="announcement-controls">
                                <label class="admin-search announcement-search">
                                    <span>⌕</span>
                                    <input type="search" id="poll-search" placeholder="Search polls..." aria-label="Search polls">
                                </label>
                                <select id="poll-status-filter" aria-label="Filter polls by status">
                                    <option value="all">All statuses</option>
                                    <option value="active">Active</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="draft">Draft</option>
                                    <option value="closed">Closed</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$polls): ?>
                        <div class="category-empty-state">
                            <span class="category-empty-icon">▤</span>
                            <h2>No polls yet</h2>
                            <p>When you create your first poll, it will appear here. You can connect a poll to a product, add answer options, and review the votes customers submit.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="product-table poll-table">
                                <thead>
                                    <tr>
                                        <th>POLL QUESTION</th>
                                        <th>PRODUCT</th>
                                        <th>OPTIONS</th>
                                        <th>VOTES</th>
                                        <th>STATUS</th>
                                        <th>DATE CREATED</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="poll-table-body">
                                    <?php foreach ($polls as $poll): ?>
                                        <?php
                                            $effectiveStatus = poll_effective_status($poll);
                                            $pollDescription = trim((string) ($poll['description'] ?? ''));
                                        ?>
                                        <tr data-poll-row data-status="<?= poll_escape($effectiveStatus) ?>">
                                            <td>
                                                <div class="poll-question-cell">
                                                    <span class="poll-row-icon">▤</span>
                                                    <div>
                                                        <strong><?= poll_escape($poll['question']) ?></strong>
                                                        <small><?= poll_escape($pollDescription !== '' ? mb_strimwidth($pollDescription, 0, 100, '…', 'UTF-8') : 'No description added') ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="poll-product-name"><?= poll_escape($poll['product_name'] ?? 'No product linked') ?></td>
                                            <td><span class="poll-count"><?= (int) $poll['option_count'] ?></span></td>
                                            <td><strong class="poll-vote-count"><?= (int) $poll['vote_count'] ?></strong></td>
                                            <td><span class="poll-status poll-status-<?= poll_escape($effectiveStatus) ?>"><i></i><?= poll_escape(ucfirst($effectiveStatus)) ?></span></td>
                                            <td class="announcement-date"><?= $poll['created_at'] ? poll_escape(date('M j, Y', strtotime($poll['created_at']))) : '—' ?></td>
                                            <td><a href="<?= BASE_URL ?>/admin/polls/edit.php?id=<?= (int) $poll['id'] ?>" class="admin-secondary-btn poll-edit-link">Edit</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="category-table-footer">
                            <span id="poll-result-count">Showing <?= count($polls) ?> polls</span>
                            <span>Poll records · Live database</span>
                        </div>
                        <div class="category-no-results" id="poll-no-results" hidden>No polls match your search or filter.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> POLL MANAGEMENT</span></footer>
    </main>
</div>

<script>
const sidebarToggle = document.querySelector('#sidebar-toggle');
sidebarToggle?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});

const pollSearch = document.querySelector('#poll-search');
const pollStatusFilter = document.querySelector('#poll-status-filter');
const pollRows = [...document.querySelectorAll('[data-poll-row]')];

function filterPolls() {
    const query = (pollSearch?.value || '').trim().toLowerCase();
    const selectedStatus = pollStatusFilter?.value || 'all';
    let visibleCount = 0;

    pollRows.forEach((row) => {
        const matchesQuery = row.textContent.toLowerCase().includes(query);
        const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
        const visible = matchesQuery && matchesStatus;
        row.hidden = !visible;
        if (visible) visibleCount++;
    });

    const resultCount = document.querySelector('#poll-result-count');
    if (resultCount) {
        resultCount.textContent = 'Showing ' + visibleCount + ' of ' + pollRows.length + ' polls';
    }

    const noResults = document.querySelector('#poll-no-results');
    if (noResults) noResults.hidden = visibleCount !== 0;
}

pollSearch?.addEventListener('input', filterPolls);
pollStatusFilter?.addEventListener('change', filterPolls);
</script>
</body>
</html>
