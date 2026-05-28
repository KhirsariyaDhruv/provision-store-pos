<?php
// inventory.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if owner or staff (staff uses owner_id)
$owner_id = get_owner_id();

$success = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

// UPDATE LOGIC
        if ($action == 'add' || $action == 'edit') {
            $name = trim($_POST['name']);
            $price = $_POST['price'];
            $cost_price = $_POST['cost_price'] ?: 0;
            $stock = $_POST['stock'];
            $weight = trim($_POST['weight']);
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            
            if ($name && $price !== '') {
                function generateBarcode($owner_id) {
                    $prefix = 'POS'; 
                    $rand = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                    return "$prefix-$rand";
                }

                try {
                    $posted_barcode = trim($_POST['barcode'] ?? '');

                    if ($action == 'add') {
                        $barcode = $posted_barcode ?: generateBarcode($owner_id);
                        
                        $stmt = $pdo->prepare("INSERT INTO products (user_id, name, price, cost_price, weight, stock, expiry_date, barcode, barcode_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)");
                        $stmt->execute([$owner_id, $name, $price, $cost_price, $weight, $stock, $expiry_date, $barcode]);
                        $success = "Product added successfully. Barcode: <strong>$barcode</strong>";
                    } elseif ($action == 'edit') {
                        $id = $_POST['product_id'];
                        
                        $chk = $pdo->prepare("SELECT barcode FROM products WHERE id = ? AND user_id = ?");
                        $chk->execute([$id, $owner_id]);
                        $old = $chk->fetch();

                        $new_barcode = $old['barcode'];
                        if ($posted_barcode && $posted_barcode !== $old['barcode']) {
                            $new_barcode = $posted_barcode;
                        }

                        $update_sql = "UPDATE products SET name = ?, price = ?, cost_price = ?, weight = ?, stock = ?, expiry_date = ?, barcode = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
                        $stmt = $pdo->prepare($update_sql);
                        $stmt->execute([$name, $price, $cost_price, $weight, $stock, $expiry_date, $new_barcode, $id, $owner_id]);
                        
                        $success = "Product updated successfully.";
                    }
                } catch (PDOException $e) {
                    $error = "Database Error: " . $e->getMessage();
                }
            } else {
                $error = "Name and Price are required.";
            }
        } elseif ($action == 'delete') {
            $id = $_POST['product_id'];
            // Soft Delete: Deactivate barcode and maybe set stock to 0 to prevent sales
            $stmt = $pdo->prepare("UPDATE products SET barcode_active = FALSE, stock = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $owner_id]);
            $success = "Product deactivated successfully.";
        }
    }
}

// Fetch Products (Active Only by default, or all? Let's show Active to keep list clean, or provide toggle. 
// For now, let's show ALL but order by active status so inactive are at bottom)
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM products WHERE user_id = ? AND barcode_active = TRUE";
$params = [$owner_id];

if ($search) {
    $sql .= " AND (name LIKE ? OR barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Order by Newest
// Order by: Low Stock first, then Alphabetical by Name
$sql .= " ORDER BY (stock < 5) DESC, name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-between items-center mb-3">

    <h2>Inventory Management</h2>
    <div class="d-flex gap-2">
        <!-- New Dropdown/Split Button Logic -->
        <button class="btn btn-primary" onclick="showAddOptions()">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>
</div>

<!-- Add SweetAlert2 for Selection Modal -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showAddOptions() {
        Swal.fire({
            title: 'Add Product',
            text: 'How would you like to add products?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-cube"></i> Add Single Product',
            confirmButtonColor: '#4f46e5',
            cancelButtonText: '<i class="fas fa-layer-group"></i> Add Multiple Products',
            cancelButtonColor: '#10b981',
            showCloseButton: true,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Single Product
                openModal('addModal');
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Multiple Products
                window.location.href = 'add_multiple_products.php';
            }
        });
    }
</script>

<?php if ($success): ?>
    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $success ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $error ?>
    </div>
<?php endif; ?>

<!-- Search Bar -->
<div class="card" style="padding: 1rem;">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control" placeholder="Search by name, barcode, or category..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if($search): ?><a href="inventory.php" class="btn" style="background: #e2e8f0; color: #475569;">Clear</a><?php endif; ?>
    </form>
</div>

