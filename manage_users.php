<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/config/database.php';

$sql = "SELECT user_id, full_name, username, role, created_at
        FROM users
        ORDER BY user_id ASC";

$result = $conn->query($sql);
$usersList = [];
$adminCount = 0;
$cashierCount = 0;

while ($row = $result->fetch_assoc()) {
    $usersList[] = $row;
    if ($row['role'] === 'admin') {
        $adminCount++;
    } else {
        $cashierCount++;
    }
}

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$isDeleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$errorMessage = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'self') {
        $errorMessage = 'Action blocked: You cannot delete your own logged-in administrator account.';
    } elseif ($_GET['error'] === 'self_reset') {
        $errorMessage = 'Notice: You cannot reset your own password from the staff manager.';
    } elseif ($_GET['error'] === 'notfound') {
        $errorMessage = 'The requested staff account could not be found.';
    } else {
        $errorMessage = 'An error occurred while processing the user account.';
    }
}
$activePage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff User Management — Nook32 Coffee</title>
    
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
                <div class="search-pill-box">
                    <svg class="search-icon-pos" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input
                        type="search"
                        class="search-pill-input"
                        placeholder="Search team members by name or username..."
                        oninput="filterStaffTable(this.value)"
                    >
                </div>

                <div class="top-bar-right">
                    <a href="add_user.php" class="btn-pill-filter" style="text-decoration:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add New Staff</span>
                    </a>

                    <div class="user-profile-header-pill">
                        <div class="user-avatar" style="width:34px; height:34px; font-size:13px; background:var(--dark-pill); color:#ffffff;">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div style="line-height:1.2;">
                            <div style="font-size:13px; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin') ?></div>
                            <div style="font-size:11px; color:var(--text-muted); text-transform:capitalize;"><?= htmlspecialchars($_SESSION['role'] ?? 'admin') ?> console</div>
                        </div>
                        <a href="logout.php" title="Sign Out" style="color:var(--text-light); text-decoration:none; margin-left:6px; display:flex; align-items:center;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="1.5"></circle>
                                <circle cx="12" cy="5" r="1.5"></circle>
                                <circle cx="12" cy="19" r="1.5"></circle>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- VIEW CONTAINER -->
            <div class="admin-view-container">
                
                <div class="admin-header-row">
                    <div class="admin-title-wrap">
                        <h1>Staff & System Accounts</h1>
                        <p>Assign cashier and administrative terminal permissions</p>
                    </div>

                    <a href="add_user.php" class="btn-apricot">
                        + Register New User
                    </a>
                </div>

                <?php if ($isDeleted): ?>
                    <div class="alert-banner success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Staff account has been successfully removed from the system.</span>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert-banner error">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <!-- METRICS ROW -->
                <div class="metric-cards-grid">
                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Total System Users</div>
                        <div class="metric-card-value"><?= count($usersList) ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Admin Accounts</div>
                        <div class="metric-card-value highlight-apricot"><?= $adminCount ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Active Cashiers</div>
                        <div class="metric-card-value"><?= $cashierCount ?></div>
                    </div>
                </div>

                <!-- USERS TABLE CARD -->
                <div class="data-table-card">
                    <div class="table-header-toolbar">
                        <span class="table-title">Registered Staff Accounts</span>
                        <span style="font-size:12px; color:var(--text-muted);">Manage login credentials and system roles</span>
                    </div>

                    <table class="clean-table" id="staffTable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Team Member</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usersList) > 0): ?>
                                <?php foreach ($usersList as $user): ?>
                                    <?php $isSelf = (int)$user['user_id'] === (int)$_SESSION['user_id']; ?>
                                    <tr class="staff-row" data-search="<?= strtolower(e($user['full_name'] . ' ' . $user['username'] . ' ' . $user['role'])) ?>">
                                        <td style="font-family:'JetBrains Mono',monospace; font-size:12.5px; color:var(--text-muted);">
                                            #USR-<?= str_pad((string)$user['user_id'], 3, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <div class="user-avatar" style="width:36px; height:36px; font-size:14px; background:<?= $user['role'] === 'admin' ? 'var(--dark-pill); color:#ffffff;' : 'var(--accent-soft); color:var(--accent-apricot);' ?>">
                                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight:700; color:var(--text-dark); font-size:14px;">
                                                        <?= e($user['full_name']) ?>
                                                        <?php if ($isSelf): ?>
                                                            <span style="font-size:10px; background:var(--surface-subtle); border:1px solid var(--border); padding:2px 6px; border-radius:6px; color:var(--text-muted); margin-left:4px;">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-family:'JetBrains Mono',monospace; font-size:13px; color:var(--text-dark);">
                                            @<?= e($user['username']) ?>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span style="background:var(--dark-pill); color:#ffffff; padding:4px 12px; border-radius:var(--radius-pill); font-size:11.5px; font-weight:700;">
                                                    Admin
                                                </span>
                                            <?php else: ?>
                                                <span style="background:var(--accent-soft); color:var(--accent-apricot); padding:4px 12px; border-radius:var(--radius-pill); font-size:11.5px; font-weight:700;">
                                                    Cashier
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:12.5px; color:var(--text-muted);">
                                            <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; align-items:center; gap:8px; justify-content:flex-end;">
                                                <a href="edit_user.php?id=<?= (int)$user['user_id'] ?>" class="btn-sm-action">
                                                    Edit
                                                </a>

                                                <?php if (!$isSelf): ?>
                                                    <a href="reset_password.php?id=<?= (int)$user['user_id'] ?>" class="btn-sm-action">
                                                        Reset Key
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        action="delete_user.php"
                                                        onsubmit="return confirm('Permanently remove staff account <?= addslashes(e($user['username'])) ?>?');"
                                                        style="margin:0; display:inline;"
                                                    >
                                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                                                        <button type="submit" class="btn-danger-pill">
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
                                        No staff accounts found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>

    </div>

    <script>
        function filterStaffTable(query) {
            query = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.staff-row');
            rows.forEach(row => {
                const search = row.getAttribute('data-search') || '';
                if (search.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>