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
            room_type VARCHAR(100) NOT NULL,
            check_in_date DATE NOT NULL,
            check_out_date DATE NOT NULL,
            num_guests INT DEFAULT 1,
            num_rooms INT DEFAULT 1,
            status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('pending', 'paid', 'refunded', 'partial') DEFAULT 'pending',
            payment_method VARCHAR(50),
            special_requests TEXT,
            room_number VARCHAR(20),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_check_in_date (check_in_date),
            INDEX idx_check_out_date (check_out_date),
            INDEX idx_status (status),
            INDEX idx_payment_status (payment_status)
        )";
        $pdo->exec($sql);
        
        // Remove sales table if exists
        $pdo->exec("DROP TABLE IF EXISTS sales");
        
        // Insert sample bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $bookings = [
                [1, 'BK001', 'Deluxe Room', '2024-01-15', '2024-01-17', 2, 1, 'checked_out', 299.00, 'paid', 'credit_card', null, null, 1],
                [2, 'BK002', 'Suite', '2024-01-16', '2024-01-19', 4, 1, 'checked_out', 599.00, 'paid', 'cash', null, null, 1],
                [3, 'BK003', 'Standard Room', '2024-01-17', '2024-01-18', 1, 1, 'checked_out', 149.00, 'paid', 'credit_card', null, null, 1],
                [4, 'BK004', 'Family Room', '2024-01-18', '2024-01-21', 6, 1, 'confirmed', 449.00, 'pending', 'credit_card', null, null, 1],
                [5, 'BK005', 'Deluxe Room', '2024-01-19', '2024-01-20', 2, 1, 'pending', 199.00, 'pending', 'cash', null, null, 1],
                [6, 'BK006', 'Presidential Suite', '2024-01-20', '2024-01-23', 2, 1, 'pending', 899.00, 'pending', 'credit_card', null, null, 1],
                [7, 'BK007', 'Standard Room', '2024-01-21', '2024-01-22', 1, 1, 'pending', 129.00, 'pending', 'cash', null, null, 1],
                [8, 'BK008', 'Deluxe Room', '2024-01-22', '2024-01-24', 2, 1, 'pending', 398.00, 'pending', 'credit_card', null, null, 1],
                [9, 'BK009', 'Suite', '2024-01-23', '2024-01-25', 3, 1, 'pending', 499.00, 'pending', 'cash', null, null, 1],
                [10, 'BK010', 'Standard Room', '2024-01-24', '2024-01-26', 2, 1, 'pending', 258.00, 'pending', 'credit_card', null, null, 1]
            ];
            $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, booking_number, room_type, check_in_date, check_out_date, num_guests, num_rooms, status, total_amount, payment_status, payment_method, special_requests, room_number, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($bookings as $booking) {
                $stmt->execute($booking);
            }
        }
        
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