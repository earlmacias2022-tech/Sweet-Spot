<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Poll';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$errors = [];
$pollId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$pollId) {
    header('Location: ' . BASE_URL . '/admin/polls/index.php');
    exit;
}

try {
    $pollQuery = $pdo->prepare(
        'SELECT p.*, pr.name AS product_name,
                (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id) AS vote_count
         FROM polls p
         LEFT JOIN products pr ON pr.id = p.product_id
         WHERE p.id = :id
         LIMIT 1'
    );
    $pollQuery->execute(['id' => $pollId]);
    $poll = $pollQuery->fetch();

    if (!$poll) {
        header('Location: ' . BASE_URL . '/admin/polls/index.php');
        exit;
    }

    $optionQuery = $pdo->prepare(
        'SELECT id, option_text FROM poll_options WHERE poll_id = :poll_id ORDER BY id ASC'
    );
    $optionQuery->execute(['poll_id' => $pollId]);
    $savedOptions = $optionQuery->fetchAll();

    $productQuery = $pdo->prepare(
        "SELECT id, name, status, is_available FROM products
         WHERE (status = 'published' AND is_available = 1) OR id = :current_product
         ORDER BY name ASC"
    );
    $productQuery->execute(['current_product' => $poll['product_id']]);
    $products = $productQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot poll edit loading failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The poll could not be loaded. Please try again later.');
}

