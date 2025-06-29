<?php
session_start();
require_once 'config.php';

//this func checks if the user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

//this func used for checking if the user is a super admin
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

// Authenticate admin login
function authenticateAdmin($username, $password) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, role, is_active FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && $admin['is_active'] && password_verify($password, $admin['password'])) {
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
            
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_full_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid credentials or account is inactive'];
        }
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Logout admin
function logoutAdmin() {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

// Require authentication for protected pages
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect mechanism for super admin
function requireSuperAdmin() {
    requireAuth();
    if (!isSuperAdmin()) {
        header('Location: ../dashboard.php?error=insufficient_privileges');
        exit();
    }
}

// Getter for current admin info
function getCurrentAdmin() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'email' => $_SESSION['admin_email'],
        'full_name' => $_SESSION['admin_full_name'],
        'role' => $_SESSION['admin_role']
    ];
}
?> 