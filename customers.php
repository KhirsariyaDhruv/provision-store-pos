<?php
// customers.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$owner_id = get_owner_id();
$success = '';
$error = '';

// Handle Add Customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO customers (user_id, name, phone, total_due) VALUES (?, ?, ?, 0.00)");
        if ($stmt->execute([$owner_id, $name, $phone])) {
            $success = "Customer added successfully.";
        } else {
            $error = "Failed to add customer.";
        }
    } else {
        $error = "Name is required.";
    }
}

// Handle Delete Customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_customer'])) {
    $cust_id = $_POST['customer_id'];
    // Verify ownership
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$cust_id, $owner_id])) {
        $success = "Customer deleted successfully.";
    } else {
        $error = "Failed to delete customer.";
    }
}

// Fetch Customers
$stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ? ORDER BY total_due DESC, name ASC");
$stmt->execute([$owner_id]);
$customers = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-between items-center mb-3">
    <h2>Khata & Customers</h2>
    <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-user-plus"></i> Add Customer</button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $success ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                    <th style="padding: 1rem;">Customer Name</th>
                    <th style="padding: 1rem;">Phone</th>
                    <th style="padding: 1rem;">Pending Due (Khata)</th>
                    <th style="padding: 1rem; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($customers) > 0): ?>
                    <?php foreach ($customers as $c): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 1rem; font-weight: 500;"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="padding: 1rem; color: #64748b;"><?= htmlspecialchars($c['phone']) ?></td>
                        <td style="padding: 1rem;">
                            <?php if ($c['total_due'] > 0): ?>
                                <span style="color: #dc2626; font-weight: bold;">₹<?= number_format($c['total_due'], 2) ?></span>
                            <?php else: ?>
                                <span style="color: #166534;">Settled</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <div class="d-flex gap-2 justify-center">
                                <a href="khata.php?id=<?= $c['id'] ?>" class="btn" style="background: #e0e7ff; color: #4338ca; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                    View Ledger <i class="fas fa-arrow-right"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? This will delete the customer and their Khata history.');">
                                    <input type="hidden" name="delete_customer" value="1">
                                    <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn" style="background: #fee2e2; color: #991b1b; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding: 2rem; text-align: center; color: #64748b;">No customers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Customer Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 400px;">
        <div class="d-flex justify-between items-center mb-3">
            <h3>Add Customer</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_customer" value="1">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Save Customer</button>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('addModal').style.display = 'block'; }
    function closeModal() { document.getElementById('addModal').style.display = 'none'; }
</script>

<?php require_once 'includes/footer.php'; ?>
