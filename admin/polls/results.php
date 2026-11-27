<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Poll Results';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$pollId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$poll = null;
$results = [];
$totalVotes = 0;
$loadError = false;

function poll_results_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($pollId) {
    try {
        $pollQuery = $pdo->prepare(
            "SELECT p.id, p.question, p.description, p.status, p.start_date, p.end_date,
                    p.created_at, pr.name AS product_name
             FROM polls p
             LEFT JOIN products pr ON pr.id = p.product_id
             WHERE p.id = :id
             LIMIT 1"
        );
        $pollQuery->execute(['id' => $pollId]);
        $poll = $pollQuery->fetch() ?: null;

        if ($poll) {
            $resultsQuery = $pdo->prepare(
                "SELECT po.id, po.option_text, COUNT(pv.id) AS vote_count
                 FROM poll_options po
                 LEFT JOIN poll_votes pv
                    ON pv.poll_option_id = po.id
                   AND pv.poll_id = po.poll_id
                 WHERE po.poll_id = :poll_id
                 GROUP BY po.id, po.option_text
                 ORDER BY po.id ASC"
            );
            $resultsQuery->execute(['poll_id' => (int) $poll['id']]);
            $results = $resultsQuery->fetchAll();
            $totalVotes = array_sum(array_map(
                static fn(array $result): int => (int) $result['vote_count'],
                $results
            ));
        }
    } catch (PDOException $exception) {
        error_log('Sweet Spot poll results loading failed: ' . $exception->getMessage());
        $loadError = true;
    }
} else {
    http_response_code(404);
}

$csrfToken = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= poll_results_escape($pageTitle) ?> | Sweet Spot Admin</title>
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
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#home"></use></svg></span> Overview</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#package"></use></svg></span> Products</a>
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#layers"></use></svg></span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#megaphone"></use></svg></span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#tag"></use></svg></span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#star"></use></svg></span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chart"></use></svg></span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#feedback" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#message"></use></svg></span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#sparkles"></use></svg></span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#arrow-up-right"></use></svg> <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= poll_results_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#log-out"></use></svg> <span>Sign out</span></button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#menu"></use></svg></button>
                <div class="breadcrumb">Community <span>/</span> <a href="<?= BASE_URL ?>/admin/polls/index.php">Polls</a> <span>/</span> <strong>Results</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CUSTOMER ENGAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= poll_results_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= poll_results_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chevron-down"></use></svg></span></div>
            </div>
        </header>
        <div class="admin-content">
            <div class="page-heading">
                <div><p class="admin-kicker">CUSTOMER ENGAGEMENT</p><h1>Poll <em>results.</em></h1><p>Review the responses collected for this product poll.</p></div>
                <a href="<?= BASE_URL ?>/admin/polls/index.php" class="admin-secondary-btn">← Back to polls</a>
            </div>
            <?php if ($loadError): ?>
                <div class="category-message category-message-error" role="alert">Poll results could not be loaded. Please check the database connection.</div>
            <?php elseif (!$poll): ?>
                <div class="category-empty-state"><span class="category-empty-icon">▤</span><h2>Poll not found</h2><p>The requested poll may have been deleted or the link is invalid.</p></div>
            <?php else: ?>
                <section class="poll-results-summary-card">
                    <div><span class="card-overline">POLL QUESTION</span><h2><?= poll_results_escape($poll['question']) ?></h2><p><?= poll_results_escape($poll['product_name'] ?? 'No product linked') ?></p></div>
                    <div class="poll-results-total"><strong><?= number_format($totalVotes) ?></strong><span><?= $totalVotes === 1 ? 'TOTAL VOTE' : 'TOTAL VOTES' ?></span></div>
                </section>
                <section class="poll-results-card">
                    <div class="card-heading"><div><span class="card-overline">RESPONSE BREAKDOWN</span><h2>Answer results</h2></div></div>
                    <?php if ($totalVotes === 0): ?>
                        <div class="category-empty-state poll-results-empty"><span class="category-empty-icon">◷</span><h2>No votes yet</h2><p>Results will appear here after customers submit their votes.</p></div>
                    <?php else: ?>
                        <div class="poll-results-list">
                            <?php foreach ($results as $result): ?>
                                <?php $votes = (int) $result['vote_count']; $percentage = (int) round(($votes / $totalVotes) * 100); ?>
                                <article class="poll-results-row">
                                    <div class="poll-results-label"><strong><?= poll_results_escape($result['option_text']) ?></strong><span><?= $percentage ?>%</span></div>
                                    <div class="poll-results-track" role="progressbar" aria-label="<?= poll_results_escape($result['option_text']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percentage ?>"><span style="width: <?= $percentage ?>%"></span></div>
                                    <small><?= number_format($votes) ?> <?= $votes === 1 ? 'vote' : 'votes' ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> POLL RESULTS</span></footer>
    </main>
</div>
<script>
document.querySelector('#sidebar-toggle')?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});
</script>
</body>
</html>
