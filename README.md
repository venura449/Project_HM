# Boomerang Project - Hotel Booking Management System

##  a) Project Title
**Boomerang Project - Hotel Booking Management System**

##  b) Task Option Chosen
**Task Option 1** - Web-Based Admin Dashboard (Hotel Booking Management System)

##  c) Technologies Used
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3.0, HTML5, CSS3, JavaScript
- **Charts**: Chart.js
- **Icons**: Font Awesome 6.0.0
- **Database Access**: PDO with prepared statements

##  d) Features Implemented
1.  ** Secure Authentication System** - Role-based access control with Super Admin and Admin roles
2.  ** Customer Management** - Add, edit, view customers with detailed profiles and booking history
3.  ** Hotel Booking Management** - Create bookings, track status (Pending→Confirmed→Checked In→Checked Out), manage payments
4.  ** Analytics Dashboard** - Real-time statistics, interactive charts, booking trends, revenue tracking
5.  ** Reporting System** - Generate booking reports, export functionality, customer analytics
6.  ** Responsive Design** - Fixed sidebar navigation, mobile-friendly interface, modern UI

##  e) Instructions to Run the Project

### Prerequisites:
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions:

1. **Clone/Download Project**
   ```
   Download the project files to your web server directory
   ```

2. **Database Configuration**
   - Open `Includes/config.php`
   - Update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. **Start Web Server**
   - Start XAMPP/WAMP/LAMP
   - Start Apache and MySQL services

4. **Access Application**
   - Open browser and navigate to: `http://localhost/Boomerang_project`
   - You'll be redirected to login page

5. **Default Login Credentials**
   - **Username**: `admin`
   - **Password**: `admin123`
   - **Role**: Super Admin

### Quick Start:
1. Start XAMPP → Apache & MySQL
2. Open browser → `http://localhost/Boomerang_project`
3. Login with admin/admin123
4. Start managing your hotel bookings!

---

**Note**: Change default password after first login for security!

##  Authentication & Security
- **Role-based Access Control**: Super Admin and Admin roles
- **Secure Login System**: Password hashing and session management
- **Authentication Middleware**: Protected routes and admin verification

##  Customer Management
- **Customer Registration**: Add new customers with detailed information
- **Customer Profiles**: Comprehensive customer details including:
  - Personal information (name, email, phone, address)
  - Customer type (Individual, Business, VIP)
  - Status management (Active, Inactive, Blocked)
  - Booking history and statistics
- **Search & Filter**: Advanced search by name, email, phone
- **Customer Details View**: Detailed modal with booking history and statistics
- **Customer Statistics**: Total bookings, spending, favorite room types

##  Hotel Booking Management
- **Booking Creation**: Create new hotel bookings with room details
- **Room Management**: Support for different room types and configurations
- **Booking Status Tracking**: 
  - Pending → Confirmed → Checked In → Checked Out
  - Cancelled and No-show statuses
- **Payment Management**: Track payment status (Pending, Paid, Partial, Refunded)
- **Room Assignment**: Assign specific room numbers to bookings
- **Guest Management**: Track number of guests and rooms
- **Special Requests**: Handle customer special requirements

##  Analytics & Reporting
- **Dashboard Analytics**: Real-time statistics and metrics
- **Booking Trends**: Monthly booking patterns and revenue analysis
- **Room Analytics**: Popular room types and occupancy rates
- **Status Distribution**: Booking status breakdown
- **Occupancy Trends**: Daily occupancy patterns
- **Customer Growth**: Monthly customer registration trends
- **Revenue Tracking**: Total revenue, average booking values
- **Report Generation**: Export booking reports in HTML format

##  User Interface
- **Modern Design**: Clean, responsive Bootstrap 5 interface
- **Fixed Sidebar**: Persistent navigation that doesn't scroll
- **Responsive Layout**: Works on desktop, tablet, and mobile devices
- **Interactive Charts**: Chart.js powered analytics visualizations
- **Modal Dialogs**: Clean forms for adding/editing data
- **Color-coded Status**: Visual indicators for different statuses

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3.0
- **Icons**: Font Awesome 6.0.0
- **Charts**: Chart.js
- **Database Access**: PDO with prepared statements

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PDO MySQL extension
- GD extension (for charts)

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd Boomerang_project
```

### 2. Database Setup
1. Create a MySQL database
2. Import the database schema (the system will auto-create tables on first run)
3. Update database credentials in `Includes/config.php`

### 3. Configure Database Connection
Edit `Includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Web Server Configuration
- Point your web server to the project directory
- Ensure PHP has write permissions for session management
- Configure URL rewriting if needed

