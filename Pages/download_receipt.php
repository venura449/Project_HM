<?php
require_once '../Includes/auth.php';
requireAuth();

$currentAdmin = getCurrentAdmin();

// Get sale ID from URL
$sale_id = $_GET['sale_id'] ?? '';

if (empty($sale_id)) {
    die('Sale ID is required');
}

try {
    $pdo = getDBConnection();
    
    // Get sale details with customer and booking information
    $sql = "SELECT s.*, c.first_name, c.last_name, c.email, c.phone, c.address, c.city, c.state, c.zip_code,
                   b.booking_number, b.service_type, b.booking_date, b.booking_time
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            LEFT JOIN bookings b ON s.booking_id = b.id 
            WHERE s.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        die('Sale not found');
    }
    
} catch(PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set headers for PDF download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="receipt_' . $sale['sale_number'] . '.html"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($sale['sale_number']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .receipt-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .receipt-body {
            padding: 30px;
        }
        .receipt-info {
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
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-section {
            margin-top: 30px;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .total-row.final {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #667eea;
            padding-top: 10px;
            margin-top: 10px;
        }
        .receipt-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: #333;
        }
        .status-refunded {
            background-color: #dc3545;
            color: white;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>BOOMERANG PROJECT</h1>
            <p>Professional Services & Solutions</p>
            <p>Receipt</p>
        </div>
        
        <div class="receipt-body">
            <div class="receipt-info">
                <div class="info-section">
                    <h3>Sale Information</h3>
                    <div class="info-row">
                        <span class="info-label">Sale Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($sale['sale_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sale Date:</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($sale['sale_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo $sale['payment_status']; ?>">
                                <?php echo ucfirst($sale['payment_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value"><?php echo htmlspecialchars($sale['payment_method'] ?? 'Not specified'); ?></span>
                    </div>
                    <?php if ($sale['booking_number']): ?>
                    <div class="info-row">
                        <span class="info-label">Related Booking:</span>
                        <span class="info-value"><?php echo htmlspecialchars($sale['booking_number']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h3>Customer Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($sale['email']); ?></span>
                    </div>
                    <?php if ($sale['phone']): ?>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($sale['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($sale['address']): ?>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($sale['address']); ?><br>
                            <?php echo htmlspecialchars($sale['city'] . ', ' . $sale['state'] . ' ' . $sale['zip_code']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product/Service</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Discount</th>
                        <th>Final Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                        <td><?php echo $sale['quantity']; ?></td>
                        <td>$<?php echo number_format($sale['unit_price'], 2); ?></td>
                        <td>$<?php echo number_format($sale['total_price'], 2); ?></td>
                        <td>$<?php echo number_format($sale['discount_amount'], 2); ?></td>
                        <td><strong>$<?php echo number_format($sale['final_amount'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($sale['total_price'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-$<?php echo number_format($sale['discount_amount'], 2); ?></span>
                </div>
                <div class="total-row final">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($sale['final_amount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>This receipt serves as proof of purchase. Please keep it for your records.</p>
            <p>Generated on <?php echo date('F j, Y \a\t g:i A'); ?> by <?php echo htmlspecialchars($currentAdmin['full_name']); ?></p>
        </div>
    </div>
</body>
</html> 