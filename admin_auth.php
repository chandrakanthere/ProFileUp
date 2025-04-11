<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to admin login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Check if user has permission to access admin panel
function hasPermission($permissionName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles ur
                              JOIN role_permissions rp ON ur.role_id = rp.role_id
                              JOIN permissions p ON rp.permission_id = p.id
                              WHERE ur.user_id = ? AND p.name = ?");
        $stmt->execute([$_SESSION['user_id'], $permissionName]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

// Check admin access permission
if (!hasPermission('access_admin')) {
    // Clear session and redirect to admin login
    session_unset();
    session_destroy();
    header('Location: admin_login.php?error=unauthorized');
    exit();
}

// Get current user's permissions
function getUserPermissions() {
    global $pdo;
    $permissions = [];
    
    try {
        $stmt = $pdo->prepare("SELECT p.name FROM user_roles ur
                              JOIN role_permissions rp ON ur.role_id = rp.role_id
                              JOIN permissions p ON rp.permission_id = p.id
                              WHERE ur.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        error_log("Failed to get user permissions: " . $e->getMessage());
    }
    
    return $permissions;
}

// Get current user's roles
function getUserRoles() {
    global $pdo;
    $roles = [];
    
    try {
        $stmt = $pdo->prepare("SELECT r.name FROM user_roles ur
                              JOIN roles r ON ur.role_id = r.id
                              WHERE ur.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        error_log("Failed to get user roles: " . $e->getMessage());
    }
    
    return $roles;
}

// Get current user's information
function getCurrentUser() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Failed to get user information: " . $e->getMessage());
        return null;
    }
}

$userPermissions = getUserPermissions();
$userRoles = getUserRoles();
$currentUser = getCurrentUser();
?>