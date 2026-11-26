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
$newStatus = $_POST['status'] ?? '';

if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['review_toast'] = [
        'type' => 'error',
        'message' => 'Your session expired. Please refresh the page and try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

if (!$reviewId || !in_array($newStatus, ['approved', 'rejected'], true)) {
    $_SESSION['review_toast'] = [
        'type' => 'error',
        'message' => 'Invalid review action. Please try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

try {
    $updateReview = $pdo->prepare(
        'UPDATE reviews SET status = :status WHERE id = :id'
    );
    $updateReview->execute([
        'status' => $newStatus,
        'id' => $reviewId,
    ]);

    if ($updateReview->rowCount() > 0) {
        $_SESSION['review_toast'] = [
            'type' => 'success',
            'message' => $newStatus === 'approved'
                ? 'Review approved and now visible on its product page.'
                : 'Review rejected and hidden from customers.',
        ];
    } else {
        $checkReview = $pdo->prepare('SELECT id FROM reviews WHERE id = :id LIMIT 1');
        $checkReview->execute(['id' => $reviewId]);

        $_SESSION['review_toast'] = $checkReview->fetch()
            ? [
                'type' => 'success',
                'message' => $newStatus === 'approved'
                    ? 'Review is already approved.'
                    : 'Review is already rejected.',
            ]
            : [
                'type' => 'error',
                'message' => 'That review could not be found.',
            ];
    }
} catch (PDOException $exception) {
    error_log('Sweet Spot review status update failed: ' . $exception->getMessage());
    $_SESSION['review_toast'] = [
        'type' => 'error',
        'message' => 'The review status could not be updated. Please try again.',
    ];
}

header('Location: ' . BASE_URL . '/admin/reviews/index.php');
exit;
