<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(get_shop_name($pdo, $_SESSION['user_id'] ?? 0)) ?> - POS</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
    <!-- Sidebar -->
    <aside class="sidebar desktop-only">
        <div class="sidebar-header">
            <i class="fas fa-store"></i> <?= htmlspecialchars(get_shop_name($pdo, $_SESSION['user_id'])) ?>
        </div>
        <ul class="nav-links">
            <li>
                <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="pos.php" class="<?= $current_page == 'pos.php' ? 'active' : '' ?>">
                    <i class="fas fa-cash-register"></i> Billing / POS
                </a>
            </li>
            <li>
                <a href="inventory.php" class="<?= $current_page == 'inventory.php' ? 'active' : '' ?>">
                    <i class="fas fa-boxes"></i> Inventory
                </a>
            </li>
            <li>
                <a href="customers.php" class="<?= $current_page == 'customers.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Customers / Khata
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
            </li>
            <?php if($_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="staff.php" class="<?= $current_page == 'staff.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Staff Management
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="about.php" class="<?= $current_page == 'about.php' ? 'active' : '' ?>">
                    <i class="fas fa-info-circle"></i> About Us
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </li>
        </ul>
    </aside>
<?php endif; ?>

    <div class="main-content">
        <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="top-navbar" style="padding: 0 1.5rem;">
            <div class="user-greeting">
                <span style="font-size: 0.95rem; color: #64748b;">Welcome, <strong style="color: #1e293b;"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong> (<?= ucfirst($_SESSION['role'] ?? '') ?>)</span>
            </div>
            
            <div class="header-right-actions d-flex items-center">
                <a href="logout.php" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 8px; background: #ef4444; border: none; font-weight: 600;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
        <?php endif; ?>
        
        <div class="page-content">
            <?php if (function_exists('display_flash')) display_flash(); ?>