<!-- Product Table -->
<div class="card">
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                    <th style="padding: 1rem;">Product Name</th>
                    <!-- <th style="padding: 1rem;">Category</th> -->
                    <th style="padding: 1rem;">Price (₹)</th>
                    <th style="padding: 1rem;">Cost (₹)</th>
                    <th style="padding: 1rem;">Profit (₹)</th>
                    <th style="padding: 1rem;">Weight</th>
                    <th style="padding: 1rem;">Stock</th>
                    <th style="padding: 1rem;">Expiry</th>
                    <th style="padding: 1rem;">Barcode</th>
                    <th style="padding: 1rem; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): 
                        $isActive = $p['barcode_active'];
                        $rowStyle = $isActive ? "border-bottom: 1px solid #f1f5f9;" : "border-bottom: 1px solid #f1f5f9; background: #f8fafc; opacity: 0.7;";
                    ?>
                    <tr style="<?= $rowStyle ?>">
                        <td style="padding: 1rem; font-weight: 500;">
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if(!$isActive): ?><span class="badge" style="background: #fee2e2; color: #991b1b; margin-left: 5px;">Inactive</span><?php endif; ?>
                        </td>
                        <!-- <td style="padding: 1rem;"><span class="badge" style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($p['category']) ?></span></td> -->
                        <td style="padding: 1rem;">₹<?= number_format($p['price'], 2) ?></td>
                        <td style="padding: 1rem;">₹<?= number_format($p['cost_price'] ?? 0, 2) ?></td>
                        <td style="padding: 1rem; color: #166534; font-weight: bold;">₹<?= number_format($p['price'] - ($p['cost_price'] ?? 0), 2) ?></td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($p['weight']) ?></td>
                        <td style="padding: 1rem; color: <?= $isActive ? ($p['stock'] < 5 ? '#dc2626' : '#166534') : '#64748b' ?>; font-weight: bold;">
                            <?= $p['stock'] ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if (!empty($p['expiry_date'])): ?>
                                <?php 
                                    $expiry = $p['expiry_date'];
                                    $isExpired = strtotime($expiry) < time();
                                ?>
                                <span style="<?= $isExpired ? 'color: #dc2626; font-weight: bold;' : 'color: #64748b;' ?>">
                                    <?= date('d M Y', strtotime($expiry)) ?>
                                    <?= $isExpired ? '(Expired)' : '' ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; font-family: monospace; letter-spacing: 1px;"><?= htmlspecialchars($p['barcode']) ?></td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php if($isActive): ?>
                            <button class="btn" style="padding: 0.4rem; color: #4f46e5;" onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn" style="padding: 0.4rem; color: #0891b2;" onclick="downloadBarcode('<?= $p['barcode'] ?>', '<?= htmlspecialchars($p['name']) ?>')">
                                <i class="fas fa-download"></i>
                            </button>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Deactivate this product?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn" style="padding: 0.4rem; color: #ef4444;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.8rem;">Deactivated</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="padding: 2rem; text-align: center; color: #64748b;">No products found. Add one to get started!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;"></div>

<!-- Add/Edit Modal -->
<div id="productModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 90%; max-width: 500px; z-index: 1001;">
    <div class="d-flex justify-between items-center mb-3">
        <h3 id="modalTitle">Add Product</h3>
        <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>
    <form method="POST" id="productForm">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="product_id" id="productId">
        
        <div class="form-group">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" id="pName" class="form-control" required>
        </div>
        
        <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0;">
            <div class="form-group">
                <label class="form-label">Weight/Unit <small class="text-muted">(Optional)</small></label>
                <input type="text" name="weight" id="pWeight" class="form-control" placeholder="e.g. 1kg, 500g">
            </div>
            <div class="form-group">
                <label class="form-label">Stock Quantity</label>
                <input type="number" name="stock" id="pStock" class="form-control" value="0" required>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Expiry Date <small class="text-muted">(Optional)</small></label>
            <input type="date" name="expiry_date" id="pExpiry" class="form-control">
        </div>

        <!-- Removed Category Input -->

        <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0;">
            <div class="form-group">
                <label class="form-label">Price (₹)</label>
                <input type="number" step="0.01" name="price" id="pPrice" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Cost Price (₹) <small>(Optional)</small></label>
                <input type="number" step="0.01" name="cost_price" id="pCostPrice" class="form-control" placeholder="0.00">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Barcode <small class="text-muted">(Optional)</small></label>
            <input type="text" name="barcode" id="pBarcode" class="form-control" placeholder="Leave blank to auto-generate">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Save Product</button>
    </form>
</div>

<!-- Invisible Canvas/Image for Download -->
<canvas id="barcodeCanvas" style="display: none;"></canvas>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    const modal = document.getElementById('productModal');
    const overlay = document.getElementById('modalOverlay');
    const form = document.getElementById('productForm');
    const title = document.getElementById('modalTitle');
    const actionInput = document.getElementById('formAction');
    const idInput = document.getElementById('productId');

    function openModal(type) {
        modal.style.display = 'block';
        overlay.style.display = 'block';
        if (type === 'addModal') {
            form.reset();
            title.innerText = 'Add Product';
            actionInput.value = 'add';
            idInput.value = '';
        }
    }

    function editProduct(product) {
        openModal();
        title.innerText = 'Edit Product';
        actionInput.value = 'edit';
        idInput.value = product.id;
        
        document.getElementById('pName').value = product.name;
        // document.getElementById('pCategory').value = product.category; // Removed
        document.getElementById('pWeight').value = product.weight;
        document.getElementById('pPrice').value = product.price;
        document.getElementById('pCostPrice').value = product.cost_price || 0;
        document.getElementById('pStock').value = product.stock;
        document.getElementById('pExpiry').value = product.expiry_date || '';
        document.getElementById('pBarcode').value = product.barcode;
    }

    function closeModal() {
        modal.style.display = 'none';
        overlay.style.display = 'none';
    }
    
    function downloadBarcode(code, name) {
        const canvas = document.getElementById('barcodeCanvas');
        JsBarcode(canvas, code, {
            format: "CODE128",
            displayValue: true,
            text: name + " - " + code,
            fontSize: 14,
            height: 50
        });
        
        const link = document.createElement('a');
        link.download = filterFilename(name) + '-barcode.png';
        link.href = canvas.toDataURL();
        link.click();
    }
    
    function filterFilename(name) {
        return name.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    }

    overlay.onclick = closeModal;

    // --- Live Search Functionality ---
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.cells[0]?.textContent.toLowerCase() || '';
                const barcode = row.cells[7]?.textContent.toLowerCase() || ''; // Index 7 is Barcode col
                
                if (name.includes(filter) || barcode.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Disable form submit for purely client-side feeling (optional, but requested 'live')
        // Actually, let's keep form submit for full DB search if items are paginated later.
        // But for now, prevent enter key from reloading if we want just live filter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent reload, rely on live filter
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>
