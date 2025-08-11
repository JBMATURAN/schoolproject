# Computer Laboratory Inventory Management System

A comprehensive web-based inventory management system designed specifically for computer laboratories, built with modern web technologies and security best practices.

![Dashboard Preview](https://via.placeholder.com/800x400/4e73df/ffffff?text=Computer+Lab+Inventory+Dashboard)

## 🚀 Features

### Core Functionality
- **📊 Real-time Dashboard** - Live statistics, charts, and activity monitoring
- **💻 Equipment Management** - Complete CRUD operations for all lab equipment
- **🏢 Location Tracking** - Multi-building and room management
- **👥 User Management** - Role-based access control (Admin, Technician, Teacher, Student)
- **🔧 Maintenance Scheduling** - Preventive and corrective maintenance tracking
- **📱 Responsive Design** - Works perfectly on desktop, tablet, and mobile

### Advanced Features
- **🔍 Advanced Search & Filtering** - Find equipment quickly with multiple criteria
- **📈 Analytics & Reports** - Equipment utilization, maintenance trends, cost analysis
- **🏷️ QR Code Generation** - Automatic QR codes for easy equipment identification
- **📋 Audit Trail** - Complete history of all equipment changes and movements
- **⚠️ Smart Alerts** - Maintenance due, warranty expiring, equipment issues
- **📊 Data Export** - CSV export for external analysis

### Security Features
- **🔐 Secure Authentication** - Password hashing, session management
- **🛡️ CSRF Protection** - Cross-site request forgery prevention
- **⏱️ Rate Limiting** - Brute force attack protection
- **🔑 Role-based Permissions** - Granular access control
- **📝 Activity Logging** - Complete user action audit trail

## 🏗️ Architecture

### Technology Stack
- **Backend**: PHP 8.0+ with PDO for database operations
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Charts**: Chart.js for data visualization
- **Icons**: Bootstrap Icons
- **Security**: Secure session handling, password hashing, CSRF tokens

### File Structure
```
computer-lab-inventory/
├── api/                          # REST API endpoints
│   ├── equipment.php            # Equipment CRUD operations
│   ├── dashboard-stats.php      # Dashboard statistics
│   └── auth.php                 # Authentication endpoints
├── auth/                        # Authentication system
│   └── session.php              # Session management & security
├── models/                      # Data models
│   ├── Database.php             # Base database class
│   └── Equipment.php            # Equipment model
├── public/                      # Web-accessible files
│   ├── index.html               # Main dashboard
│   ├── login.html               # Login page
│   ├── css/
│   │   └── dashboard.css        # Custom styles
│   └── js/
│       └── dashboard.js         # Client-side functionality
├── config.php                  # Database configuration
├── database_connection.php     # Database connection class
├── database_schema.sql         # Complete database schema
├── example_usage.php           # Usage examples
└── README.md                   # This file
```

## 🔧 Installation

### Prerequisites
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0 or higher with extensions:
  - PDO
  - PDO_MySQL
  - JSON
  - OpenSSL
  - Session
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Browser**: Modern browser with JavaScript enabled

### Step 1: Download and Setup
```bash
# Clone or download the repository
git clone https://github.com/your-repo/computer-lab-inventory.git
cd computer-lab-inventory

# Set proper permissions
chmod 755 public/
chmod 644 public/*.html
chmod -R 755 api/ auth/ models/
```

### Step 2: Database Setup
```bash
# Create database and import schema
mysql -u root -p
CREATE DATABASE computer_lab_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Import the database schema
mysql -u root -p computer_lab_inventory < database_schema.sql
```

### Step 3: Configuration
```php
// Edit config.php with your database credentials
$config = [
    'database' => [
        'host' => 'localhost',
        'name' => 'computer_lab_inventory',
        'username' => 'your_db_user',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4'
    ]
];
```

### Step 4: Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [L]
RewriteRule ^(.*)$ public/$1 [L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/computer-lab-inventory;
    index index.html;

    location / {
        try_files $uri $uri/ /public/$uri /public/$uri/;
    }

    location /api/ {
        try_files $uri $uri/ /api/$uri;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
}
```

### Step 5: Access the System
1. Open your web browser
2. Navigate to `http://your-domain.com`
3. Login with default credentials:
   - **Username**: admin
   - **Password**: password
4. Change the default password immediately!

## 👥 User Roles & Permissions

### Admin
- Full system access
- User management
- System configuration
- All equipment operations
- Reports and analytics

### Technician
- Equipment management (create, update, move)
- Maintenance operations
- Incident management
- Equipment assignment

### Teacher
- View equipment
- Equipment assignment/return
- Lab session management
- Usage logging

### Student
- View equipment (limited)
- Usage logging
- Report issues

## 📊 Database Schema

### Core Tables
- **users** - User accounts and authentication
- **buildings** - Building information
- **rooms** - Lab rooms and locations
- **categories** - Equipment categories (hierarchical)
- **manufacturers** - Equipment manufacturers
- **suppliers** - Vendor information
- **equipment** - Main equipment table

### Tracking Tables
- **equipment_movements** - Equipment location/assignment history
- **maintenance_records** - Maintenance activities
- **incidents** - Problem reports
- **usage_logs** - Equipment usage tracking
- **audit_log** - Complete system audit trail

### Session & Security Tables
- **failed_login_attempts** - Rate limiting
- **remember_tokens** - "Remember me" functionality
- **user_activity** - User action logging

## 🔐 Security Features

### Authentication
- **Secure Password Hashing**: bcrypt with cost factor 12
- **Session Security**: HTTPOnly, Secure, SameSite cookies
- **Session Fingerprinting**: Browser and IP validation
- **Remember Me**: Secure token-based persistence

### Protection Mechanisms
- **Rate Limiting**: 5 failed attempts = 15-minute lockout
- **CSRF Protection**: Tokens on all state-changing operations
- **Input Validation**: Comprehensive sanitization and validation
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Output escaping and CSP headers

### Audit & Monitoring
- **Complete Audit Trail**: All changes logged with user, timestamp, IP
- **User Activity Logging**: Login, logout, and action tracking
- **Failed Login Monitoring**: Attempted breaches tracked
- **Data Integrity**: Foreign key constraints and transaction support

## 🚀 API Endpoints

### Equipment Management
```bash
# Get all equipment (with filtering and pagination)
GET /api/equipment.php?page=1&limit=10&status=available

# Get single equipment
GET /api/equipment.php?id=123

# Create equipment
POST /api/equipment.php
Content-Type: application/json
{
    "asset_tag": "LAB-001",
    "name": "Dell OptiPlex 7090",
    "category_id": 1,
    "manufacturer_id": 1
}

# Update equipment
PUT /api/equipment.php?id=123
Content-Type: application/json
{
    "status": "maintenance",
    "notes": "Scheduled maintenance"
}

# Delete equipment
DELETE /api/equipment.php?id=123

# Export to CSV
GET /api/equipment.php?export=csv&status=available
```

### Dashboard Statistics
```bash
# Get dashboard data
GET /api/dashboard-stats.php

# Returns:
{
    "success": true,
    "data": {
        "total": 150,
        "by_status": {
            "available": 120,
            "in_use": 25,
            "maintenance": 3,
            "repair": 2
        },
        "recent_activities": [...],
        "maintenance_due": {...},
        "alerts": [...]
    }
}
```

## 🎨 Customization

### Theming
- Modify CSS variables in `public/css/dashboard.css`
- Bootstrap 5 utility classes for rapid styling
- Dark mode support with CSS media queries

### Adding Features
1. Create new API endpoints in `/api/`
2. Add corresponding JavaScript functions
3. Update the UI components
4. Add necessary database tables/columns

### Configuration Options
Edit system settings through the database:
```sql
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('lab_name', 'Your Lab Name', 'Laboratory name displayed in header'),
('maintenance_alert_days', '30', 'Days before maintenance due alert'),
('enable_qr_codes', 'true', 'Enable QR code generation');
```

## 🧪 Development

### Local Development Setup
```bash
# Use PHP built-in server for development
cd public
php -S localhost:8000

# Or use Docker
docker-compose up -d
```

### Testing
```php
// Run the example usage script to test functionality
php example_usage.php
```

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📋 Maintenance

### Regular Tasks
- **Database Backups**: Schedule daily automated backups
- **Log Rotation**: Rotate and archive audit logs monthly
- **Security Updates**: Keep PHP, web server, and database updated
- **Performance Monitoring**: Monitor query performance and optimize

### Backup Script Example
```bash
#!/bin/bash
# Daily backup script
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u backup_user -p computer_lab_inventory > backup_$DATE.sql
gzip backup_$DATE.sql
# Upload to secure storage
```

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `config.php`
- Verify MySQL service is running
- Check firewall settings

**Permission Denied Errors**
- Verify web server has read access to files
- Check PHP session directory permissions
- Ensure database user has correct privileges

**Login Not Working**
- Clear browser cookies and cache
- Check PHP session configuration
- Verify user account is active in database

**AJAX Requests Failing**
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Check server error logs

### Debug Mode
Enable debug mode by adding to `config.php`:
```php
$config['debug'] = true;
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Bootstrap Team** - For the excellent CSS framework
- **Chart.js Team** - For beautiful, responsive charts
- **PHP Community** - For the robust language and ecosystem
- **Security Community** - For best practices and guidance

## 📞 Support

For support, questions, or contributions:
- **Documentation**: Check this README and code comments
- **Issues**: Create an issue on GitHub
- **Security**: Report security issues privately to security@example.com
- **Community**: Join our discussion forum

---

**Built with ❤️ for educational institutions and computer laboratories worldwide.**

> "Efficiency is doing things right; effectiveness is doing the right things." - Peter Drucker

This inventory system helps you do both efficiently and effectively.