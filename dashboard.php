<?php
require_once 'Includes/auth.php';

// Require authentication
requireAuth();

$currentAdmin = getCurrentAdmin();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Get comprehensive admin statistics
function getAdminStats() {
    try {
        $pdo = getDBConnection();
        
        // Admin stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        $totalAdmins = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE is_active = 1");
        $activeAdmins = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'");
        $superAdmins = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recentLogins = $stmt->fetchColumn();
        
        // Customer stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
        $totalCustomers = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'");
        $activeCustomers = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE customer_type = 'vip'");
        $vipCustomers = $stmt->fetchColumn();
        
        // Booking stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
        $totalBookings = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()");
        $todayBookings = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
        $pendingBookings = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
        $completedBookings = $stmt->fetchColumn();
        
        return [
            'total_admins' => $totalAdmins,
            'active_admins' => $activeAdmins,
            'super_admins' => $superAdmins,
            'recent_logins' => $recentLogins,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'vip_customers' => $vipCustomers,
            'total_bookings' => $totalBookings,
            'today_bookings' => $todayBookings,
            'pending_bookings' => $pendingBookings,
            'completed_bookings' => $completedBookings
        ];
    } catch(PDOException $e) {
        return [
            'total_admins' => 0,
            'active_admins' => 0,
            'super_admins' => 0,
            'recent_logins' => 0,
            'total_customers' => 0,
            'active_customers' => 0,
            'vip_customers' => 0,
            'total_bookings' => 0,
            'today_bookings' => 0,
            'pending_bookings' => 0,
            'completed_bookings' => 0
        ];
    }
}

// Get chart data for analytics
function getChartData() {
    try {
        $pdo = getDBConnection();
        
        // Monthly bookings (last 6 months)
        $sql = "SELECT DATE_FORMAT(check_in_date, '%Y-%m') as month, COUNT(*) as count FROM bookings WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(check_in_date, '%Y-%m') ORDER BY month";
        $stmt = $pdo->query($sql);
        $monthly_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Customer types distribution
        $sql = "SELECT customer_type, COUNT(*) as count FROM customers GROUP BY customer_type";
        $stmt = $pdo->query($sql);
        $customer_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Booking status distribution
        $sql = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
        $stmt = $pdo->query($sql);
        $booking_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Customer growth (last 6 months)
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM customers WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month";
        $stmt = $pdo->query($sql);
        $customer_growth = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'monthly_bookings' => $monthly_bookings,
            'customer_types' => $customer_types,
            'booking_status' => $booking_status,
            'customer_growth' => $customer_growth
        ];
    } catch(PDOException $e) {
        return [
            'monthly_bookings' => [],
            'customer_types' => [],
            'booking_status' => [],
            'customer_growth' => []
        ];
    }
}

?>

<?php
$stats = getAdminStats();
$chartData = getChartData();
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Admin Dashboard - Boomerang Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stat-card.dark {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left:0;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .chart-container {
            position: relative;
            height: 300px;
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
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
            <a class="nav-link" href="Pages/customers.php">
                <i class="fas fa-users me-2"></i>
                Customers
            </a>
            <a class="nav-link" href="Pages/bookings.php">
                <i class="fas fa-calendar-check me-2"></i>
                Bookings
            </a>
            <?php if (isSuperAdmin()): ?>
            <a class="nav-link" href="Pages/manage_admins.php">
                <i class="fas fa-user-cog me-2"></i>
                Manage Admins
            </a>
            <?php endif; ?>
            <a class="nav-link" href="Pages/profile.php">
                <i class="fas fa-user me-2"></i>
                Profile
            </a>
            <?php if (isSuperAdmin()): ?>
            <a class="nav-link" href="Pages/settings.php">
                <i class="fas fa-cog me-2"></i>
                Settings
            </a>
            <?php endif; ?>
            <hr class="my-3">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>
                Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Dashboard</h4>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($currentAdmin['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="Pages/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
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
        
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-home me-2"></i>
                            Welcome back, <?php echo htmlspecialchars($currentAdmin['full_name']); ?>!
                        </h5>
                        <p class="card-text text-muted">
                            You are logged in as a <strong><?php echo ucfirst(str_replace('_', ' ', $currentAdmin['role'])); ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-area me-2"></i>
                            Bookings Trend (Last 6 Months)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Customer Types
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="customerChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Statistics -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Booking Status Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="bookingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            Customer Growth
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="customerGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="Pages/customers.php" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>
                                Manage Customers
                            </a>
                            <a href="Pages/bookings.php" class="btn btn-outline-success">
                                <i class="fas fa-calendar-check me-2"></i>
                                View Hotel Bookings & Analytics
                            </a>
                            <a href="Pages/profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user me-2"></i>
                                Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                            </li>
                            <li class="mb-2">
                                <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                            </li>
                            <li class="mb-2">
                                <strong>Database:</strong> MySQL
                            </li>
                            <li class="mb-2">
                                <strong>Last Login:</strong> 
                                <?php echo isset($_SESSION['login_time']) ? date('M j, Y g:i A', $_SESSION['login_time']) : 'Unknown'; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue & Bookings Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chartData['monthly_bookings'], 'month')); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($chartData['monthly_bookings'], 'count')); ?>,
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Bookings Count'
                        }
                    }
                }
            }
        });

        // Customer Types Chart
        const customerCtx = document.getElementById('customerChart').getContext('2d');
        const customerChart = new Chart(customerCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($chartData['customer_types'], 'customer_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($chartData['customer_types'], 'count')); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Booking Status Chart
        const bookingCtx = document.getElementById('bookingChart').getContext('2d');
        const bookingChart = new Chart(bookingCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($chartData['booking_status'], 'status')); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($chartData['booking_status'], 'count')); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#4facfe',
                        '#f093fb',
                        '#f5576c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Customer Growth Chart
        const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
        const customerGrowthChart = new Chart(customerGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chartData['customer_growth'], 'month')); ?>,
                datasets: [{
                    label: 'Customer Growth',
                    data: <?php echo json_encode(array_column($chartData['customer_growth'], 'count')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Customer Growth'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 