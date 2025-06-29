<?php
require_once '../Includes/auth.php';

// Require authentication
requireAuth();

$customer_id = $_GET['customer_id'] ?? '';

if (empty($customer_id)) {
    echo '<div class="alert alert-danger">Customer ID is required.</div>';
    exit;
}

// Get customer details
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

$customer = getCustomerDetails($customer_id);
$bookings = getCustomerBookings($customer_id);
$stats = getCustomerStats($customer_id);

if (!$customer) {
    echo '<div class="alert alert-danger">Customer not found.</div>';
    exit;
}
?>

<!-- Customer Information -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Personal Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone'] ?: 'Not provided'); ?></p>
                        <p><strong>Customer Type:</strong> 
                            <span class="badge bg-<?php 
                                echo $customer['customer_type'] === 'vip' ? 'warning' : 
                                    ($customer['customer_type'] === 'business' ? 'info' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst($customer['customer_type']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $customer['status'] === 'active' ? 'success' : 
                                    ($customer['status'] === 'inactive' ? 'secondary' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($customer['status']); ?>
                            </span>
                        </p>
                        <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($customer['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo date('M j, Y', strtotime($customer['updated_at'])); ?></p>
                    </div>
                </div>
                
                <?php if ($customer['address'] || $customer['city'] || $customer['state']): ?>
                <hr>
                <h6>Address Information</h6>
                <p>
                    <?php if ($customer['address']): ?>
                        <?php echo htmlspecialchars($customer['address']); ?><br>
                    <?php endif; ?>
                    <?php if ($customer['city'] || $customer['state'] || $customer['zip_code']): ?>
                        <?php echo htmlspecialchars(trim($customer['city'] . ', ' . $customer['state'] . ' ' . $customer['zip_code'])); ?><br>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($customer['country']); ?>
                </p>
                <?php endif; ?>
                
                <?php if ($customer['notes']): ?>
                <hr>
                <h6>Notes</h6>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($customer['notes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <h4 class="text-primary mb-1"><?php echo $stats['total_bookings']; ?></h4>
                            <small class="text-muted">Total Bookings</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <h4 class="text-success mb-1">$<?php echo number_format($stats['total_spent'], 2); ?></h4>
                            <small class="text-muted">Total Spent</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <h4 class="text-info mb-1">$<?php echo number_format($stats['avg_booking_value'], 2); ?></h4>
                            <small class="text-muted">Avg. Booking</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <h4 class="text-warning mb-1"><?php echo $stats['favorite_room_type']; ?></h4>
                            <small class="text-muted">Favorite Room</small>
                        </div>
                    </div>
                </div>
                
                <?php if ($stats['first_booking']): ?>
                <hr>
                <p><strong>First Booking:</strong> <?php echo date('M j, Y', strtotime($stats['first_booking'])); ?></p>
                <p><strong>Last Booking:</strong> <?php echo date('M j, Y', strtotime($stats['last_booking'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($stats['status_distribution'])): ?>
                <hr>
                <h6>Booking Status</h6>
                <?php foreach ($stats['status_distribution'] as $status): ?>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-<?php 
                        echo $status['status'] === 'checked_out' ? 'success' : 
                            ($status['status'] === 'checked_in' ? 'primary' : 
                            ($status['status'] === 'confirmed' ? 'info' : 
                            ($status['status'] === 'pending' ? 'warning' : 'secondary'))); 
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?>
                    </span>
                    <span class="text-muted"><?php echo $status['count']; ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Booking History -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-calendar-check me-2"></i>
            Booking History (<?php echo count($bookings); ?> bookings)
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-calendar fa-2x mb-3"></i>
            <p>No bookings found for this customer.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Booking #</th>
                        <th>Room Type</th>
                        <th>Check-in/Check-out</th>
                        <th>Guests</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Room #</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                        <td>
                            <div>
                                <strong>In:</strong> <?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?><br>
                                <strong>Out:</strong> <?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $booking['num_guests']; ?> guest<?php echo $booking['num_guests'] > 1 ? 's' : ''; ?></span>
                            <?php if ($booking['num_rooms'] > 1): ?>
                            <br><small class="text-muted"><?php echo $booking['num_rooms']; ?> rooms</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $booking['status'] === 'checked_out' ? 'success' : 
                                    ($booking['status'] === 'checked_in' ? 'primary' : 
                                    ($booking['status'] === 'confirmed' ? 'info' : 
                                    ($booking['status'] === 'pending' ? 'warning' : 'secondary'))); 
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
                            <?php if ($booking['payment_method']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($booking['payment_method']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $booking['room_number'] ? htmlspecialchars($booking['room_number']) : '-'; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div> 