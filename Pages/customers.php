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
        case 'add_customer':
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zip_code = trim($_POST['zip_code'] ?? '');
            $country = trim($_POST['country'] ?? 'USA');
            $customer_type = $_POST['customer_type'] ?? 'individual';
            $notes = trim($_POST['notes'] ?? '');
            
            if (empty($first_name) || empty($last_name) || empty($email)) {
                $error = 'First name, last name, and email are required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email already exists for another customer.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, address, city, state, zip_code, country, customer_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $state, $zip_code, $country, $customer_type, $notes]);
                        
                        $success = 'Customer added successfully.';
                    }
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_customer':
            $customer_id = $_POST['customer_id'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zip_code = trim($_POST['zip_code'] ?? '');
            $country = trim($_POST['country'] ?? 'USA');
            $customer_type = $_POST['customer_type'] ?? 'individual';
            $status = $_POST['status'] ?? 'active';
            $notes = trim($_POST['notes'] ?? '');
            
            if (empty($customer_id) || empty($first_name) || empty($last_name) || empty($email)) {
                $error = 'Required fields cannot be empty.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if email exists for other customers
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $customer_id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email already exists for another customer.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, country = ?, customer_type = ?, status = ?, notes = ? WHERE id = ?");
                        $stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $state, $zip_code, $country, $customer_type, $status, $notes, $customer_id]);
                        
                        $success = 'Customer updated successfully.';
                    }
                } catch(PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_customer':
            $customer_id = $_POST['customer_id'] ?? '';
            
            if (empty($customer_id)) {
                $error = 'Customer ID is required.';
            } else {
                try {
                    $pdo = getDBConnection();
                    
                    // Check if customer has bookings or sales
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
                    $stmt->execute([$customer_id]);
                    $hasBookings = $stmt->fetchColumn() > 0;
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
                    $stmt->execute([$customer_id]);
                    $hasSales = $stmt->fetchColumn() > 0;
                    
                    if ($hasBookings || $hasSales) {
                        $error = 'Cannot delete customer with existing bookings or sales.';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                        $stmt->execute([$customer_id]);
                        
                        $success = 'Customer deleted successfully.';
                    }
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
$type_filter = $_GET['type'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get customers with search, filter, and pagination
function getCustomers($search = '', $status_filter = '', $type_filter = '', $sort_by = 'created_at', $sort_order = 'DESC', $offset = 0, $per_page = 10) {
    try {
        $pdo = getDBConnection();
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($type_filter)) {
            $where_conditions[] = "customer_type = ?";
            $params[] = $type_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Validate sort parameters
        $allowed_sort_fields = ['first_name', 'last_name', 'email', 'total_spent', 'total_bookings', 'created_at', 'status'];
        $sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'created_at';
        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM customers $where_clause";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_records = $stmt->fetchColumn();
        
        // Get customers
        $sql = "SELECT * FROM customers $where_clause ORDER BY $sort_by $sort_order LIMIT $per_page OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'customers' => $customers,
            'total_records' => $total_records,
            'total_pages' => ceil($total_records / $per_page)
        ];
    } catch(PDOException $e) {
        return [
            'customers' => [],
            'total_records' => 0,
            'total_pages' => 0
        ];
    }
}

$result = getCustomers($search, $status_filter, $type_filter, $sort_by, $sort_order, $offset, $per_page);
$customers = $result['customers'];
$total_records = $result['total_records'];
$total_pages = $result['total_pages'];

// Get customer details for editing
function getCustomerDetails($customer_id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}

// Get customer bookings
function getCustomerBookings($customer_id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE customer_id = ? ORDER BY check_in_date DESC");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get customer statistics
function getCustomerStats($customer_id) {
    try {
        $pdo = getDBConnection();
        
        $stats = [];
        
        // Total bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        // Total spent
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE customer_id = ? AND payment_status = 'paid'");
        $stmt->execute([$customer_id]);
        $stats['total_spent'] = $stmt->fetchColumn();
        
        // Average booking value
        $stmt = $pdo->prepare("SELECT COALESCE(AVG(total_amount), 0) FROM bookings WHERE customer_id = ? AND payment_status = 'paid'");
        $stmt->execute([$customer_id]);
        $stats['avg_booking_value'] = $stmt->fetchColumn();
        
        // First booking date
        $stmt = $pdo->prepare("SELECT MIN(check_in_date) FROM bookings WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $stats['first_booking'] = $stmt->fetchColumn();
        
        // Last booking date
        $stmt = $pdo->prepare("SELECT MAX(check_in_date) FROM bookings WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $stats['last_booking'] = $stmt->fetchColumn();
        
        // Most common room type
        $stmt = $pdo->prepare("SELECT room_type, COUNT(*) as count FROM bookings WHERE customer_id = ? GROUP BY room_type ORDER BY count DESC LIMIT 1");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['favorite_room_type'] = $result ? $result['room_type'] : 'N/A';
        
        // Booking status distribution
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM bookings WHERE customer_id = ? GROUP BY status");
        $stmt->execute([$customer_id]);
        $stats['status_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    } catch(PDOException $e) {
        return [
            'total_bookings' => 0,
            'total_spent' => 0,
            'avg_booking_value' => 0,
            'first_booking' => null,
            'last_booking' => null,
            'favorite_room_type' => 'N/A',
            'status_distribution' => []
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Boomerang Project</title>
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
        .search-box {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 2px;
        }
        .badge {
            border-radius: 8px;
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
            <a class="nav-link active" href="customers.php">
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
                <h4 class="mb-0">Customer Management</h4>
                <div class="navbar-nav ms-auto">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="fas fa-plus me-2"></i>
                        Add Customer
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
        
        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control search-box" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, email, or phone">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">All Types</option>
                            <option value="individual" <?php echo $type_filter === 'individual' ? 'selected' : ''; ?>>Individual</option>
                            <option value="business" <?php echo $type_filter === 'business' ? 'selected' : ''; ?>>Business</option>
                            <option value="vip" <?php echo $type_filter === 'vip' ? 'selected' : ''; ?>>VIP</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                            <option value="first_name" <?php echo $sort_by === 'first_name' ? 'selected' : ''; ?>>First Name</option>
                            <option value="last_name" <?php echo $sort_by === 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                            <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Email</option>
                            <option value="total_spent" <?php echo $sort_by === 'total_spent' ? 'selected' : ''; ?>>Total Spent</option>
                            <option value="total_bookings" <?php echo $sort_by === 'total_bookings' ? 'selected' : ''; ?>>Total Bookings</option>
                            <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="order" class="form-label">Order</label>
                        <select class="form-select" id="order" name="order">
                            <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Search
                        </button>
                        <a href="customers.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Customers List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    Customers (<?php echo $total_records; ?>)
                </h5>
                <div>
                    <span class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Total Spent</th>
                                <th>Bookings</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-2x mb-3"></i>
                                    <p>No customers found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                        <?php if ($customer['customer_type'] === 'vip'): ?>
                                            <span class="badge bg-warning ms-1">VIP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $customer['customer_type'] === 'vip' ? 'warning' : 
                                                ($customer['customer_type'] === 'business' ? 'info' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($customer['customer_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $customer['status'] === 'active' ? 'success' : 
                                                ($customer['status'] === 'inactive' ? 'secondary' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($customer['status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $customer['total_bookings']; ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#editCustomerModal<?php echo $customer['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="viewCustomerDetails(<?php echo $customer['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteCustomerModal<?php echo $customer['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Customer pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_customer">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="USA">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_type" class="form-label">Customer Type</label>
                                <select class="form-select" id="customer_type" name="customer_type">
                                    <option value="individual">Individual</option>
                                    <option value="business">Business</option>
                                    <option value="vip">VIP</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modals -->
    <?php foreach ($customers as $customer): ?>
    <div class="modal fade" id="editCustomerModal<?php echo $customer['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer: <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_customer">
                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_first_name_<?php echo $customer['id']; ?>" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="edit_first_name_<?php echo $customer['id']; ?>" 
                                       name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_last_name_<?php echo $customer['id']; ?>" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="edit_last_name_<?php echo $customer['id']; ?>" 
                                       name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_email_<?php echo $customer['id']; ?>" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email_<?php echo $customer['id']; ?>" 
                                       name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone_<?php echo $customer['id']; ?>" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="edit_phone_<?php echo $customer['id']; ?>" 
                                       name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address_<?php echo $customer['id']; ?>" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address_<?php echo $customer['id']; ?>" 
                                      name="address" rows="2"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_city_<?php echo $customer['id']; ?>" class="form-label">City</label>
                                <input type="text" class="form-control" id="edit_city_<?php echo $customer['id']; ?>" 
                                       name="city" value="<?php echo htmlspecialchars($customer['city']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_state_<?php echo $customer['id']; ?>" class="form-label">State</label>
                                <input type="text" class="form-control" id="edit_state_<?php echo $customer['id']; ?>" 
                                       name="state" value="<?php echo htmlspecialchars($customer['state']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_zip_code_<?php echo $customer['id']; ?>" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="edit_zip_code_<?php echo $customer['id']; ?>" 
                                       name="zip_code" value="<?php echo htmlspecialchars($customer['zip_code']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_country_<?php echo $customer['id']; ?>" class="form-label">Country</label>
                                <input type="text" class="form-control" id="edit_country_<?php echo $customer['id']; ?>" 
                                       name="country" value="<?php echo htmlspecialchars($customer['country']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_customer_type_<?php echo $customer['id']; ?>" class="form-label">Customer Type</label>
                                <select class="form-select" id="edit_customer_type_<?php echo $customer['id']; ?>" name="customer_type">
                                    <option value="individual" <?php echo $customer['customer_type'] === 'individual' ? 'selected' : ''; ?>>Individual</option>
                                    <option value="business" <?php echo $customer['customer_type'] === 'business' ? 'selected' : ''; ?>>Business</option>
                                    <option value="vip" <?php echo $customer['customer_type'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_status_<?php echo $customer['id']; ?>" class="form-label">Status</label>
                                <select class="form-select" id="edit_status_<?php echo $customer['id']; ?>" name="status">
                                    <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="blocked" <?php echo $customer['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes_<?php echo $customer['id']; ?>" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit_notes_<?php echo $customer['id']; ?>" 
                                      name="notes" rows="3"><?php echo htmlspecialchars($customer['notes']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Delete Customer Modals -->
    <?php foreach ($customers as $customer): ?>
    <div class="modal fade" id="deleteCustomerModal<?php echo $customer['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the customer <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Customer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>
                        Customer Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCustomerDetails(customerId) {
            // Show loading state
            document.getElementById('customerDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading customer details...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
            modal.show();
            
            // Fetch customer details via AJAX
            fetch(`customer_details.php?customer_id=${customerId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('customerDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('customerDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading customer details. Please try again.
                        </div>
                    `;
                });
        }
    </script>
</body>
</html> 