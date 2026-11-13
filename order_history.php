<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['admin', 'cashier'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/config/database.php';

// Default to today's date
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$dateObject = DateTime::createFromFormat('!Y-m-d', $selectedDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
}

// Get orders for the selected date
$sql = "
    SELECT
        o.order_id,
        o.order_date,
        o.subtotal,
        o.discount_amount,
        o.total_amount,
        o.payment_method,
        o.payment_reference,
        o.payment_status,
        o.cash_received,
        o.change_amount,
        GROUP_CONCAT(
            CONCAT(oi.product_name, ' × ', oi.quantity)
            ORDER BY oi.order_item_id
            SEPARATOR ', '
        ) AS item_names
    FROM orders o
    LEFT JOIN order_items oi
        ON o.order_id = oi.order_id
    WHERE o.order_date >= ?
      AND o.order_date < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY
        o.order_id,
        o.order_date,
        o.subtotal,
        o.discount_amount,
        o.total_amount,
        o.payment_method,
        o.payment_reference,
        o.payment_status,
        o.cash_received,
        o.change_amount
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $selectedDate, $selectedDate);
$stmt->execute();
$result = $stmt->get_result();

$ordersList = [];
$totalDayRevenue = 0.0;
$pendingQrCount = 0;
$paidOrdersCount = 0;

