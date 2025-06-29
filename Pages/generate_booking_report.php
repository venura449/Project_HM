<?php
require_once '../Includes/auth.php';
requireAuth();

$currentAdmin = getCurrentAdmin();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get bookings with filters
function getBookingsForReport($search = '', $status_filter = '', $date_from = '', $date_to = '') {
    try {
        $pdo = getDBConnection();
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(b.booking_number LIKE ? OR b.service_type LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "b.status = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = "b.booking_date >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "b.booking_date <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT b.*, c.first_name, c.last_name, c.email, c.phone 
                FROM bookings b 
                LEFT JOIN customers c ON b.customer_id = c.id 
                $where_clause 
                ORDER BY b.booking_date DESC, b.booking_time DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get report statistics
function getReportStats($bookings) {
    $stats = [
        'total_bookings' => count($bookings),
        'total_revenue' => 0,
        'paid_revenue' => 0,
        'pending_revenue' => 0,
        'completed_bookings' => 0,
        'pending_bookings' => 0,
        'confirmed_bookings' => 0,
        'cancelled_bookings' => 0,
        'avg_booking_value' => 0,
        'services' => [],
        'payment_methods' => []
    ];
    
    foreach ($bookings as $booking) {
        $stats['total_revenue'] += $booking['total_amount'];
        
        if ($booking['payment_status'] === 'paid') {
            $stats['paid_revenue'] += $booking['total_amount'];
        } else {
            $stats['pending_revenue'] += $booking['total_amount'];
        }
        
        switch ($booking['status']) {
            case 'completed':
                $stats['completed_bookings']++;
                break;
            case 'pending':
                $stats['pending_bookings']++;
                break;
            case 'confirmed':
                $stats['confirmed_bookings']++;
                break;
            case 'cancelled':
                $stats['cancelled_bookings']++;
                break;
        }
        
        // Count services
        if (!isset($stats['services'][$booking['service_type']])) {
            $stats['services'][$booking['service_type']] = 0;
        }
        $stats['services'][$booking['service_type']]++;
        
        // Count payment methods
        if ($booking['payment_method']) {
            if (!isset($stats['payment_methods'][$booking['payment_method']])) {
                $stats['payment_methods'][$booking['payment_method']] = 0;
            }
            $stats['payment_methods'][$booking['payment_method']]++;
        }
    }
    
    if ($stats['total_bookings'] > 0) {
        $stats['avg_booking_value'] = $stats['total_revenue'] / $stats['total_bookings'];
    }
    
    return $stats;
}

$bookings = getBookingsForReport($search, $status_filter, $date_from, $date_to);
$stats = getReportStats($bookings);

// Set headers for report download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="booking_report_' . date('Y-m-d_H-i-s') . '.html"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Report - Boomerang Project</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .report-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        .report-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .report-body {
            padding: 30px;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .info-section {
            flex: 1;
            min-width: 250px;
            margin-bottom: 20px;
        }
        .info-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
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
        .stat-card h3 {
            margin: 0;
            font-size: 24px;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .bookings-table th {
            background-color: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .bookings-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .bookings-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: #333;
        }
        .status-confirmed {
            background-color: #007bff;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .payment-paid {
            background-color: #28a745;
            color: white;
        }
        .payment-pending {
            background-color: #6c757d;
            color: white;
        }
        .payment-partial {
            background-color: #ffc107;
            color: #333;
        }
        .summary-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .summary-section h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .summary-item h4 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        .summary-item ul {
            margin: 0;
            padding-left: 20px;
        }
        .summary-item li {
            margin-bottom: 5px;
        }
        .report-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .report-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1>BOOMERANG PROJECT</h1>
            <p>Professional Services & Solutions</p>
            <p>Booking Report</p>
        </div>
        
        <div class="report-body">
            <div class="report-info">
                <div class="info-section">
                    <h3>Report Information</h3>
                    <div class="info-row">
                        <span class="info-label">Report Date:</span>
                        <span class="info-value"><?php echo date('F j, Y \a\t g:i A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Generated By:</span>
                        <span class="info-value"><?php echo htmlspecialchars($currentAdmin['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Records:</span>
                        <span class="info-value"><?php echo $stats['total_bookings']; ?></span>
                    </div>
                    <?php if ($date_from || $date_to): ?>
                    <div class="info-row">
                        <span class="info-label">Date Range:</span>
                        <span class="info-value">
                            <?php echo $date_from ? date('M j, Y', strtotime($date_from)) : 'Start'; ?> - 
                            <?php echo $date_to ? date('M j, Y', strtotime($date_to)) : 'End'; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h3>Filters Applied</h3>
                    <div class="info-row">
                        <span class="info-label">Search:</span>
                        <span class="info-value"><?php echo $search ? htmlspecialchars($search) : 'None'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status Filter:</span>
                        <span class="info-value"><?php echo $status_filter ? ucfirst($status_filter) : 'All'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-card success">
                    <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card warning">
                    <h3>$<?php echo number_format($stats['paid_revenue'], 2); ?></h3>
                    <p>Paid Revenue</p>
                </div>
                <div class="stat-card info">
                    <h3>$<?php echo number_format($stats['avg_booking_value'], 2); ?></h3>
                    <p>Avg. Booking Value</p>
                </div>
            </div>
            
            <!-- Bookings Table -->
            <h3 style="color: #667eea; margin-bottom: 20px;">Booking Details</h3>
            <div class="table-responsive">
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <p>No bookings found for the specified criteria</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['service_type']); ?></td>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></small>
                                </td>
                                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge payment-<?php echo $booking['payment_status']; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Section -->
            <div class="summary-section">
                <h3>Summary & Analytics</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <h4>Booking Status Breakdown</h4>
                        <ul>
                            <li>Completed: <?php echo $stats['completed_bookings']; ?> (<?php echo $stats['total_bookings'] > 0 ? round(($stats['completed_bookings'] / $stats['total_bookings']) * 100, 1) : 0; ?>%)</li>
                            <li>Pending: <?php echo $stats['pending_bookings']; ?> (<?php echo $stats['total_bookings'] > 0 ? round(($stats['pending_bookings'] / $stats['total_bookings']) * 100, 1) : 0; ?>%)</li>
                            <li>Confirmed: <?php echo $stats['confirmed_bookings']; ?> (<?php echo $stats['total_bookings'] > 0 ? round(($stats['confirmed_bookings'] / $stats['total_bookings']) * 100, 1) : 0; ?>%)</li>
                            <li>Cancelled: <?php echo $stats['cancelled_bookings']; ?> (<?php echo $stats['total_bookings'] > 0 ? round(($stats['cancelled_bookings'] / $stats['total_bookings']) * 100, 1) : 0; ?>%)</li>
                        </ul>
                    </div>
                    
                    <div class="summary-item">
                        <h4>Revenue Analysis</h4>
                        <ul>
                            <li>Total Revenue: $<?php echo number_format($stats['total_revenue'], 2); ?></li>
                            <li>Paid Revenue: $<?php echo number_format($stats['paid_revenue'], 2); ?> (<?php echo $stats['total_revenue'] > 0 ? round(($stats['paid_revenue'] / $stats['total_revenue']) * 100, 1) : 0; ?>%)</li>
                            <li>Pending Revenue: $<?php echo number_format($stats['pending_revenue'], 2); ?> (<?php echo $stats['total_revenue'] > 0 ? round(($stats['pending_revenue'] / $stats['total_revenue']) * 100, 1) : 0; ?>%)</li>
                            <li>Average Booking Value: $<?php echo number_format($stats['avg_booking_value'], 2); ?></li>
                        </ul>
                    </div>
                    
                    <?php if (!empty($stats['services'])): ?>
                    <div class="summary-item">
                        <h4>Top Services</h4>
                        <ul>
                            <?php 
                            arsort($stats['services']);
                            $top_services = array_slice($stats['services'], 0, 5, true);
                            foreach ($top_services as $service => $count): 
                            ?>
                            <li><?php echo htmlspecialchars($service); ?>: <?php echo $count; ?> bookings</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($stats['payment_methods'])): ?>
                    <div class="summary-item">
                        <h4>Payment Methods</h4>
                        <ul>
                            <?php 
                            arsort($stats['payment_methods']);
                            foreach ($stats['payment_methods'] as $method => $count): 
                            ?>
                            <li><?php echo ucfirst(htmlspecialchars($method)); ?>: <?php echo $count; ?> bookings</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="report-footer">
            <p><strong>This report was generated automatically by the Boomerang Project Booking System.</strong></p>
            <p>For questions or support, please contact the system administrator.</p>
        </div>
    </div>
</body>
</html> 