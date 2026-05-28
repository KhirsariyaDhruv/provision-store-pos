<?php
// add_multiple_products.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$owner_id = get_owner_id();
$success = '';
$error = '';

// Handle Bulk Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_add'])) {
    try {
        $products = $_POST['products']; // Array of products
        $count = 0;

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO products (user_id, name, price, cost_price, weight, stock, expiry_date, barcode, barcode_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)");

        foreach ($products as $p) {
            $name = trim($p['name']);
            $price = $p['price'];
            
            if (!empty($name) && $price !== '') {
                // Generate Unique Barcode
                $prefix = 'POS'; 
                $rand = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                $barcode = "$prefix-$rand";

                $cost = $p['cost'] ?: 0;
                $weight = $p['weight'] ?: null;
                $stock = $p['stock'] ?: 0;
                $expiry = !empty($p['expiry']) ? $p['expiry'] : null;

                $stmt->execute([$owner_id, $name, $price, $cost, $weight, $stock, $expiry, $barcode]);
                $count++;
            }
        }

        $pdo->commit();
        $success = "$count products added successfully!";
        // header("Location: inventory.php?success=" . urlencode($success)); // Optional redirect
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding products: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-between items-center mb-3">
    <h2><i class="fas fa-layer-group"></i> Add Multiple Products</h2>
    <a href="inventory.php" class="btn" style="background: #e2e8f0; color: #475569;">
        <i class="fas fa-arrow-left"></i> Back to Inventory
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $success ?> <a href="inventory.php" style="text-decoration: underline; color: #166534; font-weight: bold;">View Inventory</a>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $error ?>
    </div>
<?php endif; ?>

<form method="POST" id="bulkForm">
    <input type="hidden" name="bulk_add" value="1">
    
    <div class="card" style="overflow-x: auto; padding: 0;">
        <table class="table" style="min-width: 800px;">
            <thead style="background: #f1f5f9;">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 25%;">Product Name <span style="color: red;">*</span></th>
                    <th style="width: 15%;">Price (₹) <span style="color: red;">*</span></th>
                    <th style="width: 15%;">Cost (₹)</th>
                    <th style="width: 10%;">Weight</th>
                    <th style="width: 10%;">Stock</th>
                    <th style="width: 15%;">Expiry Date</th>
                    <th style="width: 50px;"></th>
                </tr>
            </thead>
            <tbody id="productRows">
                <!-- Rows will be added here via JS -->
            </tbody>
        </table>
        
        <div style="padding: 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
            <button type="button" class="btn" style="background: #e0e7ff; color: #4338ca; border: 1px dashed #4338ca;" onclick="addRow()">
                <i class="fas fa-plus"></i> Add Row
            </button>
        </div>
    </div>

    <div class="d-flex justify-between items-center" style="margin-top: 2rem;">
        <span class="text-muted">Barcodes will be auto-generated for all items.</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn" style="background: #e2e8f0; color: #475569;" onclick="clearAllRows()">
                <i class="fas fa-eraser"></i> Clear All
            </button>
            <button type="button" class="btn" style="background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa;" onclick="saveDraft()">
                <i class="fas fa-bookmark"></i> Save Draft
            </button>
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1.1rem;">
                <i class="fas fa-save"></i> Save All Products
            </button>
        </div>
    </div>
</form>

<script>
    let rowCount = 0;

    function addRow() {
        rowCount++;
        const tbody = document.getElementById('productRows');
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td style="text-align: center; color: #94a3b8; font-weight: bold; vertical-align: middle;">${rowCount}</td>
            <td>
                <input type="text" name="products[${rowCount}][name]" class="form-control" placeholder="Item Name">
            </td>
            <td>
                <input type="number" step="0.01" name="products[${rowCount}][price]" class="form-control" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" name="products[${rowCount}][cost]" class="form-control" placeholder="0.00">
            </td>
            <td>
                <input type="text" name="products[${rowCount}][weight]" class="form-control" placeholder="e.g. 1kg">
            </td>
            <td>
                <input type="number" name="products[${rowCount}][stock]" class="form-control" value="10">
            </td>
            <td>
                <input type="date" name="products[${rowCount}][expiry]" class="form-control">
            </td>
            <td style="vertical-align: middle;">
                <button type="button" class="btn" style="color: #ef4444; padding: 0.5rem;" onclick="removeRow(this)" title="Remove Row">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        
        // Auto-focus name field of new row (only if not restoring draft)
        // setTimeout(() => tr.querySelector('input[name*="[name]"]').focus(), 50);
    }

    function removeRow(btn) {
        if(document.querySelectorAll('#productRows tr').length > 1) {
            btn.closest('tr').remove();
        } else {
            alert("At least one row is required.");
        }
    }

    // Add 5 rows by default
    for(let i=0; i<5; i++) addRow();

    // Prevent accidental navigation
    window.onbeforeunload = function() {
        // Simple check if form has data could be added here
        // return "Are you sure you want to leave?";
    };
    
    document.getElementById('bulkForm').onsubmit = function(e) {
        window.onbeforeunload = null; // Disable warning on submit
        
        // Client-side Validation
        const rows = document.querySelectorAll('#productRows tr');
        let isValid = true;
        
        rows.forEach((row, index) => {
            const nameInput = row.querySelector('input[name*="[name]"]');
            const priceInput = row.querySelector('input[name*="[price]"]');
            
            const name = nameInput.value.trim();
            const price = priceInput.value.trim();
            
            // If Name is entered but Price is empty
            if (name && price === '') {
                e.preventDefault();
                isValid = false;
                priceInput.style.border = "2px solid #ef4444";
                priceInput.style.background = "#fee2e2";
                if(document.activeElement !== priceInput) priceInput.focus();
            } 
            // If Price is entered but Name is empty (NEW)
            else if (price && name === '') {
                e.preventDefault();
                isValid = false;
                nameInput.style.border = "2px solid #ef4444";
                nameInput.style.background = "#fee2e2";
                if(document.activeElement !== nameInput) nameInput.focus();
            } 
            else {
                // Reset styles
                priceInput.style.border = "";
                priceInput.style.background = "";
                nameInput.style.border = "";
                nameInput.style.background = "";
            }
        });

        if (!isValid) {
            alert("Please ensure both Product Name and Price are entered for all items.");
            return false;
        }

        // Clear Draft on successful submit
        localStorage.removeItem('bulkProductDraft');
    };

    // --- Draft Logic ---
    function saveDraft() {
        const rows = [];
        document.querySelectorAll('#productRows tr').forEach(tr => {
            rows.push({
                name: tr.querySelector('input[name*="[name]"]').value,
                price: tr.querySelector('input[name*="[price]"]').value,
                cost: tr.querySelector('input[name*="[cost]"]').value,
                weight: tr.querySelector('input[name*="[weight]"]').value,
                stock: tr.querySelector('input[name*="[stock]"]').value,
                expiry: tr.querySelector('input[name*="[expiry]"]').value,
            });
        });
        localStorage.setItem('bulkProductDraft', JSON.stringify(rows));
        alert('Draft saved successfully! You can close this page and come back later.');
    }

    function loadDraft() {
        const saved = localStorage.getItem('bulkProductDraft');
        if (saved) {
            if(confirm('We found a saved draft. Do you want to restore it?')) {
                const data = JSON.parse(saved);
                // Clear existing rows except header
                document.getElementById('productRows').innerHTML = '';
                rowCount = 0; // Reset counter
                
                data.forEach(item => {
                    addRow(); // Creates row and increments rowCount
                    const tr = document.querySelector('#productRows tr:last-child');
                    tr.querySelector('input[name*="[name]"]').value = item.name;
                    tr.querySelector('input[name*="[price]"]').value = item.price;
                    tr.querySelector('input[name*="[cost]"]').value = item.cost;
                    tr.querySelector('input[name*="[weight]"]').value = item.weight;
                    tr.querySelector('input[name*="[stock]"]').value = item.stock;
                    tr.querySelector('input[name*="[expiry]"]').value = item.expiry;
                });
            }
        }
    }

    function clearAllRows() {
        if(confirm('Are you sure you want to clear all rows? This cannot be undone.')) {
            document.querySelectorAll('#productRows input').forEach(input => {
                if(input.type === 'number' && input.name.includes('[stock]')) {
                    input.value = "10"; // Default stock
                } else {
                    input.value = "";
                }
            });
            localStorage.removeItem('bulkProductDraft');
        }
    }

    // Check for draft on load
    window.addEventListener('load', loadDraft);

</script>

<?php require_once 'includes/footer.php'; ?>
