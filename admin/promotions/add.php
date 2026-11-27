<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Add Promotion';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$errors = [];
$old = [
    'title' => '',
    'description' => '',
    'discount_value' => '',
    'status' => 'draft',
    'start_date' => '',
    'end_date' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $default) {
        $old[$field] = trim((string) ($_POST[$field] ?? $default));
    }

    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }

    if ($old['title'] === '' || mb_strlen($old['title']) > 180) {
        $errors[] = 'Please enter a promotion title containing no more than 180 characters.';
    }

    if (strlen($old['description']) > 65535) {
        $errors[] = 'The promotion description is too long.';
    }

    if ($old['discount_value'] === '' || !is_numeric($old['discount_value'])) {
        $errors[] = 'Please enter a valid discount percentage.';
    } elseif ((float) $old['discount_value'] <= 0 || (float) $old['discount_value'] > 100) {
        $errors[] = 'The discount percentage must be greater than 0 and no more than 100.';
    }

    if (!in_array($old['status'], ['draft', 'active', 'expired', 'archived'], true)) {
        $errors[] = 'Please select a valid promotion status.';
    }

    $startDate = null;
    $endDate = null;

    if ($old['start_date'] !== '') {
        $parsedStart = DateTime::createFromFormat('Y-m-d\\TH:i', $old['start_date']);
        if (!$parsedStart || $parsedStart->format('Y-m-d\\TH:i') !== $old['start_date']) {
            $errors[] = 'Please enter a valid start date and time.';
        } else {
            $startDate = $parsedStart->format('Y-m-d H:i:s');
        }
    }

    if ($old['end_date'] !== '') {
        $parsedEnd = DateTime::createFromFormat('Y-m-d\\TH:i', $old['end_date']);
        if (!$parsedEnd || $parsedEnd->format('Y-m-d\\TH:i') !== $old['end_date']) {
            $errors[] = 'Please enter a valid end date and time.';
        } else {
            $endDate = $parsedEnd->format('Y-m-d H:i:s');
        }
    }

    if ($startDate !== null && $endDate !== null && strtotime($endDate) <= strtotime($startDate)) {
        $errors[] = 'The end date and time must be later than the start date and time.';
    }

    if (!$errors) {
        try {
            $insert = $pdo->prepare(
                'INSERT INTO promotions (title, description, discount_value, status, start_date, end_date)
                 VALUES (:title, :description, :discount_value, :status, :start_date, :end_date)'
            );

            $insert->execute([
                'title' => $old['title'],
                'description' => $old['description'] !== '' ? $old['description'] : null,
                'discount_value' => number_format((float) $old['discount_value'], 2, '.', ''),
                'status' => $old['status'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $_SESSION['promotion_toast'] = [
                'type' => 'success',
                'message' => 'Promotion "' . $old['title'] . '" has been created successfully.',
            ];

            header('Location: ' . BASE_URL . '/admin/promotions/index.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Sweet Spot promotion creation failed: ' . $exception->getMessage());
            $errors[] = 'The promotion could not be saved. Please try again.';
        }
    }
}

$csrfToken = admin_csrf_token();

function promotion_add_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= promotion_add_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($errors): ?>
    <div class="auth-toast is-visible" role="alert" aria-live="assertive">
        <span class="auth-toast-icon">!</span>
        <span><?= promotion_add_escape($errors[0]) ?></span>
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
            <a href="<?= BASE_URL ?>/admin/announcements/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#megaphone"></use></svg></span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#tag"></use></svg></span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#star"></use></svg></span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/polls/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chart"></use></svg></span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/feedback/index.php" class="sidebar-link"><span class="side-icon"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#message"></use></svg></span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#sparkles"></use></svg></span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#arrow-up-right"></use></svg> <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= promotion_add_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#log-out"></use></svg> <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#menu"></use></svg></button>
                <div class="breadcrumb">Workspace <span>/</span> <a href="<?= BASE_URL ?>/admin/promotions/index.php">Promotions</a> <span>/</span> <strong>Add promotion</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CONTENT MANAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= promotion_add_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= promotion_add_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron"><svg class="admin-icon" aria-hidden="true"><use href="<?= BASE_URL ?>/assets/images/admin-icons.svg#chevron-down"></use></svg></span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading add-announcement-heading">
                <div>
                    <p class="admin-kicker">A LITTLE EXTRA SWEETNESS</p>
                    <h1>Create a <em>promotion.</em></h1>
                    <p>Set up a special offer and choose when customers should see it.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="admin-secondary-btn add-back-link">← Back to promotions</a>
            </div>

            <form class="announcement-editor-form" action="<?= BASE_URL ?>/admin/promotions/add.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= promotion_add_escape($csrfToken) ?>">

                <section class="admin-card editor-section announcement-editor-card">
                    <div class="editor-section-heading">
                        <span class="editor-step">01</span>
                        <div><h2>Promotion details</h2><p>Describe the offer so customers know what is included.</p></div>
                    </div>

                    <div class="admin-field">
                        <label for="promotion-title">Promotion title <span>*</span></label>
                        <input type="text" id="promotion-title" name="title" maxlength="180" placeholder="e.g. Weekend pastry treat" value="<?= promotion_add_escape($old['title']) ?>" required autofocus>
                        <small class="field-hint">Maximum 180 characters.</small>
                    </div>

                    <div class="admin-field">
                        <label for="promotion-description">Description</label>
                        <textarea id="promotion-description" name="description" rows="8" placeholder="Explain which products or product types are included in this offer..."><?= promotion_add_escape($old['description']) ?></textarea>
                        <small class="field-hint">Optional. Use this space to explain which products or product types qualify. This promotion is informational and will not change product prices.</small>
                    </div>

                    <div class="admin-field promotion-discount-field">
                        <label for="promotion-discount">Discount percentage <span>*</span></label>
                        <div class="promotion-discount-input">
                            <input type="number" id="promotion-discount" name="discount_value" min="0.01" max="100" step="0.01" placeholder="e.g. 20" value="<?= promotion_add_escape($old['discount_value']) ?>" required>
                            <span>% OFF</span>
                        </div>
                        <small class="field-hint">Enter a value greater than 0 and up to 100. For example, 20 means 20% OFF.</small>
                    </div>
                </section>

                <aside class="announcement-editor-side">
                    <section class="admin-card editor-section announcement-settings-card">
                        <div class="editor-section-heading">
                            <span class="editor-step">02</span>
                            <div><h2>Promotion settings</h2><p>Choose the status and offer period.</p></div>
                        </div>
                        <div class="admin-field">
                            <label for="promotion-status">Status</label>
                            <select id="promotion-status" name="status">
                                <option value="draft" <?= $old['status'] === 'draft' ? 'selected' : '' ?>>Draft — keep hidden</option>
                                <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active — publish offer</option>
                                <option value="expired" <?= $old['status'] === 'expired' ? 'selected' : '' ?>>Expired — mark as ended</option>
                                <option value="archived" <?= $old['status'] === 'archived' ? 'selected' : '' ?>>Archived — keep hidden</option>
                            </select>
                        </div>
                        <div class="admin-field">
                            <label for="promotion-start">Start date and time</label>
                            <input type="datetime-local" id="promotion-start" name="start_date" value="<?= promotion_add_escape($old['start_date']) ?>">
                            <small class="field-hint">Leave empty if the offer can start immediately when active.</small>
                        </div>
                        <div class="admin-field">
                            <label for="promotion-end">End date and time</label>
                            <input type="datetime-local" id="promotion-end" name="end_date" value="<?= promotion_add_escape($old['end_date']) ?>">
                            <small class="field-hint">Leave empty if the offer has no scheduled end date.</small>
                        </div>
                    </section>

                    <div class="announcement-editor-note">
                        <span>✦ A quick reminder</span>
                        <p>The discount is displayed as an informational offer only. It does not change product prices or show an original price. Use the title and description to explain which products qualify.</p>
                    </div>
                </aside>

                <div class="editor-form-actions announcement-form-actions">
                    <a href="<?= BASE_URL ?>/admin/promotions/index.php" class="admin-secondary-btn">Cancel</a>
                    <button type="submit" class="admin-primary-btn">Save promotion <span>↗</span></button>
                </div>
            </form>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> PROMOTION MANAGEMENT</span></footer>
    </main>
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
</script>
</body>
</html>
