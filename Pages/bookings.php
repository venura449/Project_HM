<?php
require_once '../Includes/auth.php';
requireAuth();

$currentAdmin = getCurrentAdmin();
$error = '';
$success = '';

//Self submission handler using switch case
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_booking':
            $customer_id = $_POST['customer_id'] ?? '';
            $room_type = trim($_POST['room_type'] ?? '');
            $check_in_date = $_POST['check_in_date'] ?? '';
            $check_out_date = $_POST['check_out_date'] ?? '';
            $num_guests = (int)($_POST['num_guests'] ?? 1);
            $num_rooms = (int)($_POST['num_rooms'] ?? 1);
            $total_amount = (float)($_POST['total_amount'] ?? 0);
            $payment_method = $_POST['payment_method'] ?? '';
            $special_requests = trim($_POST['special_requests'] ?? '');
            
            if (empty($customer_id) || empty($room_type) || empty($check_in_date) || empty($check_out_date)) {
                $error = 'Customer, room type, check-in date, and check-out date are required.';
            } elseif (strtotime($check_out_date) <= strtotime($check_in_date)) {
                $error = 'Check-out date must be after check-in date.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Generate booking number
                    $booking_number = 'BK' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    
                    $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, booking_number, room_type, check_in_date, check_out_date, num_guests, num_rooms, total_amount, payment_method, special_requests, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$customer_id, $booking_number, $room_type, $check_in_date, $check_out_date, $num_guests, $num_rooms, $total_amount, $payment_method, $special_requests, $currentAdmin['id']]);
                    
                    $success = 'Hotel booking added successfully.';
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_booking_status':
            $booking_id = $_POST['booking_id'] ?? '';
            $status = $_POST['status'] ?? '';
            $payment_status = $_POST['payment_status'] ?? '';
            $room_number = trim($_POST['room_number'] ?? '');
            
            if (empty($booking_id) || empty($status)) {
                $error = 'Booking ID and status are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Get booking details before update
                    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                    $stmt->execute([$booking_id]);
                    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Update booking status and room number
                    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, payment_status = ?, room_number = ? WHERE id = ?");
                    $stmt->execute([$status, $payment_status, $room_number, $booking_id]);
                    
                    $success = 'Hotel booking status updated successfully.';
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort'] ?? 'check_in_date';
$sort_order = $_GET['order'] ?? 'DESC';

// Get bookings with search and filter
function getBookings($search = '', $status_filter = '', $date_from = '', $date_to = '', $sort_by = 'check_in_date', $sort_order = 'DESC') {
    try {
        $pdo = getDBConnection();
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(b.booking_number LIKE ? OR b.room_type LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "b.status = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = "b.check_in_date >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "b.check_out_date <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $allowed_sort = ['check_in_date', 'check_out_date', 'total_amount', 'status'];
        $sort_by = in_array($sort_by, $allowed_sort) ? $sort_by : 'check_in_date';
        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT b.*, c.first_name, c.last_name, c.email, c.phone 
                FROM bookings b 
                LEFT JOIN customers c ON b.customer_id = c.id 
                $where_clause 
                ORDER BY b.$sort_by $sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

$bookings = getBookings($search, $status_filter, $date_from, $date_to, $sort_by, $sort_order);

// Get customers for dropdown
function getCustomers() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM customers WHERE status = 'active' ORDER BY first_name, last_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

$customers = getCustomers();

// Get booking statistics
function getBookingStats() {
    try {
        $pdo = getDBConnection();
        
        $stats = [];
        
        // Total bookings
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        // Today's check-ins
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE check_in_date = CURDATE()");
        $stats['today_checkins'] = $stmt->fetchColumn();
        
        // Today's check-outs
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE check_out_date = CURDATE()");
        $stats['today_checkouts'] = $stmt->fetchColumn();
        
        // Pending bookings
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
        $stats['pending_bookings'] = $stmt->fetchColumn();
        
        // Confirmed bookings
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
        $stats['confirmed_bookings'] = $stmt->fetchColumn();
        
        // Checked-in bookings
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'");
        $stats['checked_in_bookings'] = $stmt->fetchColumn();
        
        // Total revenue
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE payment_status = 'paid'");
        $stats['total_revenue'] = $stmt->fetchColumn();
        
        // Average booking value
        $stmt = $pdo->query("SELECT COALESCE(AVG(total_amount), 0) FROM bookings WHERE payment_status = 'paid'");
        $stats['avg_booking_value'] = $stmt->fetchColumn();
        
        // This month's bookings
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE MONTH(check_in_date) = MONTH(CURDATE()) AND YEAR(check_in_date) = YEAR(CURDATE())");
        $stats['month_bookings'] = $stmt->fetchColumn();
        
        // This month's revenue
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(check_in_date) = MONTH(CURDATE()) AND YEAR(check_in_date) = YEAR(CURDATE()) AND payment_status = 'paid'");
        $stats['month_revenue'] = $stmt->fetchColumn();
        
        // Average stay duration
        $stmt = $pdo->query("SELECT COALESCE(AVG(DATEDIFF(check_out_date, check_in_date)), 0) FROM bookings WHERE status IN ('checked_out', 'confirmed')");
        $stats['avg_stay_duration'] = $stmt->fetchColumn();
        
        // Total guests
        $stmt = $pdo->query("SELECT COALESCE(SUM(num_guests), 0) FROM bookings WHERE status IN ('checked_in', 'confirmed')");
        $stats['total_guests'] = $stmt->fetchColumn();
        
        return $stats;
    } catch(PDOException $e) {
        return [
            'total_bookings' => 0,
            'today_checkins' => 0,
            'today_checkouts' => 0,
            'pending_bookings' => 0,
            'confirmed_bookings' => 0,
            'checked_in_bookings' => 0,
            'total_revenue' => 0,
            'avg_booking_value' => 0,
            'month_bookings' => 0,
            'month_revenue' => 0,
            'avg_stay_duration' => 0,
            'total_guests' => 0
        ];
    }
}

$stats = getBookingStats();

// Get chart data for analytics
function getChartData() {
    try {
        $pdo = getDBConnection();
        
      
        $sql = "SELECT DATE_FORMAT(check_in_date, '%Y-%m') as month, 
                       COUNT(*) as count, 
                       SUM(total_amount) as revenue 
                FROM bookings 
                WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                GROUP BY DATE_FORMAT(check_in_date, '%Y-%m') 
                ORDER BY month";
        $stmt = $pdo->query($sql);
        $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        $sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as revenue 
                FROM bookings 
                GROUP BY status";
        $stmt = $pdo->query($sql);
        $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        //r type
        $sql = "SELECT room_type, COUNT(*) as count, SUM(total_amount) as revenue 
                FROM bookings 
                GROUP BY room_type 
                ORDER BY revenue DESC 
                LIMIT 5";
        $stmt = $pdo->query($sql);
        $top_room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pay
        $sql = "SELECT payment_method, COUNT(*) as count, SUM(total_amount) as revenue 
                FROM bookings 
                WHERE payment_method IS NOT NULL 
                GROUP BY payment_method";
        $stmt = $pdo->query($sql);
        $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Daily oc
        $sql = "SELECT DATE(check_in_date) as date, COUNT(*) as count 
                FROM bookings 
                WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                GROUP BY DATE(check_in_date) 
                ORDER BY date";
        $stmt = $pdo->query($sql);
        $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Guest count
        $sql = "SELECT DATE_FORMAT(check_in_date, '%Y-%m') as month, 
                       SUM(num_guests) as total_guests 
                FROM bookings 
                WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                GROUP BY DATE_FORMAT(check_in_date, '%Y-%m') 
                ORDER BY month";
        $stmt = $pdo->query($sql);
        $guest_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'monthly_data' => $monthly_data,
            'status_data' => $status_data,
            'top_room_types' => $top_room_types,
            'payment_methods' => $payment_methods,
            'daily_data' => $daily_data,
            'guest_trends' => $guest_trends
        ];
    } catch(PDOException $e) {
        return [
            'monthly_data' => [],
            'status_data' => [],
            'top_room_types' => [],
            'payment_methods' => [],
            'daily_data' => [],
            'guest_trends' => []
        ];
    }
}

$chartData = getChartData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Boomerang Project</title>
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
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 250px;
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
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            color: #667eea;
        }
        .nav-tabs .nav-link.active {
            background-color: #667eea;
            color: white;
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
            <a class="nav-link active" href="bookings.php">
                <i class="fas fa-calendar-check me-2"></i>
                Bookings
            </a>
            <?php if (isSuperAdmin()): ?>
            <a class="nav-link" href="manage_admins.php">
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
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <h4 class="mb-0">Hotel Booking Management & Analytics</h4>
                <div class="navbar-nav ms-auto">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                        <i class="fas fa-plus me-2"></i>
                        Add Booking
                    </button>
                    <div class="nav-item dropdown ms-2">
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
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p class="mb-0">Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card success">
                    <div class="card-body text-center">
                        <i class="fas fa-sign-in-alt fa-2x mb-2"></i>
                        <h3><?php echo $stats['today_checkins']; ?></h3>
                        <p class="mb-0">Today's Check-ins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card warning">
                    <div class="card-body text-center">
                        <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                        <h3><?php echo $stats['pending_bookings']; ?></h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card info">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo $stats['confirmed_bookings']; ?></h3>
                        <p class="mb-0">Confirmed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-bed fa-2x mb-2"></i>
                        <h3><?php echo $stats['checked_in_bookings']; ?></h3>
                        <p class="mb-0">Checked In</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h3><?php echo $stats['total_guests']; ?></h3>
                        <p class="mb-0">Total Guests</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-day fa-2x mb-2"></i>
                        <h3><?php echo round($stats['avg_stay_duration'], 1); ?></h3>
                        <p class="mb-0">Avg. Stay (Days)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Tabs -->
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="analyticsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>Booking Trends
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab">
                            <i class="fas fa-bed me-2"></i>Room Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab">
                            <i class="fas fa-tasks me-2"></i>Status Distribution
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="occupancy-tab" data-bs-toggle="tab" data-bs-target="#occupancy" type="button" role="tab">
                            <i class="fas fa-calendar-day me-2"></i>Occupancy Trends
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="analyticsTabContent">
                    <!-- Booking Trends Tab -->
                    <div class="tab-pane fade show active" id="trends" role="tabpanel">
                        <div class="chart-container">
                            <canvas id="bookingTrendsChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Room Analytics Tab -->
                    <div class="tab-pane fade" id="rooms" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="topServicesChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Distribution Tab -->
                    <div class="tab-pane fade" id="status" role="tabpanel">
                        <div class="chart-container">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Occupancy Trends Tab -->
                    <div class="tab-pane fade" id="occupancy" role="tabpanel">
                        <div class="chart-container">
                            <canvas id="occupancyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Booking #, service, customer">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="check_in_date" <?php echo $sort_by === 'check_in_date' ? 'selected' : ''; ?>>Check-in Date</option>
                            <option value="check_out_date" <?php echo $sort_by === 'check_out_date' ? 'selected' : ''; ?>>Check-out Date</option>
                            <option value="total_amount" <?php echo $sort_by === 'total_amount' ? 'selected' : ''; ?>>Amount</option>
                            <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label for="order" class="form-label">Order</label>
                        <select class="form-select" id="order" name="order">
                            <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>↓</option>
                            <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>↑</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Search
                        </button>
                        <a href="bookings.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Clear
                        </a>
                        <a href="generate_booking_report.php?<?php echo http_build_query($_GET); ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i>
                            Generate Report
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bookings List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-check me-2"></i>
                    Bookings (<?php echo count($bookings); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Room Type</th>
                                <th>Check-in/Check-out</th>
                                <th>Guests</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-calendar fa-2x mb-3"></i>
                                    <p>No hotel bookings found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                    <td>
                                        <div>
                                            <strong>In: <?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></strong>
                                            <br>
                                            <small class="text-muted">Out: <?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $booking['num_guests']; ?> Guest<?php echo $booking['num_guests'] > 1 ? 's' : ''; ?></span>
                                        <?php if ($booking['num_rooms'] > 1): ?>
                                        <br><small class="text-muted"><?php echo $booking['num_rooms']; ?> Room<?php echo $booking['num_rooms'] > 1 ? 's' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'checked_out' ? 'success' : 
                                                ($booking['status'] === 'checked_in' ? 'primary' : 
                                                ($booking['status'] === 'confirmed' ? 'info' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 'danger'))); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $booking['payment_status'] === 'paid' ? 'success' : 
                                                ($booking['payment_status'] === 'partial' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modals -->
    <?php foreach ($bookings as $booking): ?>
    <div class="modal fade" id="updateStatusModal<?php echo $booking['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_booking_status">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="status_<?php echo $booking['id']; ?>" class="form-label">Booking Status</label>
                            <select class="form-select" id="status_<?php echo $booking['id']; ?>" name="status">
                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="checked_in" <?php echo $booking['status'] === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                <option value="checked_out" <?php echo $booking['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="no_show" <?php echo $booking['status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room_number_<?php echo $booking['id']; ?>" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number_<?php echo $booking['id']; ?>" name="room_number" 
                                   value="<?php echo htmlspecialchars($booking['room_number'] ?? ''); ?>" 
                                   placeholder="e.g., 101, 2A, etc.">
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_status_<?php echo $booking['id']; ?>" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status_<?php echo $booking['id']; ?>" name="payment_status">
                                <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="partial" <?php echo $booking['payment_status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> When a booking is marked as "Checked Out" and "Paid", a sale record will be automatically created.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Booking Trends Chart
        const trendsCtx = document.getElementById('bookingTrendsChart').getContext('2d');
        const trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chartData['monthly_data'], 'month')); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode(array_column($chartData['monthly_data'], 'revenue')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Bookings Count',
                    data: <?php echo json_encode(array_column($chartData['monthly_data'], 'count')); ?>,
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
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
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Bookings Count'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Top Services Chart
        const servicesCtx = document.getElementById('topServicesChart').getContext('2d');
        const servicesChart = new Chart(servicesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($chartData['top_room_types'], 'room_type')); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode(array_column($chartData['top_room_types'], 'revenue')); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Payment Methods Chart
        const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($chartData['payment_methods'], 'payment_method')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($chartData['payment_methods'], 'revenue')); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe'
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

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($chartData['status_data'], 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($chartData['status_data'], 'count')); ?>,
                    backgroundColor: [
                        '#ffc107',
                        '#007bff',
                        '#28a745',
                        '#dc3545',
                        '#6c757d'
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

        // Daily Activity Chart
        const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($chartData['daily_data'], 'date')); ?>,
                datasets: [{
                    label: 'Daily Bookings',
                    data: <?php echo json_encode(array_column($chartData['daily_data'], 'count')); ?>,
                    backgroundColor: '#43e97b',
                    borderColor: '#38f9d7',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 