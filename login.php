<?php
// login.php
require_once 'config/db.php';
require_once 'includes/functions.php';
session_start();

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_temp_password']) && $_SESSION['is_temp_password']) {
        header("Location: change_password.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username && $password) {
        // HOST LOGIN CHECK (Unified)
        if ($username === 'admin' && $password === 'admin123') {
            session_regenerate_id(true);
            $_SESSION['host_logged_in'] = true;
            $_SESSION['username'] = 'Host Admin';
            header("Location: host.php");
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['owner_id'] = $user['owner_id'];
            $_SESSION['shop_name'] = $user['shop_name'] ?? 'My Shop';
            $_SESSION['force_password_change'] = $user['force_password_change'];
            $_SESSION['is_temp_password'] = $user['is_temp_password'];
            
            if ($user['is_temp_password']) {
                redirect('change_password.php', 'Your password was reset by the host. Please set a new password.');
            }
            
            $logStmt = $pdo->prepare("INSERT INTO login_history (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
            $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

            redirect('index.php', 'Login successful!');
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; /* Blue 600 */
            --primary-hover: #1d4ed8; /* Blue 700 */
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
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow-x: hidden;
        }

        /* LEFT HERO SIDE */
        .hero-section {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        /* Abstract Shapes */
        .hero-section::before, .hero-section::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }
        .hero-section::before {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -50px;
        }
        .hero-section::after {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
        }

        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 500px;
        }

        .hero-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
        }

        .hero-text {
            font-size: 1.25rem;
            line-height: 1.6;
            opacity: 0.9;
            font-weight: 300;
        }

        /* RIGHT FORM SIDE */
        .form-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: white;
            padding: 2rem;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            color: var(--text-muted);
        }

        /* Toggle Tabs */
        .auth-tabs {
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            display: flex;
            margin-bottom: 2rem;
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .auth-tab.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-weight: 600;
        }

        /* Form Elements */
        .form-group { margin-bottom: 1.25rem; }
        
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
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 2.8rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .form-control:focus + .input-icon, 
        .input-group:focus-within .input-icon {
             color: var(--primary);
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
            margin-top: 1rem;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);
        }

        .error-alert {
            background: #fef2f2;
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #fee2e2;
            text-align: center;
        }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .hero-section { display: none; }
            .form-section { padding: 1.5rem; justify-content: center; min-height: 100vh; }
            .auth-container { max-width: 100%; }
        }
        @keyframes blink { 50% { opacity: 0; } }
        .cursor { animation: blink 1s step-end infinite; color: rgba(255,255,255,0.8); }
    </style>
</head>
<body>

    <!-- LEFT HERO -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-logo">
                <i class="fas fa-cubes"></i> POS System
            </div>
            <h1 class="hero-title">
                <span id="typewriter"></span><span class="cursor">|</span><br>
                Store Smarter.
            </h1>
            <p class="hero-text">
                Streamline sales, track inventory, and manage customers with our advanced Point of Sale system.
            </p>
        </div>
    </div>

    <!-- RIGHT FORM -->
    <div class="form-section">
        <div class="auth-container">
            <div class="auth-header">
                <h2 class="auth-title">Welcome Back</h2>
                <p class="auth-subtitle">Please enter your details to sign in.</p>
            </div>

            <div class="auth-tabs">
                <a href="login.php" class="auth-tab active">Login</a>
                <a href="register.php" class="auth-tab">Register</a>
            </div>

            <?php if($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <div style="text-align: right; margin-top: 5px;">
                        <a href="forgot_password.php" style="font-size: 0.85rem; color: var(--primary); text-decoration: none;">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    Login
                </button>
            </form>
           
        </div>
        
        <div style="margin-top: 2rem; color: var(--text-muted); font-size: 0.9rem;">
             &copy; <?= date('Y') ?> POS System. All rights reserved.
        </div>
    </div>

<script>
    const phrases = ["Welcome", "नमस्ते", "કેમ છો", "Pranam"];
    let phraseIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    const typeSpeed = 100;
    const deleteSpeed = 50;
    const pauseTime = 2000;
    const element = document.getElementById('typewriter');

    function type() {
        const currentPhrase = phrases[phraseIndex];
        
        if (isDeleting) {
            element.textContent = currentPhrase.substring(0, charIndex - 1);
            charIndex--;
        } else {
            element.textContent = currentPhrase.substring(0, charIndex + 1);
            charIndex++;
        }

        if (!isDeleting && charIndex === currentPhrase.length) {
            isDeleting = true;
            setTimeout(type, pauseTime);
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            phraseIndex = (phraseIndex + 1) % phrases.length;
            setTimeout(type, 500);
        } else {
            setTimeout(type, isDeleting ? deleteSpeed : typeSpeed);
        }
    }

    document.addEventListener('DOMContentLoaded', type);
</script>
</body>
</html>
