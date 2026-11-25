<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/announcements/index.php');
    exit;
}

$announcementId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';
$confirmed = ($_POST['confirm_delete'] ?? '') === '1';

if (!$announcementId || !admin_verify_csrf_token($csrfToken) || !$confirmed) {
    $_SESSION['announcement_toast'] = [
        'type' => 'error',
        'message' => 'The delete request could not be verified. Please try again.',
    ];
    header('Location: ' . BASE_URL . '/admin/announcements/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $findAnnouncement = $pdo->prepare('SELECT id, title FROM announcements WHERE id = :id FOR UPDATE');
    $findAnnouncement->execute(['id' => $announcementId]);
    $announcement = $findAnnouncement->fetch();

    if (!$announcement) {
        $pdo->rollBack();
        $_SESSION['announcement_toast'] = [
            'type' => 'error',
            'message' => 'This announcement no longer exists or has already been deleted.',
        ];
        header('Location: ' . BASE_URL . '/admin/announcements/index.php');
        exit;
    }

    $deleteAnnouncement = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
    $deleteAnnouncement->execute(['id' => $announcementId]);

    $pdo->commit();

    $_SESSION['announcement_toast'] = [
        'type' => 'success',
        'message' => 'Announcement "' . $announcement['title'] . '" has been deleted successfully.',
    ];
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Sweet Spot announcement deletion failed: ' . $exception->getMessage());
    $_SESSION['announcement_toast'] = [
        'type' => 'error',
        'message' => 'The announcement could not be deleted. No changes were saved.',
    ];
}

header('Location: ' . BASE_URL . '/admin/announcements/index.php');
exit;
