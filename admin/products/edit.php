<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Product';
$adminName = $_SESSION['admin_username'] ?? 'Administrator';
$errors = [];
$toast = $_SESSION['product_toast'] ?? null;
unset($_SESSION['product_toast']);

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$productId) {
    $_SESSION['product_toast'] = ['type' => 'error', 'message' => 'Please select a valid product to edit.'];
    header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
    exit;
}

try {
    $productQuery = $pdo->prepare(
        'SELECT id, category_id, name, slug, description, price, image, is_featured, is_available, status
         FROM products WHERE id = :id LIMIT 1'
    );
    $productQuery->execute(['id' => $productId]);
    $product = $productQuery->fetch();
} catch (PDOException $exception) {
    error_log('Sweet Spot product loading failed: ' . $exception->getMessage());
    $product = false;
    $errors[] = 'The product could not be loaded. Please try again later.';
}

if (!$product) {
    $_SESSION['product_toast'] = ['type' => 'error', 'message' => 'The product you selected could not be found.'];
    header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
    exit;
}

$old = [
    'name' => $product['name'],
    'category_id' => (string) ($product['category_id'] ?? ''),
    'description' => $product['description'] ?? '',
    'price' => $product['price'],
    'status' => $product['status'],
];
$isFeatured = (int) $product['is_featured'] === 1;
$isAvailable = (int) $product['is_available'] === 1;
$currentImage = $product['image'];

try {
    $categoryQuery = $pdo->prepare(
        "SELECT id, name, status FROM categories
         WHERE status = 'active' OR id = :current_category
         ORDER BY name ASC"
    );
    $categoryQuery->execute(['current_category' => (int) $product['category_id']]);
    $categories = $categoryQuery->fetchAll();
} catch (PDOException $exception) {
    error_log('Sweet Spot category loading failed: ' . $exception->getMessage());
    $categories = [];
    $errors[] = 'Categories could not be loaded. Please refresh the page or try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['category_id'] = trim($_POST['category_id'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['price'] = trim($_POST['price'] ?? '');
    $old['status'] = $_POST['status'] ?? 'draft';
    $isFeatured = isset($_POST['is_featured']);
    $isAvailable = isset($_POST['is_available']);

    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please refresh the page and submit again.';
    }

    if ($old['name'] === '' || mb_strlen($old['name']) > 150) {
        $errors[] = 'Please enter a product name containing no more than 150 characters.';
    }

    $categoryId = filter_var($old['category_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if (!$categoryId) {
        $errors[] = 'Please select a valid product category.';
    } else {
        $categoryCheck = $pdo->prepare(
            "SELECT id FROM categories
             WHERE id = :id AND (status = 'active' OR id = :current_category)
             LIMIT 1"
        );
        $categoryCheck->execute([
            'id' => $categoryId,
            'current_category' => (int) $product['category_id'],
        ]);
        if (!$categoryCheck->fetch()) {
            $errors[] = 'The selected category is unavailable. Please choose an active category.';
        }
    }

    if ($old['description'] !== '' && mb_strlen($old['description']) > 5000) {
        $errors[] = 'The product description must not exceed 5,000 characters.';
    }

    $price = filter_var($old['price'], FILTER_VALIDATE_FLOAT);
    if ($price === false || $price <= 0 || $price > 99999999.99) {
        $errors[] = 'Enter a valid price greater than zero.';
    }

    if (!in_array($old['status'], ['draft', 'published', 'archived'], true)) {
        $errors[] = 'Please select a valid publication status.';
    }

    $newImagePath = $currentImage;
    $uploadedFilePath = null;
    $image = $_FILES['image'] ?? null;

    if ($image && $image['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($image['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The image could not be uploaded. Please try again.';
        } elseif ($image['size'] > 5 * 1024 * 1024) {
            $errors[] = 'The product image must be 5 MB or smaller.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($image['tmp_name']);
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $imageInfo = @getimagesize($image['tmp_name']);

            if (!isset($allowedTypes[$mimeType]) || $imageInfo === false) {
                $errors[] = 'Please upload a valid JPG, PNG, or WEBP image.';
            } else {
                $uploadDirectory = __DIR__ . '/../../uploads/products';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $errors[] = 'The product image folder could not be created. Please check folder permissions.';
                } elseif (!is_writable($uploadDirectory)) {
                    $errors[] = 'The product image folder is not writable. Please check folder permissions.';
                } else {
                    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                    $destination = $uploadDirectory . '/' . $fileName;
                    if (move_uploaded_file($image['tmp_name'], $destination)) {
                        $newImagePath = 'uploads/products/' . $fileName;
                        $uploadedFilePath = $destination;
                    } else {
                        $errors[] = 'The product image could not be saved. Please try again.';
                    }
                }
            }
        }
    }

    if (!$errors) {
        $baseSlug = strtolower(trim($old['name']));
        $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
        $baseSlug = trim($baseSlug, '-');
        if ($baseSlug === '') {
            $baseSlug = 'product';
        }

        $slug = substr($baseSlug, 0, 170);
        $slugNumber = 2;
        $slugCheck = $pdo->prepare('SELECT id FROM products WHERE slug = :slug AND id <> :id LIMIT 1');

        while (true) {
            $slugCheck->execute(['slug' => $slug, 'id' => $productId]);
            if (!$slugCheck->fetch()) {
                break;
            }
            $suffix = '-' . $slugNumber++;
            $slug = substr($baseSlug, 0, 170 - strlen($suffix)) . $suffix;
        }

        try {
            $update = $pdo->prepare(
                'UPDATE products
                 SET category_id = :category_id,
                     name = :name,
                     slug = :slug,
                     description = :description,
                     price = :price,
                     image = :image,
                     is_featured = :is_featured,
                     is_available = :is_available,
                     status = :status
                 WHERE id = :id'
            );
            $update->execute([
                'category_id' => $categoryId,
                'name' => $old['name'],
                'slug' => $slug,
                'description' => $old['description'] !== '' ? $old['description'] : null,
                'price' => number_format((float) $price, 2, '.', ''),
                'image' => $newImagePath,
                'is_featured' => $isFeatured ? 1 : 0,
                'is_available' => $isAvailable ? 1 : 0,
                'status' => $old['status'],
                'id' => $productId,
            ]);

            if ($uploadedFilePath && $currentImage !== '') {
                $oldImagePath = __DIR__ . '/../../' . ltrim($currentImage, '/\\');
                $uploadRoot = realpath(__DIR__ . '/../../uploads/products');
                $oldRealPath = realpath($oldImagePath);
                if ($oldRealPath && $uploadRoot && strpos($oldRealPath, $uploadRoot . DIRECTORY_SEPARATOR) === 0 && is_file($oldRealPath)) {
                    @unlink($oldRealPath);
                }
            }

            $_SESSION['product_toast'] = [
                'type' => 'success',
                'message' => 'Product "' . $old['name'] . '" has been updated successfully.',
            ];
            header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
            exit;
        } catch (PDOException $exception) {
            if ($uploadedFilePath && is_file($uploadedFilePath)) {
                @unlink($uploadedFilePath);
            }
            error_log('Sweet Spot product update failed: ' . $exception->getMessage());
            $errors[] = 'The product could not be updated. Please try again.';
        }
    } elseif ($uploadedFilePath && is_file($uploadedFilePath)) {
        @unlink($uploadedFilePath);
    }
}

