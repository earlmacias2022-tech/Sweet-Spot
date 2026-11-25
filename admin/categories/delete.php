<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

$categoryId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';
$confirmed = ($_POST['confirm_delete'] ?? '') === '1';

if (!$categoryId || !admin_verify_csrf_token($csrfToken) || !$confirmed) {
    $_SESSION['category_toast'] = [
        'type' => 'error',
        'message' => 'The delete request could not be verified. Please try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $findCategory = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id FOR UPDATE');
    $findCategory->execute(['id' => $categoryId]);
    $category = $findCategory->fetch();

    if (!$category) {
        $pdo->rollBack();
        $_SESSION['category_toast'] = [
            'type' => 'error',
            'message' => 'This category no longer exists or has already been deleted.',
        ];
        header('Location: ' . BASE_URL . '/admin/categories/index.php');
        exit;
    }

    $findProducts = $pdo->prepare('SELECT id, image FROM products WHERE category_id = :category_id FOR UPDATE');
    $findProducts->execute(['category_id' => $categoryId]);
    $products = $findProducts->fetchAll();

    $deleteProducts = $pdo->prepare('DELETE FROM products WHERE category_id = :category_id');
    $deleteProducts->execute(['category_id' => $categoryId]);

    $deleteCategory = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $deleteCategory->execute(['id' => $categoryId]);

    $pdo->commit();

    // Clean up product images only after the database transaction succeeds.
    $uploadRoot = realpath(__DIR__ . '/../../uploads/products');
    if ($uploadRoot) {
        foreach ($products as $product) {
            if (empty($product['image'])) continue;

            $imagePath = __DIR__ . '/../../' . ltrim((string) $product['image'], '/\\');
            $imageRealPath = realpath($imagePath);

            if (
                $imageRealPath &&
                strpos($imageRealPath, $uploadRoot . DIRECTORY_SEPARATOR) === 0 &&
                is_file($imageRealPath)
            ) {
                @unlink($imageRealPath);
            }
        }
    }

    $deletedCount = count($products);
    $_SESSION['category_toast'] = [
        'type' => 'success',
        'message' => 'Category "' . $category['name'] . '" and ' . $deletedCount . ' associated product' . ($deletedCount === 1 ? '' : 's') . ' deleted successfully.',
    ];
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    error_log('Sweet Spot category deletion failed: ' . $exception->getMessage());
    $_SESSION['category_toast'] = [
        'type' => 'error',
        'message' => 'The category could not be deleted. No changes were saved.',
    ];
}

header('Location: ' . BASE_URL . '/admin/categories/index.php');
exit;
