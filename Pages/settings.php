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
        case 'update_settings':
            $site_name = trim($_POST['site_name'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $session_timeout = (int)($_POST['session_timeout'] ?? 30);
            $max_login_attempts = (int)($_POST['max_login_attempts'] ?? 5);
            
            if (empty($site_name) || empty($admin_email)) {
                $error = 'Site name and admin email are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Create settings table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        setting_key VARCHAR(100) UNIQUE NOT NULL,
                        setting_value TEXT,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    
                    // Update or insert settings
                    $settings = [
                        'site_name' => $site_name,
                        'admin_email' => $admin_email,
                        'session_timeout' => $session_timeout,
                        'max_login_attempts' => $max_login_attempts
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                                             ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $success = 'System settings updated successfully.';
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'backup_database':
            try {
                $pdo = getDBConnection();
                
                // Get all tables
                $tables = [];
                $stmt = $pdo->query("SHOW TABLES");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                
                $backup = '';
                $backup .= "-- Boomerang Project Database Backup\n";
                $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
                
                foreach ($tables as $table) {
                    // Get table structure
                    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                    $row = $stmt->fetch(PDO::FETCH_NUM);
                    $backup .= $row[1] . ";\n\n";
                    
                    // Get table data
                    $stmt = $pdo->query("SELECT * FROM `$table`");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $backup .= "INSERT INTO `$table` VALUES (";
                        $values = [];
                        foreach ($row as $value) {
                            $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                        }
                        $backup .= implode(', ', $values) . ");\n";
                    }
                    $backup .= "\n";
                }
                
                // Save backup file
                $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = '../Assets/backups/' . $filename;
                
                // Create backups directory if it doesn't exist
                if (!is_dir('../Assets/backups')) {
                    mkdir('../Assets/backups', 0755, true);
                }
                
                file_put_contents($backup_path, $backup);
                
                $success = 'Database backup created successfully: ' . $filename;
            } catch(PDOException $e) {
                $error = 'Backup error: ' . $e->getMessage();
            }
            break;
    }
}

// Get system settings
function getSystemSettings() {
    try {
        $pdo = getDBConnection();
        
        // Create settings table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch(PDOException $e) {
        return [];
    }
}

$settings = getSystemSettings();

// Get system statistics
function getSystemStats() {
    try {
        $pdo = getDBConnection();
        
        $stats = [];
        
        // Database size
        $stmt = $pdo->query("SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
            FROM information_schema.tables 
            WHERE table_schema = '" . DB_NAME . "'");
        $stats['db_size'] = $stmt->fetchColumn();
        
        // Table count
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $stats['table_count'] = $stmt->fetchColumn();
        
        // Admin count
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        $stats['admin_count'] = $stmt->fetchColumn();
        
        return $stats;
    } catch(PDOException $e) {
        return ['db_size' => 0, 'table_count' => 0, 'admin_count' => 0];
    }
}

$systemStats = getSystemStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Boomerang Project</title>
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
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .settings-header {
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
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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
                        <a class="nav-link" href="manage_admins.php">
                            <i class="fas fa-users me-2"></i>
                            Manage Admins
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>
                            Profile
                        </a>
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog me-2"></i>
                            Settings
                        </a>
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
                <nav class="navbar navbar-expand-lg">
                    <div class="container-fluid">
                        <h4 class="mb-0">System Settings</h4>
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
                    
                    <!-- System Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-2x mb-2"></i>
                                    <h3><?php echo $systemStats['db_size']; ?> MB</h3>
                                    <p class="mb-0">Database Size</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-table fa-2x mb-2"></i>
                                    <h3><?php echo $systemStats['table_count']; ?></h3>
                                    <p class="mb-0">Tables</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h3><?php echo $systemStats['admin_count']; ?></h3>
                                    <p class="mb-0">Admin Accounts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- General Settings -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header settings-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-cog me-2"></i>
                                        General Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_settings">
                                        
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Boomerang Project'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="admin_email" class="form-label">Admin Email</label>
                                            <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                                   value="<?php echo htmlspecialchars($settings['admin_email'] ?? 'admin@boomerang.com'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                                   value="<?php echo $settings['session_timeout'] ?? 30; ?>" min="5" max="1440" required>
                                            <small class="text-muted">5-1440 minutes (24 hours)</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                                   value="<?php echo $settings['max_login_attempts'] ?? 5; ?>" min="3" max="10" required>
                                            <small class="text-muted">3-10 attempts</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Save Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Database Management -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header settings-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-database me-2"></i>
                                        Database Management
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <h6>Database Information</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                                            <li><strong>Database:</strong> <?php echo DB_NAME; ?></li>
                                            <li><strong>Size:</strong> <?php echo $systemStats['db_size']; ?> MB</li>
                                            <li><strong>Tables:</strong> <?php echo $systemStats['table_count']; ?></li>
                                        </ul>
                                    </div>
                                    
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to create a database backup?')">
                                        <input type="hidden" name="action" value="backup_database">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-download me-2"></i>
                                            Create Backup
                                        </button>
                                    </form>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Backup files are saved in Assets/backups/ directory
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Information -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        System Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Server Information</h6>
                                            <ul class="list-unstyled">
                                                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                                                <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
                                                <li><strong>Operating System:</strong> <?php echo PHP_OS; ?></li>
                                                <li><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Application Information</h6>
                                            <ul class="list-unstyled">
                                                <li><strong>Application Name:</strong> Boomerang Project</li>
                                                <li><strong>Current User:</strong> <?php echo htmlspecialchars($currentAdmin['full_name']); ?></li>
                                                <li><strong>User Role:</strong> <?php echo ucfirst(str_replace('_', ' ', $currentAdmin['role'])); ?></li>
                                                <li><strong>Session Started:</strong> <?php echo date('M j, Y g:i A', $_SESSION['login_time']); ?></li>
                                            </ul>
                                        </div>
                                    </div>
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