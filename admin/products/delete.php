<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
    exit;
}

$productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';
$confirmed = ($_POST['confirm_delete'] ?? '') === '1';

if (!$productId || !admin_verify_csrf_token($csrfToken) || !$confirmed) {
    $_SESSION['product_toast'] = [
        'type' => 'error',
        'message' => 'The delete request could not be verified. Please try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
    exit;
}

try {
    $pdo->beginTransaction();

    $findProduct = $pdo->prepare('SELECT id, name, image FROM products WHERE id = :id FOR UPDATE');
    $findProduct->execute(['id' => $productId]);
    $product = $findProduct->fetch();

    if (!$product) {
        $pdo->rollBack();
        $_SESSION['product_toast'] = [
            'type' => 'error',
            'message' => 'This product no longer exists or has already been deleted.',
        ];
        header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
        exit;
    }

    $deleteProduct = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $deleteProduct->execute(['id' => $productId]);
    $pdo->commit();

    // Remove only image files stored inside the product upload directory.
    if (!empty($product['image'])) {
        $uploadRoot = realpath(__DIR__ . '/../../uploads/products');
        $imagePath = __DIR__ . '/../../' . ltrim((string) $product['image'], '/\\');
        $imageRealPath = realpath($imagePath);

        if (
            $uploadRoot &&
            $imageRealPath &&
            strpos($imageRealPath, $uploadRoot . DIRECTORY_SEPARATOR) === 0 &&
            is_file($imageRealPath)
        ) {
            @unlink($imageRealPath);
        }
    }

    $_SESSION['product_toast'] = [
        'type' => 'success',
        'message' => 'Product "' . $product['name'] . '" has been deleted.',
    ];
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Sweet Spot product deletion failed: ' . $exception->getMessage());
    $_SESSION['product_toast'] = [
        'type' => 'error',
        'message' => 'The product could not be deleted. Please try again.',
    ];
}

header('Location: ' . BASE_URL . '/admin/dashboard.php#products');
exit;
