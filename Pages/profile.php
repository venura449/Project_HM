<?php
require_once '../Includes/auth.php';

// Require authentication
requireAuth();

$currentAdmin = getCurrentAdmin();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($full_name) || empty($email)) {
                $error = 'Full name and email are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if email exists for other admins
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $currentAdmin['id']]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email already exists for another admin.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $currentAdmin['id']]);
                        
                        // Update session
                        $_SESSION['admin_full_name'] = $full_name;
                        $_SESSION['admin_email'] = $email;
                        
                        $success = 'Profile updated successfully.';
                        $currentAdmin = getCurrentAdmin(); // Refresh current admin data
                    }
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and confirm password do not match.';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
                    $stmt->execute([$currentAdmin['id']]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$admin || !password_verify($current_password, $admin['password'])) {
                        $error = 'Current password is incorrect.';
                    } else {
                        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $currentAdmin['id']]);
                        
                        $success = 'Password changed successfully.';
                    }
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get admin details
function getAdminDetails($admin_id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}

$adminDetails = getAdminDetails($currentAdmin['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Boomerang Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            height: 100vh;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            padding: 20px;
            margin-left: 250px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 0;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn {
            border-radius: 10px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .navbar {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <div class="text-center mb-4">
            <i class="fas fa-user-shield fa-2x mb-2"></i>
            <h5>Admin Panel</h5>
            <small>Boomerang Project</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="../dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
            <a class="nav-link" href="customers.php">
                <i class="fas fa-users me-2"></i>
                Customers
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check me-2"></i>
                Bookings
            </a>
            <?php if (isSuperAdmin()): ?>
            <a class="nav-link" href="manage_admins.php">
                <i class="fas fa-user-cog me-2"></i>
                Manage Admins
            </a>
            <?php endif; ?>
            <a class="nav-link active" href="profile.php">
                <i class="fas fa-user me-2"></i>
                Profile
            </a>
            <?php if (isSuperAdmin()): ?>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog me-2"></i>
                Settings
            </a>
            <?php endif; ?>
            <hr class="my-3">
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>
                Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg w-100 mb-4" style="left:0;right:0;position:relative;margin-left:0;">
            <div class="container-fluid">
                <h4 class="mb-0">Profile</h4>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($currentAdmin['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid">
            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header profile-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>
                                Profile Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo htmlspecialchars($currentAdmin['username']); ?>" readonly>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($currentAdmin['full_name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($currentAdmin['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" 
                                           value="<?php echo ucfirst(str_replace('_', ' ', $currentAdmin['role'])); ?>" readonly>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header profile-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-lock me-2"></i>
                                Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Details -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Account Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Account ID:</strong></td>
                                            <td><?php echo $adminDetails['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $adminDetails['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $adminDetails['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($adminDetails['created_at'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Last Login:</strong></td>
                                            <td>
                                                <?php echo $adminDetails['last_login'] ? date('M j, Y g:i A', strtotime($adminDetails['last_login'])) : 'Never'; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Current Session:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', $_SESSION['login_time']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Session Duration:</strong></td>
                                            <td>
                                                <?php 
                                                $duration = time() - $_SESSION['login_time'];
                                                $hours = floor($duration / 3600);
                                                $minutes = floor(($duration % 3600) / 60);
                                                echo $hours . 'h ' . $minutes . 'm';
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 