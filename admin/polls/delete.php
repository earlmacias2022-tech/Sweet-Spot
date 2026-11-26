<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/polls/index.php');
    exit;
}

$pollId = filter_var($_POST['poll_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$csrfToken = $_POST['csrf_token'] ?? null;

if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['poll_toast'] = [
        'type' => 'error',
        'message' => 'Your session expired. Please refresh the page and try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/polls/index.php');
    exit;
}

if (!$pollId) {
    $_SESSION['poll_toast'] = [
        'type' => 'error',
        'message' => 'Invalid poll selected. Please try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/polls/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $findPoll = $pdo->prepare('SELECT id, question FROM polls WHERE id = :id FOR UPDATE');
    $findPoll->execute(['id' => $pollId]);
    $poll = $findPoll->fetch();

    if (!$poll) {
        $pdo->rollBack();
        $_SESSION['poll_toast'] = [
            'type' => 'error',
            'message' => 'That poll could not be found. It may already have been deleted.',
        ];
        header('Location: ' . BASE_URL . '/admin/polls/index.php');
        exit;
    }

    // Delete dependent records explicitly rather than relying on foreign-key cascades.
    $deleteVotes = $pdo->prepare('DELETE FROM poll_votes WHERE poll_id = :poll_id');
    $deleteVotes->execute(['poll_id' => $pollId]);

    $deleteOptions = $pdo->prepare('DELETE FROM poll_options WHERE poll_id = :poll_id');
    $deleteOptions->execute(['poll_id' => $pollId]);

    $deletePoll = $pdo->prepare('DELETE FROM polls WHERE id = :id');
    $deletePoll->execute(['id' => $pollId]);

    $pdo->commit();

    $_SESSION['poll_toast'] = [
        'type' => 'success',
        'message' => 'Poll "' . $poll['question'] . '" and its related votes have been deleted.',
    ];
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Sweet Spot poll deletion failed: ' . $exception->getMessage());
    $_SESSION['poll_toast'] = [
        'type' => 'error',
        'message' => 'The poll could not be deleted. Please try again.',
    ];
}

header('Location: ' . BASE_URL . '/admin/polls/index.php');
exit;