$voteCount = (int) $poll['vote_count'];
$old = [
    'product_id' => (string) $poll['product_id'],
    'question' => $poll['question'],
    'description' => $poll['description'] ?? '',
    'status' => $poll['status'],
    'start_date' => $poll['start_date'] ? date('Y-m-d\\TH:i', strtotime($poll['start_date'])) : '',
    'end_date' => $poll['end_date'] ? date('Y-m-d\\TH:i', strtotime($poll['end_date'])) : '',
];
$options = array_map(static fn($option) => $option['option_text'], $savedOptions);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $default) {
        $old[$field] = trim((string) ($_POST[$field] ?? $default));
    }

    $submittedOptions = $_POST['options'] ?? [];
    if ($voteCount === 0) {
        $options = is_array($submittedOptions)
            ? array_map(static fn($option) => trim((string) $option), $submittedOptions)
            : [];
    }

    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }

    $productId = filter_var($old['product_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if (!$productId) {
        $errors[] = 'Please select a valid product for this poll.';
    } else {
        $productCheck = $pdo->prepare(
            "SELECT id FROM products
             WHERE id = :id
               AND ((status = 'published' AND is_available = 1) OR id = :current_product)
             LIMIT 1"
        );
        $productCheck->execute([
            'id' => $productId,
            'current_product' => $poll['product_id'],
        ]);
        if (!$productCheck->fetch()) {
            $errors[] = 'The selected product is unavailable. Please choose another product.';
        }
    }

    if ($voteCount > 0 && (string) $productId !== (string) $poll['product_id']) {
        $errors[] = 'The linked product cannot be changed after customers have voted.';
    }

    if ($old['question'] === '' || mb_strlen($old['question']) > 255) {
        $errors[] = 'Please enter a poll question containing no more than 255 characters.';
    }

    if (mb_strlen($old['description']) > 5000) {
        $errors[] = 'The poll description must not exceed 5,000 characters.';
    }

    if ($voteCount === 0) {
        $options = array_values(array_filter($options, static fn($option) => $option !== ''));

        if (count($options) < 2) {
            $errors[] = 'Please provide at least two answer options.';
        } elseif (count($options) > 10) {
            $errors[] = 'A poll can have no more than 10 answer options.';
        }

        foreach ($options as $option) {
            if (mb_strlen($option) > 180) {
                $errors[] = 'Each answer option must not exceed 180 characters.';
                break;
            }
        }

        $normalizedOptions = array_map(
            static fn($option) => mb_strtolower($option, 'UTF-8'),
            $options
        );
        if (count($normalizedOptions) !== count(array_unique($normalizedOptions))) {
            $errors[] = 'Please remove duplicate answer options.';
        }
    }

    if (!in_array($old['status'], ['draft', 'active', 'closed'], true)) {
        $errors[] = 'Please select a valid poll status.';
    }

    $startDate = null;
    $endDate = null;

    if ($old['start_date'] !== '') {
        $parsedStart = DateTime::createFromFormat('Y-m-d\\TH:i', $old['start_date']);
        if (!$parsedStart || $parsedStart->format('Y-m-d\\TH:i') !== $old['start_date']) {
            $errors[] = 'Please enter a valid poll start date and time.';
        } else {
            $startDate = $parsedStart->format('Y-m-d H:i:s');
        }
    }

    if ($old['end_date'] !== '') {
        $parsedEnd = DateTime::createFromFormat('Y-m-d\\TH:i', $old['end_date']);
        if (!$parsedEnd || $parsedEnd->format('Y-m-d\\TH:i') !== $old['end_date']) {
            $errors[] = 'Please enter a valid poll end date and time.';
        } else {
            $endDate = $parsedEnd->format('Y-m-d H:i:s');
        }
    }

    if ($startDate !== null && $endDate !== null && strtotime($endDate) <= strtotime($startDate)) {
        $errors[] = 'The poll end date and time must be later than the start date and time.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $lockPoll = $pdo->prepare('SELECT id, product_id FROM polls WHERE id = :id FOR UPDATE');
            $lockPoll->execute(['id' => $pollId]);
            $lockedPoll = $lockPoll->fetch();

            if (!$lockedPoll) {
                throw new RuntimeException('Poll no longer exists.');
            }

            $voteCheck = $pdo->prepare('SELECT COUNT(*) FROM poll_votes WHERE poll_id = :poll_id');
            $voteCheck->execute(['poll_id' => $pollId]);
            $latestVoteCount = (int) $voteCheck->fetchColumn();

            if ($latestVoteCount > 0 && (string) $productId !== (string) $lockedPoll['product_id']) {
                throw new RuntimeException('The linked product cannot be changed after customers have voted. Please reload the page.');
            }

            $updatePoll = $pdo->prepare(
                'UPDATE polls
                 SET product_id = :product_id,
                     question = :question,
                     description = :description,
                     status = :status,
                     start_date = :start_date,
                     end_date = :end_date
                 WHERE id = :id'
            );
            $updatePoll->execute([
                'product_id' => $productId,
                'question' => $old['question'],
                'description' => $old['description'] !== '' ? $old['description'] : null,
                'status' => $old['status'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'id' => $pollId,
            ]);

            if ($latestVoteCount === 0) {
                $deleteOptions = $pdo->prepare('DELETE FROM poll_options WHERE poll_id = :poll_id');
                $deleteOptions->execute(['poll_id' => $pollId]);

                $insertOption = $pdo->prepare(
                    'INSERT INTO poll_options (poll_id, option_text) VALUES (:poll_id, :option_text)'
                );
                foreach ($options as $option) {
                    $insertOption->execute([
                        'poll_id' => $pollId,
                        'option_text' => $option,
                    ]);
                }
            }

            $pdo->commit();

            $_SESSION['poll_toast'] = [
                'type' => 'success',
                'message' => 'Poll has been updated successfully.',
            ];

            header('Location: ' . BASE_URL . '/admin/polls/index.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Sweet Spot poll update failed: ' . $exception->getMessage());
            $errors[] = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The poll could not be updated. Please try again.';
        }
    }
}

$csrfToken = admin_csrf_token();

