<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Category';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$errors = [];

if (!$categoryId) {
    $_SESSION['category_toast'] = ['type' => 'error', 'message' => 'Please select a valid category to edit.'];
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

$findCategory = $pdo->prepare('SELECT id, name, slug, description, status FROM categories WHERE id = :id LIMIT 1');
$findCategory->execute(['id' => $categoryId]);
$category = $findCategory->fetch();

if (!$category) {
    $_SESSION['category_toast'] = ['type' => 'error', 'message' => 'The selected category could not be found.'];
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

$old = [
    'name' => $category['name'],
    'description' => $category['description'] ?? '',
    'status' => $category['status'] ?? 'inactive',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['status'] = $_POST['status'] ?? 'active';

    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }
    if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
        $errors[] = 'Please enter a category name containing no more than 100 characters.';
    }
    if (mb_strlen($old['description']) > 5000) {
        $errors[] = 'The category description must not exceed 5,000 characters.';
    }
    if (!in_array($old['status'], ['active', 'inactive'], true)) {
        $errors[] = 'Please select a valid category status.';
    }

    if (!$errors) {
        $baseSlug = strtolower(trim($old['name']));
        $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
        $baseSlug = trim($baseSlug, '-');
        if ($baseSlug === '') $baseSlug = 'category';
        $baseSlug = substr($baseSlug, 0, 120);
        $slug = $baseSlug;
        $slugNumber = 2;

        try {
            $slugCheck = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug AND id <> :id LIMIT 1');
            while (true) {
                $slugCheck->execute(['slug' => $slug, 'id' => $categoryId]);
                if (!$slugCheck->fetch()) break;
                $suffix = '-' . $slugNumber++;
                $slug = substr($baseSlug, 0, 120 - strlen($suffix)) . $suffix;
            }

            $update = $pdo->prepare(
                'UPDATE categories
                 SET name = :name, slug = :slug, description = :description, status = :status
                 WHERE id = :id'
            );
            $update->execute([
                'name' => $old['name'],
                'slug' => $slug,
                'description' => $old['description'] !== '' ? $old['description'] : null,
                'status' => $old['status'],
                'id' => $categoryId,
            ]);

            $_SESSION['category_toast'] = [
                'type' => 'success',
                'message' => 'Category "' . $old['name'] . '" has been updated successfully.',
            ];
            header('Location: ' . BASE_URL . '/admin/categories/index.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Sweet Spot category update failed: ' . $exception->getMessage());
            $errors[] = 'The category could not be updated. Please try again.';
        }
    }
}

$csrfToken = admin_csrf_token();
function category_edit_escape($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= category_edit_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($errors): ?>
    <div class="auth-toast is-visible" role="alert" aria-live="assertive">
        <span class="auth-toast-icon">!</span>
        <span><?= category_edit_escape($errors[0]) ?></span>
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
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link active" aria-current="page"><span class="side-icon">▦</span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#announcements" class="sidebar-link"><span class="side-icon">◉</span> Announcements</a>
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
                <input type="hidden" name="csrf_token" value="<?= category_edit_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Workspace <span>/</span> <a href="<?= BASE_URL ?>/admin/categories/index.php">Categories</a> <span>/</span> <strong>Edit category</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> CATEGORY MANAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= category_edit_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= category_edit_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>
        <div class="admin-content">
            <div class="page-heading add-category-heading">
                <div><p class="admin-kicker">MENU ORGANIZATION</p><h1>Edit a <em>category.</em></h1><p>Update the name, description, or status of this collection.</p></div>
                <a href="<?= BASE_URL ?>/admin/categories/index.php" class="admin-secondary-btn add-back-link">← Back to categories</a>
            </div>
            <form class="category-editor-form" action="<?= BASE_URL ?>/admin/categories/edit.php?id=<?= (int) $categoryId ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= category_edit_escape($csrfToken) ?>">
                <section class="admin-card editor-section category-editor-card">
                    <div class="editor-section-heading"><span class="editor-step">01</span><div><h2>Category information</h2><p>Update the details for this product collection.</p></div></div>
                    <div class="admin-field">
                        <label for="category-name">Category name <span>*</span></label>
                        <input type="text" id="category-name" name="name" maxlength="100" value="<?= category_edit_escape($old['name']) ?>" required autofocus>
                        <small class="field-hint">Use a short, recognizable name such as Pastries or Bread &amp; Buns.</small>
                    </div>
                    <div class="admin-field">
                        <label for="category-description">Description</label>
                        <textarea id="category-description" name="description" rows="5" maxlength="5000" placeholder="Briefly describe the products that belong in this category..."><?= category_edit_escape($old['description']) ?></textarea>
                    </div>
                    <div class="admin-field category-status-field">
                        <label for="category-status">Category status</label>
                        <select id="category-status" name="status">
                            <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active — available when adding products</option>
                            <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive — hidden from product selection</option>
                        </select>
                        <small class="field-hint">Products already assigned to this category will remain assigned if you make it inactive.</small>
                    </div>
                </section>
                <aside class="category-editor-note">
                    <span>✦ A little tip</span>
                    <p>Changing the category name also updates its generated slug. Products assigned to this category are not changed.</p>
                    <div class="category-slug-note"><small>CURRENT SLUG</small><strong><?= category_edit_escape($category['slug']) ?></strong><span>The slug is regenerated from the category name when you save.</span></div>
                </aside>
                <div class="editor-form-actions category-form-actions">
                    <a href="<?= BASE_URL ?>/admin/categories/index.php" class="admin-secondary-btn">Cancel</a>
                    <button type="submit" class="admin-primary-btn">Save changes <span>↗</span></button>
                </div>
            </form>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> CATEGORY MANAGEMENT</span></footer>
    </main>
</div>
<script>
document.querySelector('#sidebar-toggle')?.addEventListener('click', () => document.querySelector('#admin-sidebar')?.classList.toggle('is-open'));
document.querySelectorAll('.auth-toast-close').forEach(button => button.addEventListener('click', () => button.closest('.auth-toast')?.remove()));
window.setTimeout(() => document.querySelectorAll('.auth-toast').forEach(toast => toast.classList.remove('is-visible')), 5500);
</script>
</body>
</html>
