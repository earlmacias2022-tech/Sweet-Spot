<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();

$pageTitle = 'Add Product';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Sweet Spot Admin</title>
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
            <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="sidebar-link active"><span class="side-icon">▧</span> Products</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#categories" class="sidebar-link"><span class="side-icon">▦</span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#announcements" class="sidebar-link"><span class="side-icon">◉</span> Announcements</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#promotions" class="sidebar-link"><span class="side-icon">✳</span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#reviews" class="sidebar-link"><span class="side-icon">☆</span> Reviews</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#polls" class="sidebar-link"><span class="side-icon">▤</span> Polls</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php#feedback" class="sidebar-link"><span class="side-icon">✉</span> Feedback</a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span>✦</span><div><strong>Need a hand?</strong><small>Management guide</small></div><b>↗</b></div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-website">↗ <span>View public website</span></a>
            <form action="<?= BASE_URL ?>/admin/logout.php" method="POST" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Workspace <span>/</span> <a href="<?= BASE_URL ?>/admin/dashboard.php#products">Products</a> <span>/</span> <strong>Add product</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> FORM PREVIEW</span>
                <div class="admin-profile"><span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($adminName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><div><strong><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading add-product-heading">
                <div>
                    <p class="admin-kicker">YOUR BAKERY CATALOG</p>
                    <h1>Add a <em>new product.</em></h1>
                    <p>Give your next bakery favorite a place on the menu.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="admin-secondary-btn add-back-link">← Back to products</a>
            </div>

            <div class="form-preview-banner">
                <span>✦</span>
                <p><strong>Form design preview.</strong> The layout is ready. Saving products and loading live categories will be connected in the next step.</p>
            </div>

            <form class="product-editor-form" action="#" method="POST" enctype="multipart/form-data">
                <div class="product-editor-main">
                    <section class="admin-card editor-section">
                        <div class="editor-section-heading">
                            <span class="editor-step">01</span>
                            <div><h2>Basic information</h2><p>Start with the details customers will see.</p></div>
                        </div>
                        <div class="admin-field">
                            <label for="product-name">Product name <span>*</span></label>
                            <input type="text" id="product-name" name="name" placeholder="e.g. Classic Butter Croissant" maxlength="150" required>
                            <small class="field-hint">Choose a clear name that customers can easily recognize.</small>
                        </div>
                        <div class="admin-field">
                            <label for="product-category">Category <span>*</span></label>
                            <select id="product-category" name="category_id" required>
                                <option value="">Select a category</option>
                                <option value="demo-pastries">Pastries · sample option</option>
                                <option value="demo-breads">Bread &amp; Buns · sample option</option>
                                <option value="demo-cakes">Cakes · sample option</option>
                            </select>
                            <small class="field-hint">These are visual placeholders. The next step will load active categories from your database.</small>
                        </div>
                        <div class="admin-field editor-description-field">
                            <label for="product-description">Description</label>
                            <textarea id="product-description" name="description" rows="5" placeholder="Tell customers what makes this bakery item special..." maxlength="5000"></textarea>
                            <small class="field-hint">A short, helpful description works best.</small>
                        </div>
                    </section>

                    <section class="admin-card editor-section">
                        <div class="editor-section-heading">
                            <span class="editor-step">02</span>
                            <div><h2>Pricing &amp; product image</h2><p>Set the selling price and choose a product photo.</p></div>
                        </div>
                        <div class="admin-field price-field">
                            <label for="product-price">Selling price <span>*</span></label>
                            <div class="price-input-wrap"><span>₱</span><input type="number" id="product-price" name="price" min="0.01" max="99999999.99" step="0.01" placeholder="0.00" required></div>
                        </div>
                        <div class="admin-field">
                            <label for="product-image">Product image</label>
                            <label class="image-upload-box" for="product-image">
                                <span class="upload-icon">↑</span>
                                <strong>Choose an image to upload</strong>
                                <small>JPG, PNG, or WEBP · Image upload will be enabled in the next step</small>
                            </label>
                            <input class="visually-hidden-file" type="file" id="product-image" name="image" accept=".jpg,.jpeg,.png,.webp" disabled>
                        </div>
                    </section>
                </div>

                <aside class="product-editor-side">
                    <section class="admin-card editor-section publish-card">
                        <div class="editor-section-heading">
                            <span class="editor-step">03</span>
                            <div><h2>Publishing</h2><p>Choose how this item appears.</p></div>
                        </div>
                        <div class="setting-row">
                            <div><strong>Featured product</strong><small>Highlight this item on the website.</small></div>
                            <label class="switch-control"><input type="checkbox" name="is_featured" disabled><span></span></label>
                        </div>
                        <div class="setting-row">
                            <div><strong>Available to customers</strong><small>Allow customers to view this item as available.</small></div>
                            <label class="switch-control"><input type="checkbox" name="is_available" checked disabled><span></span></label>
                        </div>
                        <div class="admin-field status-field">
                            <label for="product-status">Publication status</label>
                            <select id="product-status" name="status" disabled>
                                <option value="draft" selected>Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </section>
                    <section class="editor-tip-card">
                        <span>✦ A little tip</span>
                        <p>Use a bright, clear photo that shows the product well. Consistent product photos help your menu feel polished.</p>
                    </section>
                </aside>

                <div class="editor-form-actions">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="admin-secondary-btn">Cancel</a>
                    <button type="button" class="admin-primary-btn" disabled>Save product <span>↗</span></button>
                </div>
            </form>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> FORM PREVIEW</span></footer>
    </main>
</div>
<script>
document.querySelector('#sidebar-toggle').addEventListener('click', () => {
    document.querySelector('#admin-sidebar').classList.toggle('is-open');
});
</script>
</body>
</html>
