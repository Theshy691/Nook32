<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_users.php');
    exit;
}

// Validate the security token
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Invalid security token. Please return and try again.');
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId || $userId < 1) {
    header('Location: manage_users.php?error=invalid');
    exit;
}

// Prevent the admin from deleting their own account
if ((int)$userId === (int)$_SESSION['user_id']) {
    header('Location: manage_users.php?error=self');
    exit;
}

// Check whether the account exists
$check = $conn->prepare(
    'SELECT user_id, username
     FROM users
     WHERE user_id = ?'
);
$check->bind_param('i', $userId);
$check->execute();

$user = $check->get_result()->fetch_assoc();
$check->close();

if (!$user) {
    header('Location: manage_users.php?error=notfound');
    exit;
}

// Delete the selected user
$delete = $conn->prepare(
    'DELETE FROM users WHERE user_id = ?'
);
$delete->bind_param('i', $userId);

if ($delete->execute()) {
    $delete->close();

    header('Location: manage_users.php?deleted=1');
    exit;
}

$delete->close();

http_response_code(500);
exit(
    'Unable to delete this account. It may be connected to existing records.'
);
?>
