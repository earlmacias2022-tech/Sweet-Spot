<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

$redirectUrl = BASE_URL . '/admin/feedback/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? null;
$feedbackId = filter_var($_POST['feedback_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['feedback_toast'] = [
        'type' => 'error',
        'message' => 'Your session expired. Please refresh the inbox and try again.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!$feedbackId) {
    $_SESSION['feedback_toast'] = [
        'type' => 'error',
        'message' => 'Invalid feedback message selected. Please try again.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $deleteFeedback = $pdo->prepare('DELETE FROM feedback WHERE id = :id');
    $deleteFeedback->execute(['id' => $feedbackId]);

    $_SESSION['feedback_toast'] = $deleteFeedback->rowCount() > 0
        ? [
            'type' => 'success',
            'message' => 'Customer feedback message permanently deleted.',
        ]
        : [
            'type' => 'error',
            'message' => 'That feedback message could not be found. It may already have been deleted.',
        ];
} catch (PDOException $exception) {
    error_log('Sweet Spot feedback deletion failed: ' . $exception->getMessage());
    $_SESSION['feedback_toast'] = [
        'type' => 'error',
        'message' => 'The feedback message could not be deleted. Please try again.',
    ];
}

header('Location: ' . $redirectUrl);
exit;
