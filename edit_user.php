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

// Get the selected user
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

$isCurrentUser = (int)$user['user_id'] === (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate security token
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Invalid security token. Please return and try again.');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($fullName === '') {
        $message = 'Full name is required.';
        $messageType = 'error';
    } elseif (!in_array($role, ['admin', 'cashier'], true)) {
        $message = 'Invalid account role.';
        $messageType = 'error';
    } elseif ($isCurrentUser && $role !== 'admin') {
        $message = 'You cannot revoke your own admin role.';
        $messageType = 'error';
    } else {
        $update = $conn->prepare(
            'UPDATE users
             SET full_name = ?, role = ?
             WHERE user_id = ?'
        );

        $update->bind_param('ssi', $fullName, $role, $userId);

        if ($update->execute()) {
            $message = 'User profile updated successfully.';
            $messageType = 'success';

            if ($isCurrentUser) {
                $_SESSION['full_name'] = $fullName;
            }

            $user['full_name'] = $fullName;
            $user['role'] = $role;
        } else {
            $message = 'Unable to update the account.';
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
    <title>Edit Staff Member — Nook32 Coffee</title>
    
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
                        User Profile #<?= (int)$user['user_id'] ?>
                    </div>
                </div>
            </div>

            <!-- VIEW CONTAINER -->
            <div class="admin-view-container">
                
                <div class="admin-header-row">
                    <div class="admin-title-wrap">
                        <h1>Edit Staff Member</h1>
                        <p>Update full name and assigned terminal permissions for <strong>@<?= e($user['username']) ?></strong></p>
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

                <div class="content-panel-card" style="max-width:640px;">
                    <form method="POST" action="edit_user.php?id=<?= (int)$user['user_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?= e($user['username']) ?>"
                                disabled
                                style="opacity:0.7; cursor:not-allowed;"
                            >
                            <small style="font-size:11px; color:var(--text-light); margin-top:4px;">Usernames are permanent login handles.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control"
                                value="<?= e($user['full_name']) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="role">Assigned System Role</label>
                            <?php if ($isCurrentUser): ?>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="Administrator (You cannot change your own role)"
                                    disabled
                                    style="opacity:0.7; cursor:not-allowed;"
                                >
                                <input type="hidden" name="role" value="admin">
                            <?php else: ?>
                                <select id="role" name="role" class="form-select" required>
                                    <option value="cashier" <?= $user['role'] === 'cashier' ? 'selected' : '' ?>>
                                        Cashier (POS Register & Order History)
                                    </option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
                                        Administrator (Full Access & Inventory Control)
                                    </option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:28px;">
                            <?php if (!$isCurrentUser): ?>
                                <a href="reset_password.php?id=<?= (int)$user['user_id'] ?>" class="btn-sm-action">
                                    Reset Password Key
                                </a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>

                            <div style="display:flex; gap:12px;">
                                <a href="manage_users.php" class="btn-secondary-pill">Cancel</a>
                                <button type="submit" class="btn-apricot">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </main>

    </div>

</body>
</html>