$csrfToken = admin_csrf_token();
function edit_product_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function edit_product_image_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    return BASE_URL . '/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= edit_product_escape($pageTitle) ?> | Sweet Spot Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="dashboard-page">
<?php if ($toast): ?>
    <div class="auth-toast toast-success is-visible" role="status" aria-live="polite">
        <span class="auth-toast-icon">✓</span>
        <span><?= edit_product_escape($toast['message'] ?? 'Product updated successfully.') ?></span>
        <button type="button" class="auth-toast-close" aria-label="Dismiss notification">×</button>
    </div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="auth-toast is-visible" role="alert" aria-live="assertive">
        <span class="auth-toast-icon">!</span>
        <span><?= edit_product_escape($errors[0]) ?></span>
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
            <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="sidebar-link active"><span class="side-icon">▧</span> Products</a>
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link"><span class="side-icon">▦</span> Categories</a>
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
                <input type="hidden" name="csrf_token" value="<?= edit_product_escape($csrfToken) ?>">
                <button type="submit" class="sidebar-logout">⇥ <span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
                <div class="breadcrumb">Workspace <span>/</span> <a href="<?= BASE_URL ?>/admin/dashboard.php#products">Products</a> <span>/</span> <strong>Edit product</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> PRODUCT MANAGEMENT</span>
                <div class="admin-profile"><span class="profile-avatar"><?= edit_product_escape(strtoupper(substr($adminName, 0, 1))) ?></span><div><strong><?= edit_product_escape($adminName) ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <div class="page-heading add-product-heading">
                <div>
                    <p class="admin-kicker">YOUR BAKERY CATALOG</p>
                    <h1>Edit <em>product.</em></h1>
                    <p>Update the details and settings for this menu item.</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="admin-secondary-btn add-back-link">← Back to products</a>
            </div>

            <?php if (!$categories): ?>
                <div class="form-preview-banner"><span>!</span><p><strong>No categories are available.</strong> Activate a category before changing this product's category.</p></div>
            <?php endif; ?>

            <form class="product-editor-form" action="<?= BASE_URL ?>/admin/products/edit.php?id=<?= (int) $productId ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= edit_product_escape($csrfToken) ?>">
                <div class="product-editor-main">
                    <section class="admin-card editor-section">
                        <div class="editor-section-heading">
                            <span class="editor-step">01</span>
                            <div><h2>Basic information</h2><p>Update the details customers will see.</p></div>
                        </div>
                        <div class="admin-field">
                            <label for="product-name">Product name <span>*</span></label>
                            <input type="text" id="product-name" name="name" placeholder="e.g. Classic Butter Croissant" maxlength="150" value="<?= edit_product_escape($old['name']) ?>" required>
                            <small class="field-hint">Choose a clear name that customers can easily recognize.</small>
                        </div>
                        <div class="admin-field">
                            <label for="product-category">Category <span>*</span></label>
                            <select id="product-category" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (string) $old['category_id'] === (string) $category['id'] ? 'selected' : '' ?>>
                                        <?= edit_product_escape($category['name']) ?><?= $category['status'] === 'inactive' ? ' (Inactive — current category)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="field-hint">Only active categories can be newly assigned. Your current category remains available if inactive.</small>
                        </div>
                        <div class="admin-field editor-description-field">
                            <label for="product-description">Description</label>
                            <textarea id="product-description" name="description" rows="5" placeholder="Tell customers what makes this bakery item special..." maxlength="5000"><?= edit_product_escape($old['description']) ?></textarea>
                            <small class="field-hint">A short, helpful description works best.</small>
                        </div>
                    </section>

                    <section class="admin-card editor-section">
                        <div class="editor-section-heading">
                            <span class="editor-step">02</span>
                            <div><h2>Pricing &amp; product image</h2><p>Update the selling price or replace the product photo.</p></div>
                        </div>
                        <div class="admin-field price-field">
                            <label for="product-price">Selling price <span>*</span></label>
                            <div class="price-input-wrap"><span>₱</span><input type="number" id="product-price" name="price" min="0.01" max="99999999.99" step="0.01" placeholder="0.00" value="<?= edit_product_escape($old['price']) ?>" required></div>
                        </div>
                        <div class="admin-field">
                            <label>Current product image</label>
                            <?php if ($currentImage): ?>
                                <div class="edit-image-preview"><img src="<?= edit_product_escape(edit_product_image_url($currentImage)) ?>" alt="<?= edit_product_escape($product['name']) ?>"><span>Current image</span></div>
                            <?php else: ?>
                                <p class="field-hint edit-no-image">No image has been uploaded for this product.</p>
                            <?php endif; ?>
                        </div>
                        <div class="admin-field">
                            <label for="product-image">Replace product image</label>
                            <label class="image-upload-box" for="product-image">
                                <span class="upload-icon">↑</span>
                                <strong id="upload-title">Choose a new image (optional)</strong>
                                <small id="upload-hint">JPG, PNG, or WEBP · Maximum file size: 5 MB</small>
                            </label>
                            <input class="visually-hidden-file" type="file" id="product-image" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small class="field-hint">Leave this empty to keep the current image.</small>
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
                            <label class="switch-control"><input type="checkbox" name="is_featured" <?= $isFeatured ? 'checked' : '' ?>><span></span></label>
                        </div>
                        <div class="setting-row">
                            <div><strong>Available to customers</strong><small>Allow customers to view this item as available.</small></div>
                            <label class="switch-control"><input type="checkbox" name="is_available" <?= $isAvailable ? 'checked' : '' ?>><span></span></label>
                        </div>
                        <div class="admin-field status-field">
                            <label for="product-status">Publication status</label>
                            <select id="product-status" name="status">
                                <option value="draft" <?= $old['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $old['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $old['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                    </section>
                    <section class="editor-tip-card">
                        <span>✦ A little tip</span>
                        <p>Review the product details before saving. Your changes will be reflected in the product catalog.</p>
                    </section>
                </aside>

                <div class="editor-form-actions">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php#products" class="admin-secondary-btn">Cancel</a>
                    <button type="submit" class="admin-primary-btn">Save changes <span>↗</span></button>
                </div>
            </form>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> PRODUCT MANAGEMENT</span></footer>
    </main>
</div>
<script>
document.querySelector('#sidebar-toggle')?.addEventListener('click', () => {
    document.querySelector('#admin-sidebar')?.classList.toggle('is-open');
});

const imageInput = document.querySelector('#product-image');
imageInput?.addEventListener('change', () => {
    const file = imageInput.files[0];
    document.querySelector('#upload-title').textContent = file ? file.name : 'Choose a new image (optional)';
    document.querySelector('#upload-hint').textContent = file
        ? (file.size <= 5 * 1024 * 1024 ? 'New image selected · ' + (file.size / 1024 / 1024).toFixed(2) + ' MB' : 'This file exceeds the 5 MB limit.')
        : 'JPG, PNG, or WEBP · Maximum file size: 5 MB';
});

document.querySelectorAll('.auth-toast-close').forEach(button => {
    button.addEventListener('click', () => button.closest('.auth-toast')?.remove());
});
window.setTimeout(() => {
    document.querySelectorAll('.auth-toast').forEach(toast => toast.classList.remove('is-visible'));
}, 5500);
</script>
</body>
</html>
