<?php
// pos.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<div class="pos-desktop-split">
    <!-- Left Column: Current Bill -->
    <div class="pos-products-panel">
        <div class="card" style="height: 100%; display: flex; flex-direction: column; padding: 1.5rem;">
            <div class="d-flex justify-between items-center mb-4">
                <h3 style="margin: 0; font-size: 1.25rem;">Current Bill</h3>
                <span class="badg" style="background: #e2e8f0; color: #475569; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 0.9rem;">
                    Bill #<?= date('Ymd-Hi') ?>
                </span>
            </div>

            <div class="table-container" style="flex: 1; border: 1px solid #f1f5f9; border-radius: 8px; margin-bottom: 2rem;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th class="table-head-alt" style="padding: 1rem;">Product</th>
                            <th class="table-head-alt" style="padding: 1rem; width: 100px; text-align: center;">Price</th>
                            <th class="table-head-alt" style="padding: 1rem; width: 120px; text-align: center;">Qty</th>
                            <th class="table-head-alt" style="padding: 1rem; width: 100px; text-align: center;">Total</th>
                            <th style="padding: 1rem; width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <!-- Items injected by JS -->
                    </tbody>
                </table>
                <div id="emptyCartMsg" style="text-align: center; padding: 5rem 2rem; color: #94a3b8;">
                    <i class="fas fa-shopping-basket" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.3;"></i>
                    <p style="font-size: 0.95rem;">Scan barcode or search product to add</p>
                </div>
            </div>

            <!-- Total Amount Bar (Screenshot Style) -->
            <div style="background: #f8fafc; border: 1px solid #eef2f6; border-radius: 12px; padding: 1.5rem 2rem;">
                <div class="d-flex justify-between items-center" style="color: #1e3a8a; font-weight: 800; font-size: 1.5rem;">
                    <span>Total Amount:</span>
                    <span>₹ <span id="grandTotal">0.00</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar Panels -->
    <div class="pos-cart-panel">
        <!-- SCAN PRODUCT Panel -->
        <div class="card mb-4" style="padding: 1.5rem; border: none; box-shadow: var(--shadow-sm);">
            <span class="section-label">Scan Product</span>
            <div class="d-flex gap-2">
                <div style="position: relative; flex: 1;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode or search..." autofocus autocomplete="off" 
                        style="padding-left: 2.8rem; height: 3.5rem; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc;">
                </div>
                <button class="btn btn-primary" onclick="startCameraScan()" style="width: 3.5rem; height: 3.5rem; border-radius: 10px; background: #4f46e5; border: none; padding: 0;">
                    <i class="fas fa-camera" style="font-size: 1.25rem;"></i>
                </button>
            </div>
        </div>

        <!-- Checkout Panel -->
        <div class="card mb-4" style="padding: 1.5rem; border: none; box-shadow: var(--shadow-sm);">
            <span class="section-label">Checkout</span>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.25rem;">
                <button class="btn-vertical" onclick="processCheckout('cash')" style="background: #10b981; color: white;">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Pay Cash</span>
                </button>
                <button class="btn-vertical" onclick="processCheckout('khata')" style="background: #f59e0b; color: white;">
                    <i class="fas fa-book"></i>
                    <span>Khata</span>
                </button>
            </div>
            
            <button class="btn" onclick="clearCart()" 
                style="width: 100%; border: none; background: #fff1f2; color: #e11d48; padding: 0.85rem; border-radius: 10px; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-trash-alt" style="font-size: 0.85rem;"></i> Cancel Current Bill
            </button>
        </div>

        <!-- Shortcuts Panel -->
        <div class="card" style="padding: 1.5rem; border: none; background: #f8fafc; box-shadow: none;">
            <span class="section-label" style="font-size: 0.8rem;">Shortcuts</span>
            <div style="font-size: 0.85rem; color: #64748b; line-height: 2;">
                <div><span style="color: #94a3b8; margin-right: 8px;">F2</span> Focus Search</div>
                <div><span style="color: #94a3b8; margin-right: 8px;">Enter</span> Add Product</div>
            </div>
        </div>
    </div>
</div>

<!-- Camera Scanner Modal -->
<div id="cameraModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 1rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <div class="d-flex justify-between items-center mb-2">
            <h3>Scan Barcode</h3>
            <button onclick="stopCameraScan()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="reader" style="width: 100%;"></div>
        <p class="text-muted" style="font-size: 0.85rem; text-align: center; margin-top: 10px;">Point camera at barcode</p>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 450px;">
        <h3 id="checkoutTitle" class="mb-3">Confirm Payment</h3>
        <p id="checkoutAmount" style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color); margin-bottom: 1.5rem;">Total: ₹0.00</p>
        
        <div id="khataSelector" style="display: none; margin-bottom: 1.5rem;">
            <!-- Customer Select -->
            <div id="customerSelectGroup">
                <label class="form-label">Select Customer for Khata</label>
                <select id="checkoutCustomer" class="form-control mb-2">
                    <option value="">-- Select Customer --</option>
                    <!-- AJAX Load customers -->
                </select>
                <div style="text-align: right; font-size: 0.9rem;">
                    <button class="btn-link" onclick="toggleNewCustomer()" style="border: none; background: none; color: var(--primary-color); cursor: pointer; padding: 0;">+ New Customer</button>
                </div>
            </div>

            <!-- New Customer Form (Hidden by default) -->
            <div id="newCustomerForm" style="display: none; background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 10px;">
                <h4 style="margin-top: 0; font-size: 1rem; margin-bottom: 10px;">New Customer</h4>
                <div class="form-group mb-2">
                    <input type="text" id="newCustName" class="form-control" placeholder="Customer Name *" style="font-size: 0.9rem;">
                </div>
                <div class="form-group mb-2">
                    <input type="text" id="newCustPhone" class="form-control" placeholder="Phone (Optional)" style="font-size: 0.9rem;">
                </div>
                <div class="d-flex justify-end gap-2">
                    <button class="btn btn-sm" onclick="toggleNewCustomer()" style="background: #e2e8f0; color: #475569; padding: 0.3rem 0.6rem; font-size: 0.85rem;">Cancel</button>
                    <button class="btn btn-sm btn-primary" onclick="saveNewCustomer()" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Save & Select</button>
                </div>
            </div>
        </div>

        <div class="d-flex justify-between items-center">
            <button class="btn btn-danger" onclick="closeCheckout()">Cancel</button>
            <button class="btn btn-primary" id="confirmPaymentBtn">Confirm Pay</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="assets/js/pos.js"></script>

<?php 
// Pre-load customers for simple select (Optimized for small shops)
// For large shops, we'd use AJAX Search for customer
$owner_id = get_owner_id();
$custStmt = $pdo->prepare("SELECT id, name, phone FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$owner_id]);
$customers = $custStmt->fetchAll();
?>
<script>
    const customerList = <?= json_encode($customers) ?>;
    const customerSelect = document.getElementById('checkoutCustomer');
    customerList.forEach(c => {
        let opt = document.createElement('option');
        opt.value = c.id;
        opt.innerText = c.name + (c.phone ? ` (${c.phone})` : '');
        customerSelect.appendChild(opt);
    });
</script>

<?php require_once 'includes/footer.php'; ?>
