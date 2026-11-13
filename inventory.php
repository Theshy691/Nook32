<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/InventoryManager.php';

$database = new Database();
$inventory = new InventoryManager($database);

$message = '';
$messageType = 'success';

// Add or update inventory items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $message = 'Security token invalid or expired. Please refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add') {
                $itemName = trim($_POST['item_name'] ?? '');
                $quantity = filter_var(
                    $_POST['quantity'] ?? null,
                    FILTER_VALIDATE_FLOAT
                );
                $unit = trim($_POST['unit'] ?? '');
                $lowStockLimit = filter_var(
                    $_POST['low_stock_limit'] ?? null,
                    FILTER_VALIDATE_FLOAT
                );

                if (
                    $itemName === '' ||
                    $unit === '' ||
                    $quantity === false ||
                    $quantity === null ||
                    $lowStockLimit === false ||
                    $lowStockLimit === null ||
                    $quantity < 0 ||
                    $lowStockLimit < 0 ||
                    strlen($itemName) > 100 ||
                    strlen($unit) > 30
                ) {
                    throw new InvalidArgumentException(
                        'Please enter valid item details and non-negative quantities.'
                    );
                }

                $stmt = $database->getConnection()->prepare(
                    "INSERT INTO inventory
                        (item_name, quantity, unit, low_stock_limit)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    'sdsd',
                    $itemName,
                    $quantity,
                    $unit,
                    $lowStockLimit
                );

                $stmt->execute();

                header('Location: inventory.php?message=added');
                exit;
            }

            if ($action === 'update') {
                $inventoryId = filter_var(
                    $_POST['inventory_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                $quantity = filter_var(
                    $_POST['quantity'] ?? null,
                    FILTER_VALIDATE_FLOAT
                );

                if (
                    !$inventoryId ||
                    $quantity === false ||
                    $quantity === null ||
                    $quantity < 0
                ) {
                    throw new InvalidArgumentException(
                        'Please enter a valid item and a non-negative quantity.'
                    );
                }

                $stmt = $database->getConnection()->prepare(
                    "UPDATE inventory
                     SET quantity = ?
                     WHERE inventory_id = ?"
                );

                $stmt->bind_param(
                    'di',
                    $quantity,
                    $inventoryId
                );

                $stmt->execute();

                header('Location: inventory.php?message=updated');
                exit;
            }
        } catch (Throwable $e) {
            if ($e instanceof InvalidArgumentException) {
                $message = $e->getMessage();
            } else {
                error_log('Nook32 inventory error: ' . $e->getMessage());
                $message = 'Something went wrong while saving inventory. Please try again.';
            }

            $messageType = 'error';
        }
    }
}

// Display success messages after saving
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'added') {
        $message = 'New inventory item added successfully.';
    } elseif ($_GET['message'] === 'updated') {
        $message = 'Stock quantity updated successfully.';
    }

    $messageType = 'success';
}