### 5. Default Admin Account
The system creates a default super admin account:
- **Username**: admin
- **Password**: admin123
- **Role**: Super Admin

**Important**: Change the default password after first login!

##  Project Structure

```
Boomerang_project/
├── dashboard.php              # Main dashboard with analytics
├── index.php                  # Login page
├── login.php                  # Authentication handler
├── logout.php                 # Logout handler
├── Includes/
│   ├── auth.php              # Authentication functions
│   └── config.php            # Database configuration
├── Pages/
│   ├── customers.php         # Customer management
│   ├── customer_details.php  # Customer details modal
│   ├── bookings.php          # Booking management
│   ├── profile.php           # Admin profile management
│   ├── manage_admins.php     # Admin user management
│   ├── settings.php          # System settings
│   ├── generate_booking_report.php  # Report generation
│   └── download_receipt.php  # Receipt download
└── README.md                 # This file
```

##  Database Schema

### Admins Table
- User management for system administrators
- Role-based access control
- Login tracking and session management

### Customers Table
- Customer information and contact details
- Customer type classification
- Status management
- Total spending and booking counts

### Bookings Table
- Hotel booking records
- Room type and configuration
- Check-in/check-out dates
- Payment and status tracking
- Guest and room counts

## 🔧 Configuration

### Database Settings
All database settings are in `Includes/config.php`:
- Database connection parameters
- Table creation and initialization
- Sample data insertion

### Authentication Settings
Session and security settings in `Includes/auth.php`:
- Session timeout configuration
- Password requirements
- Role permissions

##  Usage Guide

### For Administrators

#### Dashboard
- View real-time statistics
- Monitor booking trends
- Track revenue and occupancy
- Access quick actions

#### Customer Management
1. **Add Customer**: Click "Add Customer" button
2. **View Details**: Click the eye icon to see customer details
3. **Edit Customer**: Click the edit icon to modify information
4. **Search/Filter**: Use the search bar and filters to find customers

#### Booking Management
1. **Create Booking**: Click "Add Booking" button
2. **Update Status**: Use the status dropdown to change booking status
3. **Assign Room**: Enter room number when checking in
4. **Track Payments**: Update payment status as needed

#### Reports
- Generate booking reports with filters
- Export reports in HTML format
- View detailed analytics and charts

### For Super Admins
- Manage other admin accounts
- Access system settings
- Full administrative privileges

## �� Security Features

- **Password Hashing**: All passwords are hashed using PHP's password_hash()
- **SQL Injection Protection**: Prepared statements throughout
- **Session Security**: Secure session management
- **Input Validation**: Server-side validation for all inputs
- **XSS Protection**: HTML escaping for output
- **CSRF Protection**: Form token validation

## Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- Different screen sizes and orientations

## Key Features

### Real-time Analytics
- Live dashboard with current statistics
- Interactive charts and graphs
- Trend analysis and forecasting

### Comprehensive Reporting
- Detailed booking reports
- Customer analysis
- Revenue tracking
- Occupancy statistics

### User-friendly Interface
- Intuitive navigation
- Clean, modern design
- Fast loading times
- Easy-to-use forms

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Errors**
   - Ensure PHP has write permissions
   - Check file permissions for session storage

3. **Charts Not Loading**
   - Verify internet connection (Chart.js CDN)
   - Check browser console for JavaScript errors

4. **Login Issues**
   - Clear browser cache and cookies
   - Check session configuration
   - Verify admin credentials

##  Updates and Maintenance

### Regular Maintenance
- Monitor database performance
- Review and clean old session data
- Update PHP and MySQL versions
- Backup database regularly

### Adding New Features
- Follow existing code structure
- Use prepared statements for database queries
- Maintain responsive design principles
- Test thoroughly before deployment

##  Support

For technical support or feature requests:
- Check the documentation
- Review the code comments
- Test in a development environment first

## 📄 License

This project is developed by J.A Venura Jayasingha for BOOMERANG SOFTWARE ENGINEER RECRUITMENT PROCESS.

##  Future Enhancements

Potential features for future versions:
- Email notifications
- SMS integration
- Payment gateway integration
- Mobile app
- API endpoints
- Multi-language support
- Advanced reporting
- Inventory management

---

**Boomerang Project** - Making hotel management simple and efficient! 🏨✨ 
