<?php
// host.php - Host Management System
require_once 'config/db.php';
session_start();

$error = '';
$success = '';

if (!isset($_SESSION['host_logged_in']) || !$_SESSION['host_logged_in']) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['host_logged_in']);
    header("Location: host.php");
    exit;
}

$is_logged_in = isset($_SESSION['host_logged_in']) && $_SESSION['host_logged_in'];

// 2. Host Actions
if ($is_logged_in) {
    // Reset User Password
    if (isset($_POST['reset_user_pass'])) {
        $user_id = $_POST['user_id'];
        $temp_pass = "user" . rand(1000, 9999);
        $pass_hash = password_hash($temp_pass, PASSWORD_DEFAULT);

        try {
            // BACKEND VALIDATION: Only reset if request exists
            $checkStmt = $pdo->prepare("SELECT reset_requested FROM users WHERE id = ?");
            $checkStmt->execute([$user_id]);
            if ($checkStmt->fetchColumn() == 1) {
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_requested = 0, is_temp_password = 1 WHERE id = ?");
                $stmt->execute([$pass_hash, $user_id]);
                $success = "Password reset successfully for User ID $user_id. Temporary Password: <strong>$temp_pass</strong>";
            } else {
                $error = "Reset denied. This user has not requested a password reset.";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    // Delete User
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "User ID $user_id deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    // Fetch Users
    $stmt = $pdo->query("SELECT u.*, up.shop_name FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id ORDER BY reset_requested DESC, u.id ASC");
    $users = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard - POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --success: #22c55e;
            --bg: #f8fafc;
            --text: #1e293b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { padding: 0.6rem 1.2rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 500; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Login Card */
        .login-card { max-width: 400px; margin: 100px auto; background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; }
        .login-card h1 { margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; text-align: left; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 0.8rem; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; }

        /* Dashboard */
        .card { background: white; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.2rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; font-weight: 600; color: #64748b; font-size: 0.85rem; text-transform: uppercase; }
        tr.reset-alert { background: #fff1f2; }
        .badge { padding: 0.3rem 0.6rem; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fee2e2; color: #ef4444; }
        .badge-normal { background: #f1f5f9; color: #64748b; }

        .alert { border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; }
        .alert-error { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .header div h1 { font-size: 1.5rem; }
            .btn { width: 100%; text-align: center; }
            td, th { padding: 0.75rem; font-size: 0.85rem; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { min-width: 600px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>Host Dashboard</h1>
            <p style="color: #64748b;">Manage user passwords and accounts</p>
        </div>
        <a href="?logout=1" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User / Shop</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" style="text-align: center;">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?= $user['reset_requested'] ? 'reset-alert' : '' ?>">
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($user['shop_name'] ?? 'No Shop Assigned') ?></small>
                                </td>
                                <td><?= ucfirst($user['role']) ?></td>
                                <td>
                                    <?php if ($user['reset_requested']): ?>
                                        <span class="badge badge-pending">RESET REQUESTED</span>
                                    <?php elseif ($user['is_temp_password']): ?>
                                        <span class="badge" style="background: #fef3c7; color: #d97706;">TEMP PASSWORD</span>
                                    <?php else: ?>
                                        <span class="badge badge-normal">ACTIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['reset_requested']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Generate a temporary password for this user?');">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="reset_user_pass" class="btn btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                Reset Password
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #e2e8f0; color: #94a3b8; cursor: not-allowed;" title="No reset request" disabled>
                                            Reset Password
                                        </button>
                                    <?php endif; ?>
    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; margin-left: 5px;">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
