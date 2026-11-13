<?php
session_start();

require_once __DIR__ . '/auth.php';
requireLogin();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';

$sql = "SELECT
            p.product_id,
            p.product_name,
            p.price,
            c.category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.category_id
        WHERE p.availability = 'available'
        ORDER BY c.category_id, p.product_name";

$result = $conn->query($sql);

$categories = [];
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
    if (!empty($row['category_name'])) {
        $categories[$row['category_name']] = true;
    }
}

$userRole = $_SESSION['role'] ?? 'cashier';
$userName = $_SESSION['username'] ?? 'User';
$fullName = $_SESSION['full_name'] ?? 'Staff Member';
$activePage = 'pos';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Register — Nook32 Coffee</title>
    
    <link rel="stylesheet" href="assets/css/purr_theme.css">
</head>
<body>

    <div class="app-viewport">
        
        <!-- 1. LEFT SIDEBAR (SHARED) -->
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <!-- 2. MIDDLE CATALOG & MENU -->
        <main class="main-workspace">
            
            <!-- TOP BAR (SEARCH & FILTER MATCHING REFERENCE) -->
            <div class="top-action-bar">
                <div class="search-pill-box">
                    <svg class="search-icon-pos" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input
                        type="search"
                        id="catalogSearch"
                        class="search-pill-input"
                        placeholder="Search menu..."
                        autocomplete="off"
                    >
                    <button type="button" class="btn-pill-filter-inside" onclick="document.getElementById('catalogSearch').focus()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                        </svg>
                        <span>Filter</span>
                    </button>
                </div>

                <div class="top-bar-right">
                    <div class="user-profile-header-pill">
                        <div class="user-avatar" style="width:34px; height:34px; font-size:13px; background:<?= $userRole === 'admin' ? 'var(--dark-pill); color:#ffffff;' : 'var(--accent-soft); color:var(--accent-apricot);' ?>">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                        <div style="line-height:1.2;">
                            <div style="font-size:13px; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($fullName ?: $userName) ?></div>
                            <div style="font-size:11px; color:var(--text-muted); text-transform:capitalize;"><?= htmlspecialchars($userRole) ?> terminal</div>
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

            <!-- CATEGORY PILLS STRIP -->
            <div class="category-tab-strip">
                <button type="button" class="cat-chip active" data-category="">Coffee</button>
                <?php foreach (array_keys($categories) as $cat): ?>
                    <button type="button" class="cat-chip" data-category="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- CATALOG CONTENT & PRODUCT CARDS -->
            <div class="content-body">
                <h2 class="section-headline" id="sectionHeadline">Coffee menu</h2>

                <div class="catalog-cards-grid" id="productsGrid">
                    <?php foreach ($products as $p): 
                        $cName = $p['category_name'] ?? 'General';
                        $isPastry = (stripos($cName, 'pastry') !== false || stripos($cName, 'snack') !== false);
                        $isIced = (stripos($p['product_name'], 'iced') !== false || stripos($cName, 'frappe') !== false);
                    ?>
                        <article
                            class="product-item-card"
                            data-id="<?= (int)$p['product_id'] ?>"
                            data-name="<?= htmlspecialchars($p['product_name']) ?>"
                            data-price="<?= (float)$p['price'] ?>"
                            data-category="<?= htmlspecialchars($cName) ?>"
                        >
                            <div class="card-media-row">
                                <div class="card-drink-thumbnail">
                                    <div class="cup-graphic <?= $isPastry ? 'pastry' : ($isIced ? 'iced' : '') ?>"></div>
                                </div>

                                <div class="card-info-col">
                                    <div class="card-title-price">
                                        <div class="card-product-name" title="<?= htmlspecialchars($p['product_name']) ?>">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                        </div>
                                        <div class="card-product-price" id="card-price-<?= (int)$p['product_id'] ?>">
                                            ₱<?= number_format((float)$p['price'], 2) ?>
                                        </div>
                                    </div>

                                    <p class="card-description-text">
                                        Handcrafted premium beverage made with fresh ingredients and artisanal roast.
                                    </p>

                                    <div class="card-size-selector">
                                        <span>Size</span>
                                        <div class="size-pill-group">
                                            <button type="button" class="size-mini-pill active" onclick="selectSize(this, <?= (int)$p['product_id'] ?>, 'Small', <?= (float)$p['price'] ?>)">Small</button>
                                            <button type="button" class="size-mini-pill" onclick="selectSize(this, <?= (int)$p['product_id'] ?>, 'Large', <?= (float)$p['price'] + 20 ?>)">Large</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-bottom-actions">
                                <div class="stepper-pill-clean">
                                    <button type="button" class="btn-step-clean" onclick="stepCardCount(<?= (int)$p['product_id'] ?>, -1)">−</button>
                                    <span id="card-qty-<?= (int)$p['product_id'] ?>">1</span>
                                    <button type="button" class="btn-step-clean" onclick="stepCardCount(<?= (int)$p['product_id'] ?>, 1)">+</button>
                                </div>

                                <button
                                    type="button"
                                    class="btn-card-add-pill"
                                    id="card-btn-<?= (int)$p['product_id'] ?>"
                                    onclick="addCardToOrder(<?= (int)$p['product_id'] ?>, <?= htmlspecialchars(json_encode($p['product_name']), ENT_QUOTES, 'UTF-8') ?>, <?= (float)$p['price'] ?>)"
                                >
                                    Add to Cart
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>

        <!-- 3. RIGHT CART SIDEBAR -->
        <aside class="cart-sidebar">
            <div>
                <div class="cart-sidebar-header">
                    <h3 class="cart-headline">Cart</h3>
                    <span class="cart-order-num" id="orderTicketNum">Order #<?= rand(1000, 9999) ?></span>
                </div>

                <!-- ORDER TYPE SEGMENTED CHIPS -->
                <div class="order-type-switch">
                    <button type="button" class="order-type-chip" onclick="setOrderType(this, 'Delivery')">Delivery</button>
                    <button type="button" class="order-type-chip active" onclick="setOrderType(this, 'Dine in')">Dine in</button>
                    <button type="button" class="order-type-chip" onclick="setOrderType(this, 'Take away')">Take away</button>
                </div>

                <!-- CART ITEMS LIST -->
                <div class="cart-scroll-area" id="cartItemList">
                    <div style="text-align:center; padding: 40px 10px; color: var(--text-muted); font-size: 13px;">
                        Cart is empty.<br>Select items from the menu.
                    </div>
                </div>
            </div>

            <!-- CART FOOTER & CHECKOUT ACTION -->
            <div>
                <div class="cart-breakdown">
                    <div class="breakdown-row">
                        <span>Items</span>
                        <span id="breakdownSubtotal">₱0.00</span>
                    </div>
                    <div class="breakdown-row">
                        <span>Discounts</span>
                        <span id="breakdownDiscount">-₱0.00</span>
                    </div>
                    <div class="breakdown-row total-row">
                        <span>Total</span>
                        <span class="bold-val" id="breakdownTotal">₱0.00</span>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-place-order-apricot"
                    id="btnPlaceOrder"
                    onclick="openCheckoutDrawer()"
                    disabled
                >
                    Place an order
                </button>
            </div>
        </aside>

    </div>

    <!-- IN-PAGE PAYMENT TENDER DRAWER -->
    <div class="drawer-overlay" id="checkoutOverlay" style="display:none; position:fixed; inset:0; background:rgba(33,32,38,0.55); backdrop-filter:blur(3px); z-index:200; justify-content:flex-end;">
        <div style="width:420px; max-width:95vw; background:#ffffff; height:100%; box-shadow:-10px 0 30px rgba(0,0,0,0.15); display:flex; flex-direction:column; justify-content:space-between; padding:32px 28px;">
            
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                    <h3 style="font-size:20px; font-weight:800; color:var(--text-dark);">Complete Payment</h3>
                    <button type="button" onclick="closeCheckoutDrawer()" style="background:var(--surface-subtle); border:none; width:34px; height:34px; border-radius:50%; font-size:16px; cursor:pointer; color:var(--text-dark);">✕</button>
                </div>

                <div style="background:var(--surface-subtle); border-radius:18px; padding:18px; margin-bottom:20px; text-align:center;">
                    <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:4px;">Total Amount Due</div>
                    <div style="font-size:30px; font-weight:800; color:var(--accent-apricot);" id="drawerTotalText">₱0.00</div>
                </div>

                <!-- TENDER TOGGLE -->
                <div style="display:grid; grid-template-columns:1fr 1fr; background:var(--surface-subtle); padding:4px; border-radius:var(--radius-pill); margin-bottom:18px;">
                    <button type="button" class="order-type-chip active" id="tabPayCash" onclick="setDrawerPay('cash')">Cash Tender</button>
                    <button type="button" class="order-type-chip" id="tabPayQR" onclick="setDrawerPay('qr')">QR Ph / Digital</button>
                </div>

                <!-- CASH INPUT & PRESETS -->
                <div id="drawerCashBox">
                    <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:6px; margin-bottom:12px;">
                        <button type="button" class="cat-chip" style="padding:6px; font-size:11px;" onclick="setDrawerPreset('exact')">Exact</button>
                        <button type="button" class="cat-chip" style="padding:6px; font-size:11px;" onclick="setDrawerPreset(100)">₱100</button>
                        <button type="button" class="cat-chip" style="padding:6px; font-size:11px;" onclick="setDrawerPreset(200)">₱200</button>
                        <button type="button" class="cat-chip" style="padding:6px; font-size:11px;" onclick="setDrawerPreset(500)">₱500</button>
                        <button type="button" class="cat-chip" style="padding:6px; font-size:11px;" onclick="setDrawerPreset(1000)">₱1k</button>
                    </div>

                    <input
                        type="number"
                        id="drawerCashInput"
                        placeholder="Enter amount received (₱)"
                        class="search-pill-input"
                        style="height:48px; padding:0 20px; font-size:16px; font-weight:700; margin-bottom:12px;"
                        oninput="calcDrawerChange()"
                    >

                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--accent-soft); padding:12px 18px; border-radius:14px; font-weight:700;">
                        <span style="font-size:13px; color:var(--text-dark);">Change Due:</span>
                        <span style="font-size:18px; color:var(--accent-apricot);" id="drawerChangeText">₱0.00</span>
                    </div>
                </div>

                <!-- QR INPUT -->
                <div id="drawerQRBox" style="display:none;">
                    <input
                        type="text"
                        id="drawerQRInput"
                        placeholder="Enter QR reference code"
                        class="search-pill-input"
                        style="height:48px; padding:0 20px; font-size:14.5px;"
                    >
                    <p style="font-size:12px; color:var(--text-muted); margin-top:8px; text-align:center;">
                        Present customer with store QR standee and confirm payment code.
                    </p>
                </div>

                <div id="drawerErrBanner" style="display:none; color:#dc2626; font-size:12.5px; font-weight:600; margin-top:14px; text-align:center; background:#fef2f2; border:1px solid #fee2e2; padding:10px 14px; border-radius:12px;"></div>
            </div>

            <button
                type="button"
                class="btn-place-order-apricot"
                id="btnFinalizeOrder"
                onclick="finalizeCheckout()"
            >
                Confirm & Print Receipt
            </button>
        </div>
    </div>

    <!-- RECEIPT MODAL -->
    <div id="receiptModal" style="display:none; position:fixed; inset:0; background:rgba(33,32,38,0.65); z-index:300; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#ffffff; border-radius:24px; padding:24px; max-width:380px; width:100%; box-shadow:0 20px 50px rgba(0,0,0,0.25);">
            <div id="thermalPrintArea" style="font-family:'JetBrains Mono', monospace; font-size:12px; line-height:1.6; border:1px dashed var(--border-darker); padding:18px; border-radius:12px; margin-bottom:18px;">
                <div style="text-align:center; font-weight:800; font-size:14px;">NOOK32 COFFEE</div>
                <div style="text-align:center; color:var(--text-muted); font-size:11px;">Binmaley, Pangasinan · POS Terminal</div>
                <div style="border-top:1px dashed #d1ced6; margin:8px 0;"></div>
                <div>Order: <strong id="recOrderNum">#0000</strong></div>
                <div>Date: <span id="recDate"></span></div>
                <div>Cashier: <span><?= htmlspecialchars($userName) ?></span></div>
                <div style="border-top:1px dashed #d1ced6; margin:8px 0;"></div>
                <div id="recItemsList"></div>
                <div style="border-top:1px dashed #d1ced6; margin:8px 0;"></div>
                <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><span id="recSub">₱0.00</span></div>
                <div style="display:flex; justify-content:space-between; font-weight:800; font-size:14px;"><span>TOTAL:</span><span id="recTot">₱0.00</span></div>
                <div style="display:flex; justify-content:space-between;" id="recTenderRow"><span>Tendered:</span><span id="recTend">₱0.00</span></div>
                <div style="display:flex; justify-content:space-between; font-weight:700;" id="recChangeRow"><span>Change:</span><span id="recChg">₱0.00</span></div>
                <div style="border-top:2px dashed #212026; margin:10px 0;"></div>
                <div style="text-align:center; font-size:11px;">Thank you for your visit!<br>Enjoy your handcrafted coffee!</div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <button type="button" class="btn-step-clean" style="width:100%; height:44px; border-radius:22px; font-weight:700;" onclick="window.print()">Print Receipt</button>
                <button type="button" class="btn-place-order-apricot" style="height:44px; font-size:13.5px;" onclick="closeReceiptAndReset()">New Order</button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        try {
            cart = JSON.parse(sessionStorage.getItem('nook32_cart')) || [];
        } catch (e) {
            cart = [];
        }

        let orderType = 'Dine in';
        let currentPayType = 'cash';
        let activeCategoryFilter = '';

        // Card steppers and sizes state
        const cardSelections = {};
        const cardSizeChoices = {};

        function stepCardCount(id, delta) {
            const current = cardSelections[id] || 1;
            const updated = Math.max(1, current + delta);
            cardSelections[id] = updated;
            document.getElementById(`card-qty-${id}`).textContent = updated;
        }

        function selectSize(btn, id, sizeName, price) {
            const group = btn.closest('.size-pill-group');
            group.querySelectorAll('.size-mini-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            cardSizeChoices[id] = { size: sizeName, price: price };
            const priceEl = document.getElementById(`card-price-${id}`);
            if (priceEl) {
                priceEl.textContent = `₱${price.toFixed(2)}`;
            }
        }

        function addCardToOrder(id, name, basePrice) {
            const qty = cardSelections[id] || 1;
            const sizeInfo = cardSizeChoices[id] || { size: 'Small', price: basePrice };
            const itemKey = `${id}-${sizeInfo.size}`;

            const existing = cart.find(x => x.itemKey === itemKey);
            if (existing) {
                existing.quantity += qty;
            } else {
                cart.push({
                    id,
                    itemKey,
                    name: `${name} (${sizeInfo.size})`,
                    size: sizeInfo.size,
                    price: sizeInfo.price,
                    quantity: qty
                });
            }

            // Visual feedback on card button
            const btn = document.getElementById(`card-btn-${id}`);
            if (btn) {
                btn.classList.add('added');
                btn.textContent = 'Added to cart';
            }

            renderCart();
            saveCart();
        }

        function saveCart() {
            sessionStorage.setItem('nook32_cart', JSON.stringify(cart));
            syncCardButtons();
        }

        function syncCardButtons() {
            document.querySelectorAll('.btn-card-add-pill').forEach(btn => {
                btn.classList.remove('added');
                btn.textContent = 'Add to Cart';
            });
            cart.forEach(item => {
                const btn = document.getElementById(`card-btn-${item.id}`);
                if (btn) {
                    btn.classList.add('added');
                    btn.textContent = 'In Cart (' + item.quantity + ')';
                }
            });
        }

        function getCartTotal() {
            return Math.round(cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * 100) / 100;
        }

        function renderCart() {
            const list = document.getElementById('cartItemList');
            const total = getCartTotal();

            document.getElementById('breakdownSubtotal').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('breakdownTotal').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('btnPlaceOrder').disabled = (cart.length === 0);

            if (cart.length === 0) {
                list.innerHTML = `
                    <div style="text-align:center; padding: 40px 10px; color: var(--text-muted); font-size: 13px;">
                        Cart is empty.<br>Select items from the menu.
                    </div>
                `;
                syncCardButtons();
                return;
            }

            list.replaceChildren();

            cart.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'cart-line-item';
                row.innerHTML = `
                    <div class="cart-line-thumb">
                        <div class="cup-graphic" style="width:24px; height:36px; border-radius:3px 3px 8px 8px;"></div>
                    </div>
                    <div class="cart-line-details">
                        <div class="cart-line-name">${escapeHtml(item.name)}</div>
                        <div class="cart-line-meta">${item.size || 'Small'} · ₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-line-actions">
                        <span class="cart-line-price">₱${(item.price * item.quantity).toFixed(2)}</span>
                        <div class="cart-stepper-mini">
                            <button type="button" class="btn-mini-step" onclick="changeCartQty(${index}, -1)">−</button>
                            <span>${item.quantity}</span>
                            <button type="button" class="btn-mini-step" onclick="changeCartQty(${index}, 1)">+</button>
                        </div>
                    </div>
                `;
                list.appendChild(row);
            });

            syncCardButtons();
        }

        function changeCartQty(index, delta) {
            if (!cart[index]) return;
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            renderCart();
            saveCart();
        }

        function setOrderType(btn, type) {
            document.querySelectorAll('.order-type-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            orderType = type;
        }

        // CATEGORY & SEARCH FILTER
        function filterCatalog() {
            const query = document.getElementById('catalogSearch').value.toLowerCase().trim();
            document.querySelectorAll('.product-item-card').forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const cat = card.dataset.category;
                const matchesQ = !query || name.includes(query);
                const matchesC = !activeCategoryFilter || cat === activeCategoryFilter;
                card.style.display = (matchesQ && matchesC) ? 'flex' : 'none';
            });
        }

        document.querySelectorAll('.cat-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                activeCategoryFilter = chip.dataset.category;
                document.getElementById('sectionHeadline').textContent = activeCategoryFilter ? activeCategoryFilter : 'Coffee menu';
                filterCatalog();
            });
        });

        document.getElementById('catalogSearch').addEventListener('input', filterCatalog);

        // CHECKOUT DRAWER
        function openCheckoutDrawer() {
            if (cart.length === 0) return;
            const total = getCartTotal();
            document.getElementById('drawerTotalText').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('drawerCashInput').value = '';
            document.getElementById('drawerQRInput').value = '';
            document.getElementById('drawerErrBanner').style.display = 'none';
            setDrawerPay('cash');
            calcDrawerChange();
            const overlay = document.getElementById('checkoutOverlay');
            overlay.style.display = 'flex';
            setTimeout(() => {
                const input = document.getElementById('drawerCashInput');
                if (input) input.focus();
            }, 60);
        }

        function closeCheckoutDrawer() {
            document.getElementById('checkoutOverlay').style.display = 'none';
        }

        function setDrawerPay(type) {
            currentPayType = type;
            const tabCash = document.getElementById('tabPayCash');
            const tabQR = document.getElementById('tabPayQR');
            const boxCash = document.getElementById('drawerCashBox');
            const boxQR = document.getElementById('drawerQRBox');

            if (type === 'cash') {
                tabCash.classList.add('active');
                tabQR.classList.remove('active');
                boxCash.style.display = 'block';
                boxQR.style.display = 'none';
            } else {
                tabQR.classList.add('active');
                tabCash.classList.remove('active');
                boxCash.style.display = 'none';
                boxQR.style.display = 'block';
            }
        }

        function setDrawerPreset(val) {
            const total = getCartTotal();
            document.getElementById('drawerCashInput').value = (val === 'exact') ? total.toFixed(2) : Number(val).toFixed(2);
            calcDrawerChange();
        }

        function calcDrawerChange() {
            const total = getCartTotal();
            const tendered = parseFloat(document.getElementById('drawerCashInput').value) || 0;
            const diff = tendered - total;
            const text = document.getElementById('drawerChangeText');
            if (tendered >= total && total > 0) {
                text.textContent = `₱${diff.toFixed(2)}`;
                text.style.color = 'var(--accent-apricot)';
            } else if (tendered < total && total > 0) {
                text.textContent = `−₱${(total - tendered).toFixed(2)}`;
                text.style.color = '#ef4444';
            } else {
                text.textContent = '₱0.00';
                text.style.color = 'var(--text-muted)';
            }
        }

        async function finalizeCheckout() {
            const total = getCartTotal();
            const btn = document.getElementById('btnFinalizeOrder');
            const err = document.getElementById('drawerErrBanner');
            err.style.display = 'none';

            let cashVal = null;
            let refVal = null;

            if (currentPayType === 'cash') {
                const parsed = parseFloat(document.getElementById('drawerCashInput').value);
                if (isNaN(parsed) || parsed < total) {
                    err.textContent = 'Entered cash does not cover the total.';
                    err.style.display = 'block';
                    return;
                }
                cashVal = parsed;
            } else {
                refVal = document.getElementById('drawerQRInput').value.trim();
                if (!refVal) {
                    err.textContent = 'Please enter the QR reference number.';
                    err.style.display = 'block';
                    return;
                }
            }

            btn.disabled = true;
            btn.textContent = 'Placing Order...';

            try {
                const res = await fetch('checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: cart.map(x => ({ id: x.id, quantity: x.quantity })),
                        payment_method: currentPayType,
                        cash_received: cashVal,
                        payment_reference: refVal
                    })
                });

                let data;
                try {
                    data = await res.json();
                } catch (jsonParseError) {
                    throw new Error('Server returned an unexpected response format. Please try again.');
                }

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Payment processing failed. Please verify order details.');
                }

                const receiptItems = [...cart];
                closeCheckoutDrawer();
                showReceipt(data, cashVal, receiptItems);

                cart = [];
                renderCart();
                saveCart();

            } catch (e) {
                err.textContent = e.message;
                err.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Confirm & Print Receipt';
            }
        }

        function showReceipt(data, tendered, items) {
            document.getElementById('recOrderNum').textContent = '#' + data.order_id;
            document.getElementById('recDate').textContent = new Date().toLocaleString();
            document.getElementById('recSub').textContent = `₱${data.total}`;
            document.getElementById('recTot').textContent = `₱${data.total}`;

            const list = document.getElementById('recItemsList');
            list.replaceChildren();

            const itemsToRender = items || cart;
            itemsToRender.forEach(item => {
                const line = document.createElement('div');
                line.style.cssText = 'display:flex; justify-content:space-between; margin-bottom:2px;';
                line.innerHTML = `<span>${item.quantity}x ${escapeHtml(item.name)}</span><span>₱${(item.price * item.quantity).toFixed(2)}</span>`;
                list.appendChild(line);
            });

            if (currentPayType === 'cash') {
                document.getElementById('recTenderRow').style.display = 'flex';
                document.getElementById('recChangeRow').style.display = 'flex';
                document.getElementById('recTend').textContent = `₱${Number(tendered).toFixed(2)}`;
                document.getElementById('recChg').textContent = `₱${data.change || '0.00'}`;
            } else {
                document.getElementById('recTenderRow').style.display = 'none';
                document.getElementById('recChangeRow').style.display = 'none';
            }

            document.getElementById('receiptModal').style.display = 'flex';
        }

        function closeReceiptAndReset() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        // Close drawers & modals on Escape key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCheckoutDrawer();
                closeReceiptAndReset();
            }
        });

        // Close overlay on backdrop click
        document.getElementById('checkoutOverlay').addEventListener('click', (e) => {
            if (e.target.id === 'checkoutOverlay') {
                closeCheckoutDrawer();
            }
        });
        document.getElementById('receiptModal').addEventListener('click', (e) => {
            if (e.target.id === 'receiptModal') {
                closeReceiptAndReset();
            }
        });

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]);
        }

        renderCart();
    </script>
</body>
</html>
