<?php
/**
 * Shared Purr'Coffee Style Sidebar for Nook32 POS
 * Usage: $activePage = 'pos'; require __DIR__ . '/includes/sidebar.php';
 */
$userRole = $_SESSION['role'] ?? 'cashier';
$userName = $_SESSION['username'] ?? 'User';
$fullName = $_SESSION['full_name'] ?? 'Team Member';
$activePage = $activePage ?? 'pos';
?>
<aside class="app-sidebar">
    <div>
        <!-- BRAND -->
        <a href="index.php" class="sidebar-brand">
            <div class="brand-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                    <line x1="6" y1="1" x2="6" y2="4"></line>
                    <line x1="10" y1="1" x2="10" y2="4"></line>
                    <line x1="14" y1="1" x2="14" y2="4"></line>
                </svg>
            </div>
            <div class="brand-title-text">
                Nook32<span>.</span>
            </div>
        </a>

        <!-- NAV LINKS -->
        <nav class="nav-menu">
            <a href="index.php" class="nav-item <?= $activePage === 'pos' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
                <span>POS Menu</span>
            </a>

            <a href="order_history.php" class="nav-item <?= $activePage === 'orders' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Order History</span>
            </a>

            <?php if ($userRole === 'admin'): ?>
                <div class="nav-section-label">Management</div>

                <a href="dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="inventory.php" class="nav-item <?= $activePage === 'inventory' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>Inventory</span>
                </a>

                <a href="manage_users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Staff Users</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- SIDEBAR BOTTOM PROFILE & LOGOUT -->
    <div class="sidebar-bottom">
        <div class="user-profile-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <div class="user-info-text">
                <div class="user-info-name"><?= htmlspecialchars($fullName ?: $userName) ?></div>
                <div class="user-info-role"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

        <a href="logout.php" class="btn-nav-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Log out</span>
        </a>
    </div>
</aside>

