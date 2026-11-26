<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? null;
$reviewId = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['review_toast'] = [
        'type' => 'error',
        'message' => 'Your session expired. Please refresh the page and try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

if (!$reviewId) {
    $_SESSION['review_toast'] = [
        'type' => 'error',
        'message' => 'Invalid review selected. Please try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

try {
    $deleteReview = $pdo->prepare('DELETE FROM reviews WHERE id = :id');
    $deleteReview->execute(['id' => $reviewId]);

    $_SESSION['review_toast'] = $deleteReview->rowCount() > 0
        ? [
            'type' => 'success',
            'message' => 'Customer review permanently deleted.',
        ]
        : [
            'type' => 'error',
            'message' => 'That review could not be found. It may already have been deleted.',
        ];
} catch (PDOException $exception) {
    error_log('Sweet Spot review deletion failed: ' . $exception->getMessage());
    $_SESSION['review_toast'] = [
        'type' => 'error',
        'message' => 'The review could not be deleted. Please try again.',
    ];
}

header('Location: ' . BASE_URL . '/admin/reviews/index.php');
exit;
