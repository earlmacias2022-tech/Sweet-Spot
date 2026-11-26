<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

function poll_vote_redirect(int $productId, string $type, string $message): void
{
    $_SESSION['poll_vote_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    if ($productId > 0) {
        header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#product-poll');
    } else {
        header('Location: ' . BASE_URL . '/index.php#products');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$pollId = filter_var($_POST['poll_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$optionId = filter_var($_POST['poll_option_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$csrfToken = $_POST['csrf_token'] ?? null;

$redirectProductId = is_int($productId) ? $productId : 0;

if (!admin_verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
    poll_vote_redirect($redirectProductId, 'error', 'Your session expired. Please refresh the page and try again.');
}

if (!is_int($productId) || !is_int($pollId) || !is_int($optionId)) {
    poll_vote_redirect($redirectProductId, 'error', 'Please choose one of the available answers and try again.');
}

$voterToken = $_COOKIE['sweet_spot_voter'] ?? '';
if (!is_string($voterToken) || !preg_match('/\\A[a-f0-9]{64}\\z/', $voterToken)) {
    poll_vote_redirect($productId, 'error', 'Your voting session could not be verified. Please refresh the product page and try again.');
}

$voterTokenHash = hash('sha256', $voterToken);
$pollNow = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    $pollCheck = $pdo->prepare(
        "SELECT polls.id
         FROM polls
         INNER JOIN products
             ON products.id = polls.product_id
         INNER JOIN categories
             ON categories.id = products.category_id
            AND categories.status = 'active'
         WHERE polls.id = :poll_id
           AND polls.product_id = :product_id
           AND polls.status = 'active'
           AND products.status = 'published'
           AND products.is_available = 1
           AND (polls.start_date IS NULL OR polls.start_date <= :poll_start)
           AND (polls.end_date IS NULL OR polls.end_date >= :poll_end)
         LIMIT 1
         FOR UPDATE"
    );
    $pollCheck->execute([
        'poll_id' => $pollId,
        'product_id' => $productId,
        'poll_start' => $pollNow,
        'poll_end' => $pollNow,
    ]);

    if (!$pollCheck->fetch()) {
        $pdo->rollBack();
        poll_vote_redirect($productId, 'error', 'This poll is no longer available for voting.');
    }

    $optionCheck = $pdo->prepare(
        'SELECT id FROM poll_options WHERE id = :option_id AND poll_id = :poll_id LIMIT 1'
    );
    $optionCheck->execute([
        'option_id' => $optionId,
        'poll_id' => $pollId,
    ]);

    if (!$optionCheck->fetch()) {
        $pdo->rollBack();
        poll_vote_redirect($productId, 'error', 'That answer does not belong to this poll. Please select a valid option.');
    }

    $insertVote = $pdo->prepare(
        'INSERT INTO poll_votes (poll_id, poll_option_id, voter_token_hash)
         VALUES (:poll_id, :option_id, :token_hash)'
    );
    $insertVote->execute([
        'poll_id' => $pollId,
        'option_id' => $optionId,
        'token_hash' => $voterTokenHash,
    ]);

    $pdo->commit();
    poll_vote_redirect($productId, 'success', 'Your vote has been recorded. Thank you for sharing your preference!');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $mysqlErrorCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : 0;
    if ($exception->getCode() === '23000' && $mysqlErrorCode === 1062) {
        poll_vote_redirect($productId, 'info', 'You have already voted in this poll from this browser. Thank you for participating!');
    }

    error_log('Sweet Spot poll vote submission failed: ' . $exception->getMessage());
    poll_vote_redirect($productId, 'error', 'We could not save your vote right now. Please try again.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Sweet Spot poll vote processing failed: ' . $exception->getMessage());
    poll_vote_redirect($productId, 'error', 'Something went wrong while recording your vote. Please try again.');
}