while ($row = $result->fetch_assoc()) {
    $ordersList[] = $row;
    if ($row['payment_status'] === 'paid') {
        $totalDayRevenue += (float)$row['total_amount'];
        $paidOrdersCount++;
    } elseif ($row['payment_status'] === 'pending') {
        $pendingQrCount++;
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

$message = $_GET['message'] ?? '';
$activePage = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History & Receipts — Nook32 Coffee</title>
    
    <link rel="stylesheet" href="assets/css/purr_theme.css">
</head>
<body>

    <div class="app-viewport">
        
        <!-- 1. LEFT SIDEBAR (SHARED) -->
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <!-- 2. MAIN WORKSPACE -->
        <main class="main-workspace">
            
            <!-- TOP BAR (FILTER BY DATE & SEARCH) -->
            <div class="top-action-bar">
                <form method="GET" action="order_history.php" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px; background:var(--surface-subtle); border:1px solid var(--border); padding:4px 14px; border-radius:var(--radius-pill);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted);">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <input
                            type="date"
                            name="date"
                            value="<?= e($selectedDate) ?>"
                            style="border:none; background:transparent; font-family:inherit; font-size:13px; font-weight:700; color:var(--text-dark); outline:none; cursor:pointer;"
                            onchange="this.form.submit()"
                        >
                    </div>

                    <button type="submit" class="btn-sm-action">
                        Filter Date
                    </button>

                    <?php if ($selectedDate !== date('Y-m-d')): ?>
                        <a href="order_history.php" class="btn-sm-action" style="background:var(--accent-soft); color:var(--accent-apricot); border-color:var(--accent-apricot);">
                            Reset to Today
                        </a>
                    <?php endif; ?>
                </form>

                <div class="top-bar-right">
                    <div class="search-pill-box" style="max-width:280px;">
                        <svg class="search-icon-pos" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input
                            type="search"
                            class="search-pill-input"
                            placeholder="Search orders, items..."
                            style="height:40px; font-size:13px;"
                            oninput="filterOrderTable(this.value)"
                        >
                    </div>

                    <div class="user-profile-header-pill">
                        <div class="user-avatar" style="width:34px; height:34px; font-size:13px; background:<?= $userRole === 'admin' ? 'var(--dark-pill); color:#ffffff;' : 'var(--accent-soft); color:var(--accent-apricot);' ?>">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div style="line-height:1.2;">
                            <div style="font-size:13px; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></div>
                            <div style="font-size:11px; color:var(--text-muted); text-transform:capitalize;"><?= htmlspecialchars($userRole) ?> console</div>
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
                        <h1>Order Transactions & Audit</h1>
                        <p>Showing records for <strong><?= date('F j, Y', strtotime($selectedDate)) ?></strong> <?= $selectedDate === date('Y-m-d') ? '(Today)' : '' ?></p>
                    </div>

                    <a href="index.php" class="btn-apricot">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>New Sale (POS)</span>
                    </a>
                </div>

                <!-- ALERTS -->
                <?php if ($message === 'verified'): ?>
                    <div class="alert-banner success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>Payment verified! Digital QR transaction marked as paid and stock deductions verified.</span>
                    </div>
                <?php elseif ($message === 'stock_error'): ?>
                    <div class="alert-banner error">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>Cannot verify payment: Insufficient inventory stock or missing recipe ingredients for this order. Please check and restock ingredients in Inventory.</span>
                    </div>
                <?php elseif ($message === 'unchanged'): ?>
                    <div class="alert-banner warning">
                        <span>This order could not be updated. It may already be paid or is not a pending QR order.</span>
                    </div>
                <?php elseif ($message === 'invalid' || $message === 'error'): ?>
                    <div class="alert-banner error">
                        <span>Unable to verify the selected order. Please check the transaction and try again.</span>
                    </div>
                <?php endif; ?>

                <!-- KPI METRIC CARDS -->
                <div class="metric-cards-grid">
                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Total Day Revenue</div>
                        <div class="metric-card-value highlight-apricot">₱<?= number_format($totalDayRevenue, 2) ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Paid Transactions</div>
                        <div class="metric-card-value"><?= number_format($paidOrdersCount) ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Pending QR Payments</div>
                        <div class="metric-card-value" style="<?= $pendingQrCount > 0 ? 'color:var(--warning);' : '' ?>">
                            <?= number_format($pendingQrCount) ?>
                        </div>
                    </div>
                </div>

                <!-- ORDERS TABLE CARD -->
                <div class="data-table-card">
                    <div class="table-header-toolbar">
                        <span class="table-title">Transactions List</span>
                        <span style="font-size:12px; color:var(--text-muted);">Sorted newest first</span>
                    </div>

                    <table class="clean-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Timestamp</th>
                                <th>Items Ordered</th>
                                <th>Method</th>
                                <th>Details / Tender</th>
                                <th style="text-align:right;">Total Amount</th>
                                <th style="text-align:center;">Payment Status</th>
                                <th style="text-align:right;">Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ordersList) > 0): ?>
                                <?php foreach ($ordersList as $order): ?>
                                    <?php
                                    $status = $order['payment_status'];
                                    if ($status === 'paid') {
                                        $statusClass = 'status-paid';
                                    } elseif ($status === 'pending') {
                                        $statusClass = 'status-pending';
                                    } else {
                                        $statusClass = 'status-failed';
                                    }
                                    ?>
                                    <tr class="order-row" data-text="<?= strtolower(e($order['order_id'] . ' ' . $order['item_names'] . ' ' . $order['payment_reference'])) ?>">
                                        <td style="font-family:'JetBrains Mono',monospace; font-weight:700; font-size:13px; color:var(--text-dark);">
                                            #<?= str_pad((string)$order['order_id'], 4, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td style="font-size:13px; color:var(--text-muted); white-space:nowrap;">
                                            <?= date('h:i A', strtotime($order['order_date'])) ?>
                                            <div style="font-size:11px; color:var(--text-light);"><?= date('M d, Y', strtotime($order['order_date'])) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size:13.5px; font-weight:600; color:var(--text-dark); max-width:280px; line-height:1.4;">
                                                <?= e($order['item_names'] ?: 'No items listed') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($order['payment_method'] === 'cash'): ?>
                                                <span style="display:inline-flex; align-items:center; gap:5px; background:var(--surface-subtle); border:1px solid var(--border); padding:3px 10px; border-radius:var(--radius-pill); font-size:12px; font-weight:700;">
                                                    Cash
                                                </span>
                                            <?php else: ?>
                                                <span style="display:inline-flex; align-items:center; gap:5px; background:var(--accent-soft); color:var(--accent-apricot); padding:3px 10px; border-radius:var(--radius-pill); font-size:12px; font-weight:700;">
                                                    QR Ph
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:12.5px;">
                                            <?php if ($order['payment_method'] === 'cash'): ?>
                                                <div style="color:var(--text-dark); font-family:'JetBrains Mono',monospace;">Rec: ₱<?= number_format((float)$order['cash_received'], 2) ?></div>
                                                <div style="color:var(--text-muted); font-size:11px; font-family:'JetBrains Mono',monospace;">Chg: ₱<?= number_format((float)$order['change_amount'], 2) ?></div>
                                            <?php else: ?>
                                                <div style="color:var(--text-dark); font-family:'JetBrains Mono',monospace; font-weight:600;">
                                                    <?= e($order['payment_reference'] ?: 'No reference code') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right; font-family:'JetBrains Mono',monospace; font-size:15px; font-weight:800; color:var(--accent-apricot);">
                                            ₱<?= number_format((float)$order['total_amount'], 2) ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="status-pill <?= $statusClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if ($order['payment_method'] === 'qr' && $order['payment_status'] === 'pending' && $userRole === 'admin'): ?>
                                                <form method="POST" action="verify_qr_payment.php" onsubmit="return confirm('Confirm receipt of QR payment for Order #<?= (int)$order['order_id'] ?>?');">
                                                    <input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                    <button type="submit" class="btn-sm-apricot">
                                                        Verify Payment
                                                    </button>
                                                </form>
                                            <?php elseif ($order['payment_status'] === 'paid'): ?>
                                                <span style="font-size:12px; font-weight:700; color:#059669; display:inline-flex; align-items:center; gap:4px;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    Confirmed
                                                </span>
                                            <?php else: ?>
                                                <span style="font-size:12px; color:var(--text-light);">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:40px; color:var(--text-muted);">
                                        No transactions recorded for <?= date('F j, Y', strtotime($selectedDate)) ?>.
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
        function filterOrderTable(query) {
            query = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.order-row');
            rows.forEach(row => {
                const text = row.getAttribute('data-text') || '';
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>