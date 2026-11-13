<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/config/database.php';

// Today's paid sales and paid orders
$todaySql = "SELECT
                COALESCE(SUM(total_amount), 0) AS sales,
                COUNT(*) AS orders
             FROM orders
             WHERE payment_status = 'paid'
               AND order_date >= CURDATE()
               AND order_date < CURDATE() + INTERVAL 1 DAY";

$todayResult = $conn->query($todaySql);
$today = $todayResult->fetch_assoc();

// Today's payment status summary
$statusSql = "SELECT
                payment_status,
                COUNT(*) AS total_orders
              FROM orders
              WHERE order_date >= CURDATE()
                AND order_date < CURDATE() + INTERVAL 1 DAY
              GROUP BY payment_status";

$statusResult = $conn->query($statusSql);

$paymentSummary = [
    'paid' => 0,
    'pending' => 0,
    'failed' => 0
];

while ($status = $statusResult->fetch_assoc()) {
    if (array_key_exists($status['payment_status'], $paymentSummary)) {
        $paymentSummary[$status['payment_status']] = (int) $status['total_orders'];
    }
}

// Today's top five best-selling products from paid orders
$bestSql = "SELECT
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) AS quantity_sold,
                SUM(oi.item_total) AS revenue
            FROM order_items oi
            INNER JOIN orders o
                ON o.order_id = oi.order_id
            WHERE o.payment_status = 'paid'
              AND o.order_date >= CURDATE()
              AND o.order_date < CURDATE() + INTERVAL 1 DAY
            GROUP BY oi.product_id, oi.product_name
            ORDER BY quantity_sold DESC, revenue DESC
            LIMIT 5";

$bestResult = $conn->query($bestSql);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard — Nook32 Coffee</title>
    
    <link rel="stylesheet" href="assets/css/purr_theme.css">
</head>
<body>

    <div class="app-viewport">
        
        <!-- SHARED PURR'COFFEE SIDEBAR -->
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="main-workspace">
            
            <!-- TOP BAR -->
            <div class="top-action-bar">
                <div style="font-size:14px; font-weight:700; color:var(--text-muted); display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent-apricot);">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span><?= date('l, F j, Y') ?></span>
                </div>

                <div class="top-bar-right">
                    <div class="store-live-pill">
                        Live Analytics
                    </div>

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

            <div class="admin-view-container">
                
                <div class="admin-header-row">
                    <div class="admin-title-wrap">
                        <h1>Executive Sales Dashboard</h1>
                        <p>Real-time revenue, completed transactions, and best seller reports</p>
                    </div>

                    <a href="index.php" class="btn-pill-filter" style="text-decoration:none;">
                        Open POS Register →
                    </a>
                </div>

                <!-- KPI METRIC CARDS -->
                <div class="metric-cards-grid">
                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Today's Paid Sales</div>
                        <div class="metric-card-value highlight-apricot">₱<?= number_format((float)$today['sales'], 2) ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Paid Transactions</div>
                        <div class="metric-card-value"><?= (int)$today['orders'] ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Pending QR Orders</div>
                        <div class="metric-card-value" style="color:var(--warning);"><?= (int)$paymentSummary['pending'] ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Average Ticket Size</div>
                        <div class="metric-card-value">
                            ₱<?= $today['orders'] > 0 ? number_format((float)$today['sales'] / (int)$today['orders'], 2) : '0.00' ?>
                        </div>
                    </div>
                </div>

                <!-- TOP BEST SELLERS TABLE -->
                <div class="data-table-card">
                    <div class="table-header-toolbar">
                        <span class="table-title">Top Best Selling Products Today</span>
                        <span style="font-size:12px; color:var(--text-muted);">Ranked by total quantity sold</span>
                    </div>

                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Product Item</th>
                                <th style="text-align:center;">Cups Sold</th>
                                <th style="text-align:right;">Generated Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bestResult->num_rows > 0): ?>
                                <?php while ($item = $bestResult->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-dark);"><?= e($item['product_name']) ?></div>
                                            <small style="color:var(--text-muted);">SKU #<?= (int)$item['product_id'] ?></small>
                                        </td>
                                        <td style="text-align:center; font-weight:700;">
                                            <span style="background:var(--accent-soft); color:var(--accent-apricot); padding:4px 12px; border-radius:var(--radius-pill); font-family:'JetBrains Mono',monospace;">
                                                <?= (int)$item['quantity_sold'] ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right; font-weight:800; font-family:'JetBrains Mono',monospace; color:var(--text-dark);">
                                            ₱<?= number_format((float)$item['revenue'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; padding:40px; color:var(--text-muted);">
                                        No sales recorded yet today. Complete orders on the POS register to see analytics.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>

    </div>

</body>
</html>
