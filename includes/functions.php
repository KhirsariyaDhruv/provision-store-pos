<?php
// includes/functions.php

function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

function display_flash() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] == 'error' ? 'danger' : 'success'; // Map to bootstrap classes if used
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $_SESSION['flash_message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

function get_shop_name($pdo, $user_id) {
    // 1. Get Owner ID (if staff)
    $stmt = $pdo->prepare("SELECT role, owner_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $target_id = ($user['role'] == 'staff' && $user['owner_id']) ? $user['owner_id'] : $user_id;
        
        // 2. Fetch Shop Name from Profile
        $stmtProfile = $pdo->prepare("SELECT shop_name FROM user_profiles WHERE user_id = ?");
        $stmtProfile->execute([$target_id]);
        $profile = $stmtProfile->fetch();
        
        return $profile['shop_name'] ?? 'My Shop';
    }
    return 'POS System';
}

// Helper to get the correct Shop Owner ID for queries
function get_owner_id() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = $_SESSION['user_id'] ?? null;
    $owner_id = $_SESSION['owner_id'] ?? null;
    return $owner_id ? $owner_id : $user_id;
}
