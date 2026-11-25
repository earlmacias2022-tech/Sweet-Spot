<?php
require_once __DIR__ . '/../config/auth.php';
admin_require_login();

$pageTitle = 'Dashboard';
$loginSuccess = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);

$sampleProducts = [
    ['id'=>1,'name'=>'Butter Croissant','category'=>'Pastries','price'=>85,'stock'=>'Available','image'=>'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=160&q=80'],
    ['id'=>2,'name'=>'Classic Ensaymada','category'=>'Bread & Buns','price'=>65,'stock'=>'Available','image'=>'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=160&q=80'],
    ['id'=>3,'name'=>'Chocolate Danish','category'=>'Pastries','price'=>95,'stock'=>'Available','image'=>'https://images.unsplash.com/photo-1519915028121-7d3463d5b3b7?auto=format&fit=crop&w=160&q=80'],
    ['id'=>4,'name'=>'Cinnamon Swirl','category'=>'Pastries','price'=>90,'stock'=>'Low stock','image'=>'https://images.unsplash.com/photo-1509365465985-25d11c17e812?auto=format&fit=crop&w=160&q=80'],
    ['id'=>5,'name'=>'Strawberry Shortcake','category'=>'Cakes','price'=>180,'stock'=>'Available','image'=>'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=160&q=80'],
    ['id'=>6,'name'=>'Country Sourdough','category'=>'Bread & Buns','price'=>160,'stock'=>'Available','image'=>'https://images.unsplash.com/photo-1585478259715-876acc5be8eb?auto=format&fit=crop&w=160&q=80']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= htmlspecialchars($pageTitle) ?> | Sweet Spot Admin</title>
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
            <a href="#overview" class="sidebar-link active" data-view="overview"><span class="side-icon">⌂</span> Overview</a>
            <a href="#products" class="sidebar-link" data-view="products"><span class="side-icon">▧</span> Products <span class="side-count">06</span></a>
            <a href="#categories" class="sidebar-link" data-view="categories"><span class="side-icon">▦</span> Categories</a>
            <span class="sidebar-section-label sidebar-spaced">CONTENT</span>
            <a href="#announcements" class="sidebar-link" data-view="announcements"><span class="side-icon">◉</span> Announcements</a>
            <a href="#promotions" class="sidebar-link" data-view="promotions"><span class="side-icon">✳</span> Promotions</a>
            <span class="sidebar-section-label sidebar-spaced">COMMUNITY</span>
            <a href="#reviews" class="sidebar-link" data-view="reviews"><span class="side-icon">☆</span> Reviews</a>
            <a href="#polls" class="sidebar-link" data-view="polls"><span class="side-icon">▤</span> Polls</a>
            <a href="#feedback" class="sidebar-link" data-view="feedback"><span class="side-icon">✉</span> Feedback <span class="side-count side-count-warm">3</span></a>
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
                <div class="breadcrumb">Workspace <span>/</span> <strong id="breadcrumb-current">Overview</strong></div>
            </div>
            <div class="topbar-right">
                <span class="preview-chip"><i></i> PREVIEW MODE</span>
                <button class="topbar-icon" type="button" aria-label="Notifications">♧<b></b></button>
                <div class="admin-profile"><span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['admin_username'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><div><strong><?= htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') ?></strong><small>Administrator</small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <div class="admin-content">
            <section class="admin-view active" id="view-overview">
                <div class="page-heading">
                    <div><p class="admin-kicker"><?= strtoupper(date('l, F j, Y')) ?></p><h1>Good morning, <em>Sweet Spot.</em></h1><p>Here's what's happening with your bakery today.</p></div>
                    <a href="#products" class="admin-primary-btn" data-view-link="products">Manage products <span>↗</span></a>
                </div>
                <div class="preview-banner"><span>✦</span><p><strong>You're viewing sample dashboard data.</strong> The numbers and records are for UI demonstration and are not connected to your database yet.</p><button type="button" class="banner-close" aria-label="Dismiss notice">×</button></div>

                <div class="stat-grid">
                    <article class="stat-card"><div class="stat-top"><span>Total products</span><span class="stat-icon">▧</span></div><strong id="stat-products">06</strong><small><span class="stat-neutral">↗</span> Items in your menu</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Categories</span><span class="stat-icon">▦</span></div><strong>03</strong><small>Organize your menu</small></article>
                    <article class="stat-card"><div class="stat-top"><span>Published reviews</span><span class="stat-icon">☆</span></div><strong>24</strong><small><span class="stat-positive">+4</span> Sample this month</small></article>
                    <article class="stat-card"><div class="stat-top"><span>New feedback</span><span class="stat-icon">✉</span></div><strong>03</strong><small><span class="stat-attention">Needs attention</span></small></article>
                </div>

                <div class="dashboard-grid">
                    <section class="admin-card recent-products">
                        <div class="card-heading"><div><span class="card-overline">YOUR CATALOG</span><h2>Recently added products</h2></div><a href="#products" class="subtle-link" data-view-link="products">View all ↗</a></div>
                        <div class="mini-product-list" id="recent-product-list">
                            <?php foreach (array_slice($sampleProducts,0,4) as $product): ?>
                            <div class="mini-product-row"><img src="<?= htmlspecialchars($product['image']) ?>" alt=""><div class="mini-product-info"><strong><?= htmlspecialchars($product['name']) ?></strong><small><?= htmlspecialchars($product['category']) ?></small></div><b>₱<?= number_format($product['price'],2) ?></b></div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <section class="admin-card quick-actions">
                        <div class="card-heading"><div><span class="card-overline">SHORTCUTS</span><h2>Quick actions</h2></div></div>
                        <a class="quick-action" href="<?= BASE_URL ?>/admin/products/add.php"><span class="quick-icon">＋</span><span><strong>Add a product</strong><small>Create a new menu entry</small></span><b>→</b></a>
                        <button class="quick-action" type="button" data-view-link="categories"><span class="quick-icon">▦</span><span><strong>Manage categories</strong><small>Keep your menu organized</small></span><b>→</b></button>
                        <button class="quick-action" type="button" data-view-link="announcements"><span class="quick-icon">◉</span><span><strong>Post an announcement</strong><small>Share a bakery update</small></span><b>→</b></button>
                    </section>
                </div>

                <section class="admin-card activity-card">
                    <div class="card-heading"><div><span class="card-overline">AT A GLANCE</span><h2>Workspace overview</h2></div><span class="status-label">ALL SYSTEMS PREVIEW</span></div>
                    <div class="overview-tiles"><div><span class="overview-dot dot-green"></span><strong>Product catalog</strong><small>Sample content ready</small></div><div><span class="overview-dot dot-gold"></span><strong>Content management</strong><small>UI planned next</small></div><div><span class="overview-dot dot-muted"></span><strong>Database connection</strong><small>Not connected yet</small></div></div>
                </section>
            </section>

            <section class="admin-view" id="view-products">
                <div class="page-heading products-page-heading"><div><p class="admin-kicker">YOUR BAKERY CATALOG</p><h1>Products <em>& menu.</em></h1><p>Add, update, and organize the treats shown on your website.</p></div><a href="<?= BASE_URL ?>/admin/products/add.php" class="admin-primary-btn">＋ Add product</a></div>
                <div class="preview-banner"><span>✦</span><p><strong>Product manager preview.</strong> Changes work in this browser session only. They will not be saved to MySQL until backend integration.</p><button type="button" class="banner-close" aria-label="Dismiss notice">×</button></div>
                <div class="product-manager-card">
                    <div class="manager-toolbar"><div class="manager-title"><span class="card-overline">MENU INVENTORY</span><h2>All products <span id="product-count">(6)</span></h2></div><div class="manager-controls"><div class="admin-search"><span>⌕</span><input id="product-search" type="search" placeholder="Search products..." aria-label="Search products"></div><select id="category-filter" aria-label="Filter by category"><option value="all">All categories</option><option>Pastries</option><option>Bread & Buns</option><option>Cakes</option></select></div></div>
                    <div class="table-scroll"><table class="product-table"><thead><tr><th>PRODUCT</th><th>CATEGORY</th><th>PRICE</th><th>STATUS</th><th class="action-column">ACTIONS</th></tr></thead><tbody id="product-table-body">
                    <?php foreach ($sampleProducts as $product): ?>
                    <tr data-product-id="<?= $product['id'] ?>"><td><div class="table-product"><img src="<?= htmlspecialchars($product['image']) ?>" alt=""><div><strong><?= htmlspecialchars($product['name']) ?></strong><small>SKU-SS-<?= str_pad((string)$product['id'],3,'0',STR_PAD_LEFT) ?></small></div></div></td><td><span class="category-pill"><?= htmlspecialchars($product['category']) ?></span></td><td class="table-price">₱<?= number_format($product['price'],2) ?></td><td><span class="product-status <?= $product['stock']==='Available'?'available':'low-stock' ?>"><i></i><?= htmlspecialchars($product['stock']) ?></span></td><td><div class="row-actions"><button type="button" class="row-action edit-product" aria-label="Edit <?= htmlspecialchars($product['name']) ?>" data-id="<?= $product['id'] ?>">Edit</button><button type="button" class="row-action delete-product" aria-label="Delete <?= htmlspecialchars($product['name']) ?>" data-id="<?= $product['id'] ?>">Delete</button></div></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <div class="table-footer"><span id="table-result-count">Showing 6 sample products</span><span>Sample catalog · Not saved to database</span></div>
                </div>
            </section>

            <section class="admin-view" id="view-categories"><div class="page-heading"><div><p class="admin-kicker">MENU ORGANIZATION</p><h1>Product <em>categories.</em></h1><p>Group bakery items into clear, easy-to-browse collections.</p></div></div><div class="module-placeholder"><span>▦</span><h2>Category management</h2><p>This module will let staff create, rename, and remove categories such as Pastries, Bread & Buns, and Cakes. The UI will be completed alongside the backend CRUD workflow.</p><span class="module-status">PLANNED MODULE</span></div></section>
            <section class="admin-view" id="view-announcements"><div class="page-heading"><div><p class="admin-kicker">KEEP CUSTOMERS INFORMED</p><h1>Announcements.</h1><p>Share bakery news, schedule updates, and important notices.</p></div></div><div class="module-placeholder"><span>◉</span><h2>Announcement management</h2><p>Create, edit, publish, schedule, and archive announcements that can appear on the public website.</p><span class="module-status">PLANNED MODULE</span></div></section>
            <section class="admin-view" id="view-promotions"><div class="page-heading"><div><p class="admin-kicker">A LITTLE EXTRA SWEETNESS</p><h1>Promotions.</h1><p>Manage special offers and limited-time campaigns.</p></div></div><div class="module-placeholder"><span>✳</span><h2>Promotion management</h2><p>Set up discounts, define campaign dates, and control whether a promotion is active, expired, or archived.</p><span class="module-status">PLANNED MODULE</span></div></section>
            <section class="admin-view" id="view-reviews"><div class="page-heading"><div><p class="admin-kicker">CUSTOMER LOVE</p><h1>Ratings <em>& reviews.</em></h1><p>Review customer comments before they appear on the website.</p></div></div><div class="module-placeholder"><span>☆</span><h2>Review moderation</h2><p>Staff will be able to approve, reject, and manage customer ratings and written reviews.</p><span class="module-status">PLANNED MODULE</span></div></section>
            <section class="admin-view" id="view-polls"><div class="page-heading"><div><p class="admin-kicker">HEAR WHAT PEOPLE THINK</p><h1>Customer <em>polls.</em></h1><p>Create simple polls and see what your customers prefer.</p></div></div><div class="module-placeholder"><span>▤</span><h2>Poll management</h2><p>Create questions, manage answer options, open or close polls, and review voting results.</p><span class="module-status">PLANNED MODULE</span></div></section>
            <section class="admin-view" id="view-feedback"><div class="page-heading"><div><p class="admin-kicker">MESSAGES FROM YOUR CUSTOMERS</p><h1>Customer <em>feedback.</em></h1><p>Keep track of questions, suggestions, and customer messages.</p></div></div><div class="module-placeholder"><span>✉</span><h2>Feedback inbox</h2><p>Review incoming messages and update their status as new, read, replied, or archived.</p><span class="module-status">PLANNED MODULE</span></div></section>
        </div>
        <footer class="admin-footer"><span>© <?= date('Y') ?> Sweet Spot Management</span><span>ADMIN WORKSPACE <i></i> UI PREVIEW</span></footer>
    </main>
</div>

<div class="modal-backdrop" id="product-modal" aria-hidden="true">
    <section class="product-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-heading"><div><span class="card-overline">MENU MANAGEMENT</span><h2 id="modal-title">Add a product</h2><p>Fill in the details for this bakery item.</p></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div>
        <form id="product-form">
            <input type="hidden" id="edit-product-id">
            <div class="admin-field"><label for="product-name">Product name</label><input type="text" id="product-name" placeholder="e.g. Almond Croissant" required></div>
            <div class="modal-form-grid"><div class="admin-field"><label for="product-category">Category</label><select id="product-category" required><option value="">Choose category</option><option>Pastries</option><option>Bread & Buns</option><option>Cakes</option></select></div><div class="admin-field"><label for="product-price">Price (₱)</label><input type="number" id="product-price" min="0" step="0.01" placeholder="0.00" required></div></div>
            <div class="admin-field"><label for="product-description">Short description</label><textarea id="product-description" rows="3" placeholder="Describe this product (optional)"></textarea></div>
            <div class="admin-field"><label for="product-image">Image URL</label><input type="url" id="product-image" placeholder="https://example.com/product.jpg"></div>
            <div class="admin-field"><label for="product-availability">Availability</label><select id="product-availability"><option>Available</option><option>Low stock</option><option>Unavailable</option></select></div>
            <div class="modal-actions"><button type="button" class="admin-secondary-btn" data-close-modal>Cancel</button><button type="submit" class="admin-primary-btn">Save product <span>↗</span></button></div>
            <p class="modal-preview-note">Preview only — this action updates temporary browser data, not your database.</p>
        </form>
    </section>
</div>
<div class="toast-message" id="admin-toast" role="status" aria-live="polite"></div>
<?php if ($loginSuccess !== ''): ?>
<script>
window.addEventListener("DOMContentLoaded", () => {
    const toast = document.querySelector("#admin-toast");
    toast.textContent = <?= json_encode($loginSuccess, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    toast.classList.add("show", "toast-success");
    window.setTimeout(() => toast.classList.remove("show"), 3500);
});
</script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>