function poll_edit_escape($value): string
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
    <title><?= poll_edit_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($errors): ?>
    <div class="auth-toast is-visible" role="alert" aria-live="assertive">
        <span class="auth-toast-icon">!</span>
        <span><?= poll_edit_escape($errors[0]) ?></span>
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
            <a href="<?= BASE_URL ?>/admin/feedback/index.php" class="sidebar-link"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= poll_edit_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Community <span>/</span> <a href="<?= BASE_URL ?>/admin/polls/index.php">Polls</a> <span>/</span> <strong>Edit poll</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CUSTOMER ENGAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= poll_edit_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= poll_edit_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading add-announcement-heading">
                <div>
                    <p class="admin-kicker">KEEP YOUR POLL UP TO DATE</p>
                    <h1>Edit <em>poll.</em></h1>
                    <p>Update the question, choices, and settings for this product poll.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/polls/index.php" class="admin-secondary-btn add-back-link">← Back to polls</a>
            </div>

            <?php if ($voteCount > 0): ?>
                <div class="form-preview-banner">
                    <span>!</span>
                    <p><strong>This poll already has <?= $voteCount ?> vote<?= $voteCount === 1 ? '' : 's' ?>.</strong> To protect existing results, its answer options and linked product are locked. You can still edit the question, description, status, and schedule.</p>
                </div>
            <?php endif; ?>

            <form class="announcement-editor-form" action="<?= BASE_URL ?>/admin/polls/edit.php?id=<?= (int) $pollId ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= poll_edit_escape($csrfToken) ?>">

                <section class="admin-card editor-section announcement-editor-card">
                    <div class="editor-section-heading">
                        <span class="editor-step">01</span>
                        <div><h2>Poll details</h2><p>Choose the product and update the question customers will answer.</p></div>
                    </div>
                    <div class="admin-field">
                        <label for="poll-product">Related product <span>*</span></label>
                        <select id="poll-product" name="product_id" required <?= $voteCount > 0 ? 'disabled' : '' ?>>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product['id'] ?>" <?= $old['product_id'] === (string) $product['id'] ? 'selected' : '' ?>>
                                    <?= poll_edit_escape($product['name']) ?><?= (int) $product['id'] === (int) $poll['product_id'] && ($product['status'] ?? '') !== 'published' ? ' (Current product)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-hint">Only published and available products can be newly selected.</small>
                    </div>
                    <div class="admin-field">
                        <label for="poll-question">Poll question <span>*</span></label>
                        <input type="text" id="poll-question" name="question" maxlength="255" value="<?= poll_edit_escape($old['question']) ?>" required>
                    </div>
                    <div class="admin-field">
                        <label for="poll-description">Description</label>
                        <textarea id="poll-description" name="description" rows="5" maxlength="5000"><?= poll_edit_escape($old['description']) ?></textarea>
                        <small class="field-hint">Optional. Maximum 5,000 characters.</small>
                    </div>
                </section>

                <section class="admin-card editor-section announcement-editor-card poll-options-editor">
                    <div class="editor-section-heading">
                        <span class="editor-step">02</span>
                        <div><h2>Answer options</h2><p><?= $voteCount > 0 ? 'Existing choices are shown below and cannot be changed after voting begins.' : 'Add at least two choices. Customers can select one answer.' ?></p></div>
                    </div>
                    <div class="poll-option-list" id="poll-option-list">
                        <?php foreach ($options as $index => $option): ?>
                            <div class="admin-field poll-option-field">
                                <label for="poll-option-<?= $index ?>">Option <?= $index + 1 ?></label>
                                <div class="poll-option-input-row">
                                    <input type="text" id="poll-option-<?= $index ?>" name="options[]" maxlength="180" value="<?= poll_edit_escape($option) ?>" <?= $voteCount > 0 ? 'disabled' : 'required' ?>>
                                    <button type="button" class="poll-option-remove" aria-label="Remove option <?= $index + 1 ?>" <?= $voteCount > 0 || count($options) <= 2 ? 'disabled' : '' ?>>×</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($voteCount === 0): ?>
                        <button type="button" class="admin-secondary-btn poll-add-option" id="poll-add-option">+ Add another option</button>
                        <small class="field-hint">You can add up to 10 options. Each option can contain up to 180 characters.</small>
                    <?php endif; ?>
                </section>

                <aside class="announcement-editor-side">
                    <section class="admin-card editor-section announcement-settings-card">
                        <div class="editor-section-heading">
                            <span class="editor-step">03</span>
                            <div><h2>Poll settings</h2><p>Choose when the poll should be available.</p></div>
                        </div>
                        <div class="admin-field">
                            <label for="poll-status">Status</label>
                            <select id="poll-status" name="status">
                                <option value="draft" <?= $old['status'] === 'draft' ? 'selected' : '' ?>>Draft — keep hidden</option>
                                <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active — make available</option>
                                <option value="closed" <?= $old['status'] === 'closed' ? 'selected' : '' ?>>Closed — stop voting</option>
                            </select>
                        </div>
                        <div class="admin-field">
                            <label for="poll-start">Start date and time</label>
                            <input type="datetime-local" id="poll-start" name="start_date" value="<?= poll_edit_escape($old['start_date']) ?>">
                        </div>
                        <div class="admin-field">
                            <label for="poll-end">End date and time</label>
                            <input type="datetime-local" id="poll-end" name="end_date" value="<?= poll_edit_escape($old['end_date']) ?>">
                        </div>
                    </section>
                    <div class="announcement-editor-note">
                        <span>✦ A quick reminder</span>
                        <p>Changing a poll's schedule or status controls when customers can participate. Existing votes are preserved when you save.</p>
                    </div>
                </aside>

                <div class="editor-form-actions announcement-form-actions">
                    <a href="<?= BASE_URL ?>/admin/polls/index.php" class="admin-secondary-btn">Cancel</a>
                    <button type="submit" class="admin-primary-btn">Save changes <span>↗</span></button>
                </div>
            </form>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> POLL MANAGEMENT</span></footer>
    </main>
