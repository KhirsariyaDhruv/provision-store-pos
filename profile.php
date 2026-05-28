<?php
// profile.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $shop_name = trim($_POST['shop_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_username = trim($_POST['username']);

    if ($full_name && $shop_name && $new_username) {
        $pdo->beginTransaction();
        try {
            // Check username uniqueness if changed
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_username = $stmt->fetchColumn();

            if ($new_username !== $current_username) {
                // Check if taken
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$new_username, $user_id]);
                if ($stmt->fetch()) {
                    throw new Exception("Username '$new_username' is already taken.");
                }

                // Update users table
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$new_username, $user_id]);
            }

            // Check if profile exists
            $stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->fetch()) {
                // Update
                $stmt = $pdo->prepare("UPDATE user_profiles SET full_name = ?, shop_name = ?, phone = ?, address = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$full_name, $shop_name, $phone, $address, $user_id]);
            } else {
                // Insert (if not exists for some reason)
                $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, shop_name, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $full_name, $shop_name, $phone, $address]);
            }
            
            // Update Session Shop Name if Admin
            if ($_SESSION['role'] === 'admin') {
                $_SESSION['shop_name'] = $shop_name;
            }

            $pdo->commit();
            $success = "Profile updated successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = "Name, Username, and Shop Name are required.";
    }
}

// Fetch User Details
$stmt = $pdo->prepare("
    SELECT u.username, u.role, u.created_at, 
           p.full_name, p.shop_name, p.phone, p.address 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Login History
$stmt = $pdo->prepare("SELECT login_time, ip_address, user_agent FROM login_history WHERE user_id = ? ORDER BY login_time DESC LIMIT 5");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-between items-center mb-3">
    <h2>My Profile</h2>
</div>

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

<div class="stats-grid profile-layout">
    
    <!-- Profile Edit Form -->
    <div class="card">
        <div class="d-flex justify-between items-center mb-3">
            <h3>Edit Details</h3>
            <span class="badge" style="background: #e0e7ff; color: #4338ca; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                <?= ucfirst($user['role']) ?> Account
            </span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="row mb-4" style="display: flex; gap: 1.5rem;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required style="height: 3.5rem; border-radius: 10px;">
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label">Shop Name</label>
                    <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($user['shop_name'] ?? '') ?>" <?= $_SESSION['role'] == 'staff' ? 'readonly style="background: #f1f5f9; height: 3.5rem; border-radius: 10px;"' : 'required style="height: 3.5rem; border-radius: 10px;"' ?>>
                </div>
            </div>

            <div class="row mb-4" style="display: flex; gap: 1.5rem;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" style="height: 3.5rem; border-radius: 10px;">
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required style="height: 3.5rem; border-radius: 10px;">
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" style="border-radius: 10px; resize: none;"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-3" style="width: 100%;">
                <button type="submit" class="btn btn-primary" style="flex: 1; height: 3.5rem; border-radius: 10px; font-weight: 700; background: #6366f1;">Save Changes</button>
                <a href="change_password.php" class="btn" style="flex: 1; height: 3.5rem; border-radius: 10px; background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-weight: 600;">Change Password</a>
            </div>
        </form>
    </div>

    <!-- Login History & Info -->
    <div>
        <div class="card">
            <h3>Login History</h3>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($history as $log): ?>
                    <li style="border-bottom: 1px solid #f1f5f9; padding: 0.75rem 0; font-size: 0.9rem; color: #475569;">
                        <div style="font-weight: 500; color: #1e293b;"><i class="far fa-clock"></i> <?= date('d M Y, h:i A', strtotime($log['login_time'])) ?></div>
                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                            IP: <?= htmlspecialchars($log['ip_address']) ?> 
                            <span style="border-left: 1px solid #cbd5e1; margin-left: 8px; padding-left: 8px;">
                                <?= htmlspecialchars(substr($log['user_agent'] ?? 'Unknown', 0, 30)) ?>...
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border: none; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 5rem; opacity: 0.1; color: white;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 style="color: white; margin-bottom: 0.5rem; position: relative;">Security & Tools</h3>
            <p style="font-size: 0.85rem; opacity: 0.8; line-height: 1.4; margin-bottom: 1.5rem; position: relative;">
                Keep your shop data safe. Regularly update your password and keep offline backups.
            </p>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="export_shop_data.php" class="btn" style="width: 100%; justify-content: center; background: #22c55e; color: white; font-weight: 600; padding: 0.8rem; border-radius: 10px; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); position: relative; transition: 0.3s;">
                    <i class="fas fa-file-excel"></i> Download All Shop Data
                </a>
                <p style="font-size: 0.75rem; text-align: center; margin-top: 0.75rem; opacity: 0.6; position: relative;">
                    <i class="fas fa-info-circle"></i> Exports Products, Customers & Sales
                </p>
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid rgba(255,255,255,0.1); position: relative;">
                <h4 style="color: white; font-size: 0.9rem; margin-bottom: 1rem; position: relative;">Restore Data</h4>
                <form action="import_shop_data.php" method="POST" enctype="multipart/form-data" style="position: relative;">
                    <div style="background: rgba(255,255,255,0.05); border: 2px dashed rgba(255,255,255,0.2); border-radius: 10px; padding: 1rem; text-align: center; margin-bottom: 1rem; transition: 0.3s;" id="dropZone">
                        <label for="import_file" style="cursor: pointer; display: block;">
                            <i class="fas fa-file-upload" style="font-size: 1.5rem; margin-bottom: 0.5rem; opacity: 0.8;"></i>
                            <p style="font-size: 0.8rem; opacity: 0.7;">Click to select CSV backup file</p>
                        </label>
                        <input type="file" name="import_file" id="import_file" style="display: none;" accept=".csv" required onchange="this.form.submit()">
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
