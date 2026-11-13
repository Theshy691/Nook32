<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/config/database.php';

$message = '';
$messageType = '';

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId || $userId < 1) {
    http_response_code(400);
    exit('Invalid user ID.');
}

// Fetch the selected user
$stmt = $conn->prepare(
    'SELECT user_id, full_name, username, role
     FROM users
     WHERE user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: manage_users.php?error=notfound');
    exit;
}

// Prevent the admin from resetting their own password here
if ((int)$user['user_id'] === (int)$_SESSION['user_id']) {
    header('Location: manage_users.php?error=self_reset');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Invalid security token. Please return and try again.');
    }

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'The passwords do not match. Please re-enter carefully.';
        $messageType = 'error';
    } else {
        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $update = $conn->prepare(
            'UPDATE users SET password = ? WHERE user_id = ?'
        );
        $update->bind_param('si', $hashedPassword, $userId);

        if ($update->execute()) {
            $message = 'Password reset successfully! The user can now log in with the new password.';
            $messageType = 'success';
        } else {
            $message = 'Unable to reset the password. Please try again.';
            $messageType = 'error';
        }

        $update->close();
    }
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$activePage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Staff Password — Nook32 Coffee</title>
    
    <link rel="stylesheet" href="assets/css/purr_theme.css">
</head>
<body>

    <div class="app-viewport">
        
        <!-- 1. LEFT SIDEBAR (SHARED) -->
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <!-- 2. MAIN WORKSPACE -->
        <main class="main-workspace">
            
            <!-- TOP BAR -->
            <div class="top-action-bar">
                <a href="manage_users.php" class="btn-sm-action">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back to Staff Registry</span>
                </a>

                <div class="top-bar-right">
                    <div class="store-live-pill">
                        Security Reset
                    </div>
                </div>
            </div>

            <!-- VIEW CONTAINER -->
            <div class="admin-view-container">
                
                <div class="admin-header-row">
                    <div class="admin-title-wrap">
                        <h1>Reset Staff Password</h1>
                        <p>Generate a new secure login key for <strong><?= e($user['full_name']) ?> (@<?= e($user['username']) ?>)</strong></p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert-banner <?= e($messageType) ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($messageType === 'success'): ?>
                                <polyline points="20 6 9 17 4 12"></polyline>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            <?php endif; ?>
                        </svg>
                        <span><?= e($message) ?></span>
                    </div>
                <?php endif; ?>

                <div class="content-panel-card" style="max-width:580px;">
                    <form method="POST" action="reset_password.php?id=<?= (int)$user['user_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="form-control"
                                placeholder="Minimum 8 characters"
                                minlength="8"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control"
                                placeholder="Re-type new password"
                                minlength="8"
                                required
                            >
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px;">
                            <a href="manage_users.php" class="btn-secondary-pill">Cancel</a>
                            <button type="submit" class="btn-apricot">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>

        </main>

    </div>

</body>
</html>