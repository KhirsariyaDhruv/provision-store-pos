<?php
// change_password.php
require_once 'config/db.php';
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($current_password && $new_password && $confirm_password) {
        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            // Verify Current Password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_hash = $stmt->fetchColumn();

            if (password_verify($current_password, $user_hash)) {
                // Update Password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, force_password_change = 0, is_temp_password = 0 WHERE id = ?");
                
                if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
                    $_SESSION['force_password_change'] = 0;
                    $_SESSION['is_temp_password'] = 0;
                    $success = "Password changed successfully! You can now use your new password.";
                    // Optional: Redirect after delay or show success
                    // redirect('index.php', 'Password changed successfully!'); // Immediate redirect
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Provision Store POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; background: #e2e8f0; min-height: 100vh; }
        .login-card { 
            background: white; 
            padding: 2.5rem; 
            border-radius: var(--radius); 
            box-shadow: var(--shadow); 
            width: 100%; 
            max-width: 400px; 
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2>Change Password</h2>
            <p class="text-muted">Secure your account</p>
        </div>
        
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div style="background: #dcfce7; color: #166534; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                <?= $success ?>
                <div style="margin-top: 10px;">
                    <a href="index.php" class="btn btn-primary" style="width: 100%; justify-content: center;">Go to Dashboard</a>
                </div>
            </div>
        <?php else: ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
             <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Password</button>
        </form>
         <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
            <a href="index.php" style="color: var(--secondary-color);">Cancel / Back to Dashboard</a>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>
