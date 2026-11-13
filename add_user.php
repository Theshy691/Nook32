<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check the form's security token
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Invalid security token. Please reload the page and try again.');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'cashier';

    if ($fullName === '' || $username === '' || $password === '') {
        $message = 'Please complete all required fields.';
        $messageType = 'error';
    } elseif (!in_array($role, ['admin', 'cashier'], true)) {
        $message = 'Invalid user role selected.';
        $messageType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $messageType = 'error';
    } else {
        // Check whether the username is already used
        $check = $conn->prepare(
            'SELECT user_id FROM users WHERE username = ?'
        );
        $check->bind_param('s', $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = 'That username is already taken. Please choose another.';
            $messageType = 'error';
        } else {
            // Hash the password before saving it
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                'INSERT INTO users (full_name, username, password, role)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssss',
                $fullName,
                $username,
                $hashedPassword,
                $role
            );

            if ($stmt->execute()) {
                $message = 'User account created successfully! The staff member can now log in.';
                $messageType = 'success';
            } else {
                $message = 'Unable to create the account. Please check the database and try again.';
                $messageType = 'error';
            }

            $stmt->close();
        }

        $check->close();
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$activePage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Staff — Nook32 Coffee</title>
    
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
                        Account Provisioning
                    </div>
                </div>
            </div>

            <!-- VIEW CONTAINER -->
            <div class="admin-view-container">
                
                <div class="admin-header-row">
                    <div class="admin-title-wrap">
                        <h1>Add New Staff Member</h1>
                        <p>Create credentials for cashiers or additional store administrators</p>
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
                    <form method="POST" action="add_user.php">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control"
                                placeholder="e.g. Maria Santos"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="username">Username (Terminal Login)</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="e.g. maria"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Minimum 8 characters"
                                minlength="8"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="role">Assigned Role</label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="cashier" selected>Cashier (POS Register & Order History)</option>
                                <option value="admin">Administrator (Full Access & Inventory Control)</option>
                            </select>
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px;">
                            <a href="manage_users.php" class="btn-secondary-pill">Cancel</a>
                            <button type="submit" class="btn-apricot">Create Account</button>
                        </div>
                    </form>
                </div>

            </div>

        </main>

    </div>

</body>
</html>