// Retrieve inventory records
$items = $inventory->getAllItems();
$totalItems = $inventory->getTotalItems();
$lowStockCount = $inventory->getLowStockCount();
$inStockCount = max(0, $totalItems - $lowStockCount);

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$activePage = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management — Nook32 Coffee</title>
    
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
                        id="inventorySearch"
                        class="search-pill-input"
                        placeholder="Search ingredients, syrups, packaging..."
                        autocomplete="off"
                        oninput="filterInventoryTable(this.value)"
                    >
                </div>

                <div class="top-bar-right">
                    <button type="button" class="btn-pill-filter" onclick="openAddModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Stock Item</span>
                    </button>

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
                        <h1>Inventory & Ingredient Control</h1>
                        <p>Real-time ingredient levels, automatic deductions per recipe, and reorder warnings</p>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <a href="index.php" class="btn-secondary-pill">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            POS Register
                        </a>
                        <a href="dashboard.php" class="btn-secondary-pill">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                            Dashboard
                        </a>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert-banner <?= e($messageType) ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($messageType === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            <?php endif; ?>
                        </svg>
                        <span><?= e($message) ?></span>
                    </div>
                <?php endif; ?>

                <!-- METRICS ROW -->
                <div class="metric-cards-grid">
                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Total Ingredients / Items</div>
                        <div class="metric-card-value"><?= number_format($totalItems) ?></div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Low Stock Warnings</div>
                        <div class="metric-card-value <?= $lowStockCount > 0 ? 'highlight-apricot' : '' ?>" style="<?= $lowStockCount > 0 ? 'color:var(--danger);' : '' ?>">
                            <?= number_format($lowStockCount) ?>
                        </div>
                    </div>

                    <div class="metric-card-apricot">
                        <div class="metric-card-label">Adequate Stock Items</div>
                        <div class="metric-card-value" style="color:var(--success);">
                            <?= number_format($inStockCount) ?>
                        </div>
                    </div>
                </div>

                <!-- INVENTORY DATA TABLE -->
                <div class="data-table-card">
                    <div class="table-header-toolbar">
                        <span class="table-title">Ingredient Inventory List</span>
                        <span style="font-size:12px; color:var(--text-muted);">Adjust stock levels directly below</span>
                    </div>

                    <table class="clean-table" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Item ID</th>
                                <th>Item Name</th>
                                <th>In-Stock Quantity</th>
                                <th>Unit</th>
                                <th>Low Stock Threshold</th>
                                <th>Stock Status</th>
                                <th style="text-align:right;">Quick Adjust</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $quantity = (float) $item['quantity'];
                                    $limit = (float) $item['low_stock_limit'];

                                    if ($quantity <= 0) {
                                        $statusClass = 'status-out-stock';
                                        $statusText = 'Out of Stock';
                                    } elseif ($quantity <= $limit) {
                                        $statusClass = 'status-low-stock';
                                        $statusText = 'Low Stock';
                                    } else {
                                        $statusClass = 'status-in-stock';
                                        $statusText = 'In Stock';
                                    }
                                    ?>
                                    <tr class="inv-row" data-name="<?= strtolower(e($item['item_name'])) ?>">
                                        <td style="font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--text-muted);">
                                            #INV-<?= str_pad((string)$item['inventory_id'], 3, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-dark); font-size:14px;">
                                                <?= e($item['item_name']) ?>
                                            </div>
                                        </td>
                                        <td style="font-family:'JetBrains Mono',monospace; font-weight:700; font-size:14px; color:var(--text-dark);">
                                            <?= number_format($quantity, 2) ?>
                                        </td>
                                        <td>
                                            <span style="background:var(--surface-subtle); border:1px solid var(--border); padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:600; color:var(--text-muted);">
                                                <?= e($item['unit']) ?>
                                            </span>
                                        </td>
                                        <td style="font-family:'JetBrains Mono',monospace; font-size:13px; color:var(--text-muted);">
                                            <?= number_format($limit, 2) ?> <?= e($item['unit']) ?>
                                        </td>
                                        <td>
                                            <span class="status-pill <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <form method="POST" action="inventory.php" style="display:inline-flex; align-items:center; gap:8px; justify-content:flex-end;">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="inventory_id" value="<?= (int) $item['inventory_id'] ?>">
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    min="0"
                                                    step="0.01"
                                                    value="<?= e($item['quantity']) ?>"
                                                    class="form-control"
                                                    style="width:96px; height:36px; padding:0 10px; font-family:'JetBrains Mono',monospace; font-size:13px;"
                                                    required
                                                >
                                                <button type="submit" class="btn-sm-apricot">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                                        No inventory items found. Click "+ Add Stock Item" to create your first ingredient.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>

    </div>

    <!-- ADD INVENTORY ITEM MODAL -->
    <div class="modal-overlay" id="addInventoryModal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Add Inventory Item</h3>
                    <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Register a new ingredient or supply item</p>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeAddModal()">✕</button>
            </div>

            <form method="POST" action="inventory.php">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="add">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="item_name">Item Name</label>
                        <input
                            type="text"
                            id="item_name"
                            name="item_name"
                            class="form-control"
                            maxlength="100"
                            placeholder="e.g. Espresso Beans (Dark Roast)"
                            required
                        >
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                        <div class="form-group">
                            <label class="form-label" for="quantity">Initial Quantity</label>
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                class="form-control"
                                min="0"
                                step="0.01"
                                placeholder="e.g. 20"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="unit">Unit of Measure</label>
                            <select id="unit" name="unit" class="form-select" required>
                                <option value="">Select unit</option>
                                <option value="kg">Kilograms (kg)</option>
                                <option value="g">Grams (g)</option>
                                <option value="L">Liters (L)</option>
                                <option value="mL">Milliliters (mL)</option>
                                <option value="bottle">Bottles</option>
                                <option value="pack">Packs</option>
                                <option value="pcs">Pieces</option>
                                <option value="box">Boxes</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="low_stock_limit">Low Stock Alert Limit</label>
                        <input
                            type="number"
                            id="low_stock_limit"
                            name="low_stock_limit"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="5"
                            placeholder="e.g. 5"
                            required
                        >
                        <small style="font-size:11.5px; color:var(--text-light); margin-top:4px;">
                            The POS will trigger a warning when stock falls below this amount.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary-pill" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-apricot">Save Stock Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            const modal = document.getElementById('addInventoryModal');
            modal.style.display = 'flex';
            setTimeout(() => {
                document.getElementById('item_name').focus();
            }, 50);
        }

        function closeAddModal() {
            document.getElementById('addInventoryModal').style.display = 'none';
        }

        // Close on escape key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAddModal();
            }
        });

        // Close on background click
        document.getElementById('addInventoryModal').addEventListener('click', (e) => {
            if (e.target.id === 'addInventoryModal') {
                closeAddModal();
            }
        });

        // Instant table filter
        function filterInventoryTable(query) {
            query = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.inv-row');
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                if (name.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>