<?php
require_once '../Includes/auth.php';

// Require super admin privileges
requireSuperAdmin();

$currentAdmin = getCurrentAdmin();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_admin':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'admin';
            
            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                $error = 'All fields are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if username or email already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Username or email already exists.';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$username, $email, $hashedPassword, $full_name, $role]);
                        
                        $success = 'Admin account created successfully.';
                    }
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_admin':
            $admin_id = $_POST['admin_id'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'admin';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($admin_id) || empty($full_name) || empty($email)) {
                $error = 'Required fields cannot be empty.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if email exists for other admins
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $admin_id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email already exists for another admin.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $role, $is_active, $admin_id]);
                        
                        $success = 'Admin account updated successfully.';
                    }
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_admin':
            $admin_id = $_POST['admin_id'] ?? '';
            
            if ($admin_id == $currentAdmin['id']) {
                $error = 'You cannot delete your own account.';
            } else {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    
                    $success = 'Admin account deleted successfully.';
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get all admins
function getAllAdmins() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM admins ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

$admins = getAllAdmins();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Boomerang Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
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
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-sm {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
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
            <a class="nav-link active" href="manage_admins.php">
                <i class="fas fa-user-cog me-2"></i>
                Manage Admins
            </a>
            <?php endif; ?>
            <a class="nav-link" href="profile.php">
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
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg w-100 mb-4" style="left:0;right:0;position:relative;margin-left:0;">
                    <div class="container-fluid">
                        <h4 class="mb-0">Manage Admins</h4>
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
                
                <div class="main-content">
                    <!-- Alerts -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add New Admin -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-plus me-2"></i>
                                        Add New Admin
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="add_admin">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="role" class="form-label">Role</label>
                                                <select class="form-select" id="role" name="role">
                                                    <option value="admin">Admin</option>
                                                    <option value="super_admin">Super Admin</option>
                                                </select>
                                            </div>
                                            <div class="col-md-9 mb-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>
                                                    Add Admin
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admins List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>
                                        Admin Accounts
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Username</th>
                                                    <th>Full Name</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th>Last Login</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                                        <?php if ($admin['id'] == $currentAdmin['id']): ?>
                                                            <span class="badge bg-primary ms-1">You</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'secondary'; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $admin['is_active'] ? 'success' : 'danger'; ?>">
                                                            <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $admin['last_login'] ? date('M j, Y g:i A', strtotime($admin['last_login'])) : 'Never'; ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    data-bs-toggle="modal" data-bs-target="#editModal<?php echo $admin['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($admin['id'] != $currentAdmin['id']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $admin['id']; ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modals -->
    <?php foreach ($admins as $admin): ?>
    <div class="modal fade" id="editModal<?php echo $admin['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin: <?php echo htmlspecialchars($admin['username']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_admin">
                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="edit_full_name_<?php echo $admin['id']; ?>" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_full_name_<?php echo $admin['id']; ?>" 
                                   name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email_<?php echo $admin['id']; ?>" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email_<?php echo $admin['id']; ?>" 
                                   name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role_<?php echo $admin['id']; ?>" class="form-label">Role</label>
                            <select class="form-select" id="edit_role_<?php echo $admin['id']; ?>" name="role">
                                <option value="admin" <?php echo $admin['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="super_admin" <?php echo $admin['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active_<?php echo $admin['id']; ?>" 
                                       name="is_active" <?php echo $admin['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="edit_is_active_<?php echo $admin['id']; ?>">
                                    Active Account
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Delete Modals -->
    <?php foreach ($admins as $admin): ?>
    <?php if ($admin['id'] != $currentAdmin['id']): ?>
    <div class="modal fade" id="deleteModal<?php echo $admin['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the admin account for <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete_admin">
                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 