<?php
// forgot_password.php - Notify Host for Password Reset
require_once 'config/db.php';
require_once 'includes/functions.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);

    if ($username) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            try {
                // Set reset_requested to 1
                $stmt = $pdo->prepare("UPDATE users SET reset_requested = 1 WHERE id = ?");
                $stmt->execute([$user['id']]);
                $success = "A request has been sent to the HOST. Please contact the administrator for your temporary password.";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            // Standard message for security
            $success = "If the username is valid, a request has been sent to the HOST.";
        }
    } else {
        $error = 'Please enter your username.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --bg-light: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-light); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .auth-container { width: 100%; max-width: 420px; background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-title { font-size: 1.8rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; }
        .auth-subtitle { color: var(--text-muted); font-size: 0.95rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500; color: var(--text-main); }
        .input-group { position: relative; }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .form-control { width: 100%; padding: 1rem 1rem 1rem 2.8rem; border: 2px solid var(--border); border-radius: 12px; font-size: 1rem; transition: all 0.2s; background: #f8fafc; font-family: inherit;}
        .form-control:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .btn-primary { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4); }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center; }
        .alert-error { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h2 class="auth-title">Forgot Password</h2>
            <p class="auth-subtitle">Request a reset from the HOST.</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Send Request to Host
            </button>
        </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</body>
</html>