</div>
<script>
document.querySelector('#sidebar-toggle')?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});

const optionList = document.querySelector('#poll-option-list');
const addOptionButton = document.querySelector('#poll-add-option');
const maximumOptions = 10;

function refreshPollOptions() {
    if (!optionList || !addOptionButton) return;
    const fields = [...optionList.querySelectorAll('.poll-option-field')];
    fields.forEach((field, index) => {
        const label = field.querySelector('label');
        const input = field.querySelector('input');
        const removeButton = field.querySelector('.poll-option-remove');
        const inputId = 'poll-option-' + index;
        label.textContent = 'Option ' + (index + 1);
        label.htmlFor = inputId;
        input.id = inputId;
        removeButton.disabled = fields.length <= 2;
        removeButton.setAttribute('aria-label', 'Remove option ' + (index + 1));
    });
    addOptionButton.disabled = fields.length >= maximumOptions;
    addOptionButton.textContent = fields.length >= maximumOptions
        ? 'Maximum of 10 options reached'
        : '+ Add another option';
}

addOptionButton?.addEventListener('click', () => {
    const fields = optionList.querySelectorAll('.poll-option-field');
    if (fields.length >= maximumOptions) return;
    const wrapper = document.createElement('div');
    wrapper.className = 'admin-field poll-option-field';
    wrapper.innerHTML = '<label></label><div class="poll-option-input-row"><input type="text" name="options[]" maxlength="180" placeholder="Enter an answer option..." required><button type="button" class="poll-option-remove" aria-label="Remove option">×</button></div>';
    optionList.appendChild(wrapper);
    refreshPollOptions();
    wrapper.querySelector('input')?.focus();
});

optionList?.addEventListener('click', (event) => {
    const removeButton = event.target.closest('.poll-option-remove');
    if (!removeButton || optionList.querySelectorAll('.poll-option-field').length <= 2) return;
    removeButton.closest('.poll-option-field').remove();
    refreshPollOptions();
});

refreshPollOptions();

document.querySelectorAll('.auth-toast-close').forEach(button => {
    button.addEventListener('click', () => button.closest('.auth-toast')?.remove());
});
window.setTimeout(() => {
    document.querySelectorAll('.auth-toast').forEach(toast => toast.classList.remove('is-visible'));
}, 5500);
</script>
</body>
</html>
