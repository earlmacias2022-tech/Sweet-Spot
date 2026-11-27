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
$newStatus = $_POST['status'] ?? '';

if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['feedback_toast'] = [
        'type' => 'error',
        'message' => 'Your session expired. Please refresh the inbox and try again.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!$feedbackId || !in_array($newStatus, ['new', 'read', 'replied', 'archived'], true)) {
    $_SESSION['feedback_toast'] = [
        'type' => 'error',
        'message' => 'Invalid feedback action. Please try again.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $updateFeedback = $pdo->prepare(
        'UPDATE feedback SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $updateFeedback->execute([
        'status' => $newStatus,
        'id' => $feedbackId,
    ]);

    if ($updateFeedback->rowCount() > 0) {
        $_SESSION['feedback_toast'] = [
            'type' => 'success',
            'message' => 'Feedback status changed to ' . ucfirst($newStatus) . '.',
        ];
    } else {
        $checkFeedback = $pdo->prepare('SELECT id, status FROM feedback WHERE id = :id LIMIT 1');
        $checkFeedback->execute(['id' => $feedbackId]);
        $existingFeedback = $checkFeedback->fetch();

        $_SESSION['feedback_toast'] = $existingFeedback
            ? [
                'type' => 'success',
                'message' => 'This message is already marked as ' . ucfirst($existingFeedback['status']) . '.',
            ]
            : [
                'type' => 'error',
                'message' => 'That feedback message could not be found.',
            ];
    }
} catch (PDOException $exception) {
    error_log('Sweet Spot feedback status update failed: ' . $exception->getMessage());
    $_SESSION['feedback_toast'] = [
        'type' => 'error',
        'message' => 'The feedback status could not be updated. Please try again.',
    ];
}

header('Location: ' . $redirectUrl);
exit;
