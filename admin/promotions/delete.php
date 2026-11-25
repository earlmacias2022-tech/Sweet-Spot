<?php
require_once __DIR__ . '/../../config/auth.php';
admin_require_login();
require_once __DIR__ . '/../../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/admin/promotions/index.php'); exit; }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || !admin_verify_csrf_token($_POST['csrf_token'] ?? '') || ($_POST['confirm_delete'] ?? '') !== '1') {
    $_SESSION['promotion_toast'] = ['type'=>'error','message'=>'The delete request could not be verified. Please try again.'];
    header('Location: ' . BASE_URL . '/admin/promotions/index.php'); exit;
}
try {
    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT id, title FROM promotions WHERE id = :id FOR UPDATE');
    $find->execute(['id'=>$id]);
    $promotion = $find->fetch();
    if (!$promotion) {
        $pdo->rollBack();
        $_SESSION['promotion_toast'] = ['type'=>'error','message'=>'This promotion no longer exists or has already been deleted.'];
        header('Location: ' . BASE_URL . '/admin/promotions/index.php'); exit;
    }
    $delete = $pdo->prepare('DELETE FROM promotions WHERE id = :id');
    $delete->execute(['id'=>$id]);
    $pdo->commit();
    $_SESSION['promotion_toast'] = ['type'=>'success','message'=>'Promotion "' . $promotion['title'] . '" has been deleted successfully.'];
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Sweet Spot promotion deletion failed: ' . $exception->getMessage());
    $_SESSION['promotion_toast'] = ['type'=>'error','message'=>'The promotion could not be deleted. No changes were saved.'];
}
header('Location: ' . BASE_URL . '/admin/promotions/index.php');
exit;
