<?php
// reset_password.php
require_once 'config/db.php';
require_once 'includes/functions.php';
session_start();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    header("Location: login.php");
    exit;
}

// Verify token
$stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "The password reset link is invalid or has expired.";
} else {
    $email = $reset['email'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password && $confirm_password) {
            if ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $pdo->beginTransaction();

                    // Update user password
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                    $stmt->execute([$password_hash, $email]);

                    // Delete the token
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt->execute([$email]);

                    $pdo->commit();
                    $success = "Your password has been reset successfully! You can now login.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error resetting password: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-light: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .auth-container {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
        }
        .input-group { position: relative; }
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 2.8rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s;
            background: #f8fafc;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }
        .alert-error {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fee2e2;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #dcfce7;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h2 class="auth-title">Reset Password</h2>
            <?php if (!$success && !$error): ?>
                <p class="auth-subtitle">Choose a new password for your account.</p>
            <?php endif; ?>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <a href="forgot_password.php" class="back-link">
                Request a new link
            </a>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <a href="login.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none;">
                Login Now
            </a>
        <?php endif; ?>

        <?php if (!$success && !$error): ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <i class="fas fa-check-circle input-icon"></i>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
