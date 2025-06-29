<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boomerang_admin');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database and create tables
function initializeDatabase() {
    try {
        // Create database if it doesn't exist
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        
        // Create admin table
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'admin') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        
        // Create customers table
        $sql = "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            state VARCHAR(50),
            zip_code VARCHAR(20),
            country VARCHAR(50) DEFAULT 'USA',
            customer_type ENUM('individual', 'business', 'vip') DEFAULT 'individual',
            status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
            total_spent DECIMAL(10,2) DEFAULT 0.00,
            total_bookings INT DEFAULT 0,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_customer_type (customer_type)
        )";
        $pdo->exec($sql);
        
        // Create bookings table
        $sql = "CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            booking_number VARCHAR(20) UNIQUE NOT NULL,
            service_type VARCHAR(100) NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            duration INT DEFAULT 60,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('pending', 'paid', 'refunded', 'partial') DEFAULT 'pending',
            payment_method VARCHAR(50),
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_booking_date (booking_date),
            INDEX idx_status (status),
            INDEX idx_payment_status (payment_status)
        )";
        $pdo->exec($sql);
        
        // Create sales table
        $sql = "CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT,
            customer_id INT NOT NULL,
            sale_number VARCHAR(20) UNIQUE NOT NULL,
            product_name VARCHAR(100) NOT NULL,
            quantity INT DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            final_amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
            sale_date DATE NOT NULL,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_sale_date (sale_date),
            INDEX idx_payment_status (payment_status)
        )";
        $pdo->exec($sql);
        
        // Create system_settings table
        $sql = "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        
        // Check if default admin exists, if not create one
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@boomerang.com', $defaultPassword, 'System Administrator', 'super_admin']);
        }
        
        // Insert sample data for demonstration
        insertSampleData($pdo);
        
        return true;
    } catch(PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

// Insert sample data for demonstration
function insertSampleData($pdo) {
    try {
        // Check if sample data already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers");
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            return; // Sample data already exists
        }
        
        // Insert sample customers
        $customers = [
            ['John', 'Doe', 'john.doe@email.com', '555-0101', '123 Main St', 'New York', 'NY', '10001', 'individual'],
            ['Jane', 'Smith', 'jane.smith@email.com', '555-0102', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'business'],
            ['Mike', 'Johnson', 'mike.johnson@email.com', '555-0103', '789 Pine Rd', 'Chicago', 'IL', '60601', 'vip'],
            ['Sarah', 'Williams', 'sarah.williams@email.com', '555-0104', '321 Elm St', 'Houston', 'TX', '77001', 'individual'],
            ['David', 'Brown', 'david.brown@email.com', '555-0105', '654 Maple Dr', 'Phoenix', 'AZ', '85001', 'business'],
            ['Lisa', 'Davis', 'lisa.davis@email.com', '555-0106', '987 Cedar Ln', 'Philadelphia', 'PA', '19101', 'vip'],
            ['Robert', 'Miller', 'robert.miller@email.com', '555-0107', '147 Birch Way', 'San Antonio', 'TX', '78201', 'individual'],
            ['Emily', 'Wilson', 'emily.wilson@email.com', '555-0108', '258 Spruce Ct', 'San Diego', 'CA', '92101', 'business'],
            ['James', 'Taylor', 'james.taylor@email.com', '555-0109', '369 Willow Pl', 'Dallas', 'TX', '75201', 'vip'],
            ['Amanda', 'Anderson', 'amanda.anderson@email.com', '555-0110', '741 Poplar St', 'San Jose', 'CA', '95101', 'individual']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, address, city, state, zip_code, customer_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($customers as $customer) {
            $stmt->execute($customer);
        }
        
        // Insert sample bookings
        $bookings = [
            [1, 'BK001', 'Haircut & Styling', '2024-01-15', '10:00:00', 60, 'completed', 45.00, 'paid', 'credit_card'],
            [2, 'BK002', 'Manicure & Pedicure', '2024-01-16', '14:30:00', 90, 'completed', 75.00, 'paid', 'cash'],
            [3, 'BK003', 'Facial Treatment', '2024-01-17', '11:00:00', 120, 'completed', 120.00, 'paid', 'credit_card'],
            [4, 'BK004', 'Hair Coloring', '2024-01-18', '15:00:00', 180, 'confirmed', 150.00, 'pending', 'credit_card'],
            [5, 'BK005', 'Massage Therapy', '2024-01-19', '09:00:00', 60, 'pending', 80.00, 'pending', 'cash'],
            [6, 'BK006', 'Spa Package', '2024-01-20', '13:00:00', 240, 'pending', 200.00, 'pending', 'credit_card'],
            [7, 'BK007', 'Haircut & Beard Trim', '2024-01-21', '16:00:00', 45, 'pending', 35.00, 'pending', 'cash'],
            [8, 'BK008', 'Nail Art', '2024-01-22', '12:00:00', 60, 'pending', 50.00, 'pending', 'credit_card'],
            [9, 'BK009', 'Body Treatment', '2024-01-23', '10:30:00', 90, 'pending', 95.00, 'pending', 'cash'],
            [10, 'BK010', 'Hair Styling', '2024-01-24', '14:00:00', 60, 'pending', 55.00, 'pending', 'credit_card']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, booking_number, service_type, booking_date, booking_time, duration, status, total_amount, payment_status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($bookings as $booking) {
            $stmt->execute($booking);
        }
        
        // Insert sample sales
        $sales = [
            [1, 1, 'SL001', 'Hair Products', 2, 25.00, 50.00, 5.00, 45.00, 'credit_card', 'paid', '2024-01-15'],
            [2, 2, 'SL002', 'Nail Polish', 3, 15.00, 45.00, 0.00, 45.00, 'cash', 'paid', '2024-01-16'],
            [3, 3, 'SL003', 'Skincare Products', 1, 80.00, 80.00, 10.00, 70.00, 'credit_card', 'paid', '2024-01-17'],
            [4, 4, 'SL004', 'Hair Dye', 1, 45.00, 45.00, 0.00, 45.00, 'credit_card', 'pending', '2024-01-18'],
            [5, 5, 'SL005', 'Massage Oil', 1, 30.00, 30.00, 5.00, 25.00, 'cash', 'pending', '2024-01-19'],
            [6, 6, 'SL006', 'Spa Gift Set', 1, 75.00, 75.00, 15.00, 60.00, 'credit_card', 'pending', '2024-01-20'],
            [7, 7, 'SL007', 'Beard Oil', 1, 20.00, 20.00, 0.00, 20.00, 'cash', 'pending', '2024-01-21'],
            [8, 8, 'SL008', 'Nail Art Supplies', 2, 18.00, 36.00, 0.00, 36.00, 'credit_card', 'pending', '2024-01-22'],
            [9, 9, 'SL009', 'Body Lotion', 1, 40.00, 40.00, 8.00, 32.00, 'cash', 'pending', '2024-01-23'],
            [10, 10, 'SL010', 'Hair Accessories', 3, 12.00, 36.00, 0.00, 36.00, 'credit_card', 'pending', '2024-01-24']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO sales (booking_id, customer_id, sale_number, product_name, quantity, unit_price, total_price, discount_amount, final_amount, payment_method, payment_status, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sales as $sale) {
            $stmt->execute($sale);
        }
        
        // Update customer totals
        updateCustomerTotals($pdo);
        
    } catch(PDOException $e) {
        // Silently fail for sample data insertion
    }
}

// Update customer totals based on bookings and sales
function updateCustomerTotals($pdo, $customer_id = null) {
    try {
        if ($customer_id) {
            // Update totals for a specific customer
            $sql = "UPDATE customers c 
                    SET total_spent = (
                        SELECT COALESCE(SUM(b.total_amount), 0) + COALESCE(SUM(s.final_amount), 0)
                        FROM customers cust
                        LEFT JOIN bookings b ON cust.id = b.customer_id
                        LEFT JOIN sales s ON cust.id = s.customer_id
                        WHERE cust.id = ?
                    ),
                    total_bookings = (
                        SELECT COUNT(*)
                        FROM bookings b
                        WHERE b.customer_id = ?
                    )
                    WHERE c.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$customer_id, $customer_id, $customer_id]);
        } else {
            // Update totals for all customers
            $sql = "UPDATE customers c 
                    SET total_spent = (
                        SELECT COALESCE(SUM(b.total_amount), 0) + COALESCE(SUM(s.final_amount), 0)
                        FROM customers cust
                        LEFT JOIN bookings b ON cust.id = b.customer_id
                        LEFT JOIN sales s ON cust.id = s.customer_id
                        WHERE cust.id = c.id
                    ),
                    total_bookings = (
                        SELECT COUNT(*)
                        FROM bookings b
                        WHERE b.customer_id = c.id
                    )";
            $pdo->exec($sql);
        }
    } catch(PDOException $e) {
        // Silently fail for totals update
    }
}

// Initialize database on first run
initializeDatabase();
?> 