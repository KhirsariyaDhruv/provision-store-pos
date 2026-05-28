<?php
// staff.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') {
    redirect('index.php', 'Access Denied', 'error');
}

$error = '';
$success = '';

// Handle Create Staff
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_staff'])) {
    $username = trim($_POST['username']);
    $temp_password = $_POST['temp_password'];
    $full_name = trim($_POST['full_name']);

    if ($username && $temp_password && $full_name) {
        // Check availability
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken.";
        } else {
            $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
            $owner_id = $_SESSION['user_id']; // Current Admin IS the owner

            try {
                $pdo->beginTransaction();

                // 1. Create User
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, owner_id, status, force_password_change) VALUES (?, ?, 'staff', ?, 'active', 1)");
                $stmt->execute([$username, $password_hash, $owner_id]);
                $new_user_id = $pdo->lastInsertId();

                // 2. Create Profile
                $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, shop_name) VALUES (?, ?, ?)");
                $stmt->execute([$new_user_id, $full_name, $_SESSION['shop_name']]);

                $pdo->commit();
                $success = "Staff member created successfully. They must change password on login.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to create staff: " . $e->getMessage();
            }
        }
    } else {
        $error = "All fields are required.";
    }
}

// Fetch Staff
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.status, u.created_at, p.full_name 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.owner_id = ?
    ORDER BY u.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$staff_members = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-between items-center mb-3">
    <h2>Staff Management</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $error ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <?= $success ?>
    </div>
<?php endif; ?>

<div class="stats-grid staff-grid">
    <!-- Create Staff Form -->
    <div class="card">
        <h3>Add New Staff</h3>
        <form method="POST">
            <input type="hidden" name="create_staff" value="1">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Temporary Password</label>
                <input type="text" name="temp_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
        </form>
    </div>

    <!-- Staff List -->
    <div class="card">
        <h3>Your Staff Members</h3>
        <?php if (count($staff_members) > 0): ?>
            <div class="table-container">
                <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 0.75rem;">Name</th>
                        <th style="padding: 0.75rem;">Username</th>
                        <th style="padding: 0.75rem;">Status</th>
                        <th style="padding: 0.75rem;">Created</th>
                        <th style="padding: 0.75rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_members as $staff): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem;"><?= htmlspecialchars($staff['full_name']) ?></td>
                        <td style="padding: 0.75rem;"><?= htmlspecialchars($staff['username']) ?></td>
                        <td style="padding: 0.75rem;">
                            <span style="padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.8rem; background: <?= $staff['status'] == 'active' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $staff['status'] == 'active' ? '#166534' : '#991b1b' ?>;">
                                <?= ucfirst($staff['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 0.75rem; font-size: 0.9rem; color: #64748b;"><?= date('M d, Y', strtotime($staff['created_at'])) ?></td>
                        <td style="padding: 0.75rem;">
                            <!-- Placeholder for actions like Edit/Delete -->
                            <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Remove</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No staff members found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
