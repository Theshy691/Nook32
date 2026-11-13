<?php
session_start();

require_once __DIR__ . '/auth.php';
requireLogin();

$activePage = 'pos';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Checkout — Nook32 Coffee</title>

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
                <a href="index.php" class="btn-sm-action">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back to POS Menu</span>
                </a>

                <div class="top-bar-right">
                    <div class="store-live-pill">
                        Tender Checkout
                    </div>
                </div>
            </div>

            <!-- VIEW CONTAINER -->
            <div class="admin-view-container">
                
                <div class="admin-header-row">
                    <div class="admin-title-wrap">
                        <h1>Payment & Tender Checkout</h1>
                        <p>Review customer ticket items and process payment tender</p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; max-width:960px;">
                    
                    <!-- CART SUMMARY -->
                    <div class="content-panel-card">
                        <h3 style="font-size:16px; font-weight:800; margin-bottom:16px; color:var(--text-dark);">
                            Order Ticket Summary
                        </h3>

                        <div id="orderItems" style="display:flex; flex-direction:column; gap:10px; min-height:160px;"></div>

                        <div style="border-top:1px dashed var(--border); margin:18px 0; padding-top:14px; display:flex; justify-content:space-between; align-items:baseline;">
                            <span style="font-size:15px; font-weight:700; color:var(--text-dark);">Total Due</span>
                            <span id="orderTotal" style="font-size:24px; font-weight:800; color:var(--accent-apricot); font-family:'JetBrains Mono',monospace;">₱0.00</span>
                        </div>
                    </div>

                    <!-- PAYMENT FORM -->
                    <div class="content-panel-card">
                        <h3 style="font-size:16px; font-weight:800; margin-bottom:16px; color:var(--text-dark);">
                            Select Payment Method
                        </h3>

                        <div class="form-group">
                            <label class="form-label" for="paymentMethod">Payment Method</label>
                            <select id="paymentMethod" class="form-select">
                                <option value="cash">Cash Tender</option>
                                <option value="qr">QR Ph / Digital Wallet</option>
                            </select>
                        </div>

                        <!-- CASH FIELDS -->
                        <div id="cashFields">
                            <div class="form-group">
                                <label class="form-label">Tender Presets</label>
                                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:6px; margin-bottom:10px;">
                                    <button type="button" class="btn-sm-action" id="presetExact" onclick="setCashPreset('exact')">Exact</button>
                                    <button type="button" class="btn-sm-action" onclick="setCashPreset(100)">₱100</button>
                                    <button type="button" class="btn-sm-action" onclick="setCashPreset(200)">₱200</button>
                                    <button type="button" class="btn-sm-action" onclick="setCashPreset(500)">₱500</button>
                                </div>

                                <label class="form-label">Cash Received (₱)</label>
                                <input
                                    type="number"
                                    id="cashReceived"
                                    class="form-control"
                                    step="any"
                                    min="0"
                                    placeholder="0.00"
                                    style="font-family:'JetBrains Mono',monospace; font-size:16px; font-weight:700;"
                                >

                                <div id="changePreview" style="background:var(--accent-soft); border-radius:12px; padding:12px 16px; margin-top:12px; display:flex; justify-content:space-between; align-items:center; font-weight:700;">
                                    <span style="font-size:13px; color:var(--text-dark);">Change Due:</span>
                                    <span id="changeAmount" style="font-size:18px; color:var(--accent-apricot); font-family:'JetBrains Mono',monospace;">₱0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- QR FIELDS -->
                        <div id="qrFields" style="display:none;">
                            <div class="form-group">
                                <label class="form-label" for="paymentReference">QR Ph Reference Number</label>
                                <input
                                    type="text"
                                    id="paymentReference"
                                    class="form-control"
                                    maxlength="150"
                                    placeholder="Enter reference code or trace no."
                                    style="font-family:'JetBrains Mono',monospace;"
                                >
                                <small style="font-size:11.5px; color:var(--text-light); margin-top:4px;">
                                    Confirm payment on customer phone before completing.
                                </small>
                            </div>
                        </div>

                        <button type="button" class="btn-place-order-apricot" id="confirmButton" onclick="checkoutOrder()" style="margin-top:20px;">
                            Confirm Transaction
                        </button>

                        <div id="message" style="display:none; margin-top:14px; padding:12px 16px; border-radius:12px; font-size:13px; font-weight:700; text-align:center;"></div>
                    </div>

                </div>

            </div>

        </main>

    </div>

    <script>
        let cart = [];
        try {
            cart = JSON.parse(sessionStorage.getItem('nook32_cart')) || [];
        } catch (error) {
            cart = [];
        }

        const paymentMethod = document.getElementById('paymentMethod');
        const cashReceived = document.getElementById('cashReceived');
        const paymentReference = document.getElementById('paymentReference');
        const cashFields = document.getElementById('cashFields');
        const qrFields = document.getElementById('qrFields');
        const changePreview = document.getElementById('changePreview');
        const changeAmount = document.getElementById('changeAmount');
        const confirmButton = document.getElementById('confirmButton');
        const message = document.getElementById('message');

        function getTotal() {
            return Math.round(cart.reduce((sum, item) => sum + (Number(item.price) * Number(item.quantity)), 0) * 100) / 100;
        }

        function renderOrder() {
            const container = document.getElementById('orderItems');
            container.replaceChildren();

            if (cart.length === 0) {
                container.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:24px;">Ticket is empty.</p>';
                confirmButton.disabled = true;
                return;
            }

            confirmButton.disabled = false;
            const total = getTotal();

            cart.forEach(item => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.justifyContent = 'space-between';
                row.style.alignItems = 'center';
                row.style.padding = '8px 0';
                row.style.borderBottom = '1px solid var(--border)';
                row.innerHTML = `
                    <div>
                        <div style="font-weight:700; font-size:13.5px; color:var(--text-dark);">${escapeHtml(item.name)}</div>
                        <div style="font-size:11.5px; color:var(--text-muted); font-family:'JetBrains Mono',monospace;">${item.quantity} × ₱${Number(item.price).toFixed(2)}</div>
                    </div>
                    <div style="font-weight:800; font-size:14px; color:var(--text-dark); font-family:'JetBrains Mono',monospace;">₱${(Number(item.price) * Number(item.quantity)).toFixed(2)}</div>
                `;
                container.appendChild(row);
            });

            document.getElementById('orderTotal').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('presetExact').textContent = `Exact (₱${total.toFixed(2)})`;
            updatePaymentFields();
        }

        function updatePaymentFields() {
            const isCash = paymentMethod.value === 'cash';
            cashFields.style.display = isCash ? 'block' : 'none';
            qrFields.style.display = isCash ? 'none' : 'block';
            updateChange();
        }

        function setCashPreset(val) {
            const total = getTotal();
            cashReceived.value = (val === 'exact') ? total.toFixed(2) : Number(val).toFixed(2);
            updateChange();
        }

        function updateChange() {
            const total = getTotal();
            const received = parseFloat(cashReceived.value) || 0;

            if (received >= total) {
                changePreview.style.background = 'var(--accent-soft)';
                changeAmount.style.color = 'var(--accent-apricot)';
                changeAmount.textContent = `₱${(received - total).toFixed(2)}`;
            } else {
                changePreview.style.background = '#fef2f2';
                changeAmount.style.color = '#dc2626';
                changeAmount.textContent = `−₱${(total - received).toFixed(2)} (Short)`;
            }
        }

        async function checkoutOrder() {
            const total = getTotal();
            const method = paymentMethod.value;
            message.style.display = 'none';

            let received = null;
            let refNo = null;

            if (method === 'cash') {
                received = parseFloat(cashReceived.value);
                if (isNaN(received) || received < total) {
                    message.style.display = 'block';
                    message.style.background = '#fef2f2';
                    message.style.color = '#dc2626';
                    message.textContent = 'Cash tendered is less than total amount due.';
                    return;
                }
            } else {
                refNo = paymentReference.value.trim();
                if (!refNo) {
                    message.style.display = 'block';
                    message.style.background = '#fef2f2';
                    message.style.color = '#dc2626';
                    message.textContent = 'Reference number is required for digital payments.';
                    return;
                }
            }

            confirmButton.disabled = true;
            confirmButton.textContent = 'Recording Transaction...';

            try {
                const res = await fetch('checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: cart.map(i => ({ id: i.id, quantity: i.quantity })),
                        payment_method: method,
                        cash_received: received,
                        payment_reference: refNo
                    })
                });

                let data;
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    throw new Error('Server communication error. Please try again.');
                }

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Transaction could not be recorded.');
                }

                sessionStorage.removeItem('nook32_cart');
                cart = [];
                renderOrder();

                message.style.display = 'block';
                message.style.background = '#ecfdf5';
                message.style.color = '#065f46';
                message.textContent = `Order #${data.order_id} recorded successfully! Total: ₱${data.total}. Change: ₱${data.change || '0.00'}`;
            } catch (err) {
                message.style.display = 'block';
                message.style.background = '#fef2f2';
                message.style.color = '#dc2626';
                message.textContent = err.message;
                confirmButton.disabled = false;
                confirmButton.textContent = 'Confirm Transaction';
            }
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]);
        }

        paymentMethod.addEventListener('change', updatePaymentFields);
        cashReceived.addEventListener('input', updateChange);
        renderOrder();
    </script>
</body>
</html>