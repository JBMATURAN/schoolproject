-- Computer Laboratory Inventory Database Schema
-- Created by Senior Web Developer
-- Date: 2024

-- Set charset and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create database
CREATE DATABASE IF NOT EXISTS computer_lab_inventory 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE computer_lab_inventory;

-- =============================================
-- USERS AND AUTHENTICATION TABLES
-- =============================================

-- Users table for system access
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'technician', 'teacher', 'student') NOT NULL DEFAULT 'student',
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- =============================================
-- LOCATION AND ORGANIZATION TABLES
-- =============================================

-- Buildings table
CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Rooms/Labs table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    capacity INT DEFAULT 0,
    room_type ENUM('computer_lab', 'classroom', 'server_room', 'storage', 'office') DEFAULT 'computer_lab',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room (building_id, room_number),
    INDEX idx_room_type (room_type)
) ENGINE=InnoDB;

-- =============================================
-- INVENTORY CATEGORY TABLES
-- =============================================

-- Categories for equipment types
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB;

-- Manufacturers/Brands
CREATE TABLE manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    website VARCHAR(255),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    address TEXT,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Suppliers/Vendors
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    website VARCHAR(255),
    tax_number VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- MAIN INVENTORY TABLES
-- =============================================

-- Equipment/Items table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    manufacturer_id INT,
    model VARCHAR(100),
    serial_number VARCHAR(100),
    description TEXT,
    specifications JSON,
    purchase_date DATE,
    purchase_price DECIMAL(10, 2),
    supplier_id INT,
    warranty_start DATE,
    warranty_end DATE,
    depreciation_years INT DEFAULT 5,
    current_value DECIMAL(10, 2),
    condition_status ENUM('excellent', 'good', 'fair', 'poor', 'broken') DEFAULT 'good',
    status ENUM('available', 'in_use', 'maintenance', 'repair', 'retired', 'lost', 'stolen') DEFAULT 'available',
    room_id INT,
    assigned_to INT NULL,
    qr_code VARCHAR(255),
    barcode VARCHAR(255),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_asset_tag (asset_tag),
    INDEX idx_serial_number (serial_number),
    INDEX idx_status (status),
    INDEX idx_condition (condition_status),
    INDEX idx_room (room_id),
    INDEX idx_category (category_id),
    FULLTEXT idx_search (name, description, model, serial_number)
) ENGINE=InnoDB;

-- Computer-specific details (extends equipment)
CREATE TABLE computer_details (
    equipment_id INT PRIMARY KEY,
    cpu VARCHAR(100),
    ram_gb INT,
    storage_type ENUM('HDD', 'SSD', 'Hybrid') DEFAULT 'HDD',
    storage_capacity_gb INT,
    gpu VARCHAR(100),
    motherboard VARCHAR(100),
    os_name VARCHAR(50),
    os_version VARCHAR(50),
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    computer_name VARCHAR(100),
    domain_status ENUM('domain', 'workgroup') DEFAULT 'workgroup',
    last_maintenance DATE,
    next_maintenance DATE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- TRACKING AND HISTORY TABLES
-- =============================================

-- Equipment movement/transfer history
CREATE TABLE equipment_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    from_room_id INT,
    to_room_id INT,
    from_user_id INT,
    to_user_id INT,
    movement_type ENUM('room_transfer', 'assignment', 'return', 'maintenance', 'repair') NOT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    notes TEXT,
    moved_by INT NOT NULL,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (from_room_id) REFERENCES rooms(id),
    FOREIGN KEY (to_room_id) REFERENCES rooms(id),
    FOREIGN KEY (from_user_id) REFERENCES users(id),
    FOREIGN KEY (to_user_id) REFERENCES users(id),
    FOREIGN KEY (moved_by) REFERENCES users(id),
    INDEX idx_equipment (equipment_id),
    INDEX idx_movement_date (movement_date)
) ENGINE=InnoDB;

-- Maintenance records
CREATE TABLE maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    maintenance_type ENUM('preventive', 'corrective', 'upgrade', 'cleaning', 'inspection') NOT NULL,
    description TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    cost DECIMAL(10, 2),
    technician_id INT,
    supplier_id INT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    parts_replaced TEXT,
    notes TEXT,
    next_maintenance_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_equipment (equipment_id),
    INDEX idx_maintenance_date (start_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Incident/Problem reports
CREATE TABLE incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    reported_by INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    incident_type ENUM('hardware', 'software', 'network', 'security', 'user_error', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed', 'cancelled') DEFAULT 'open',
    assigned_to INT,
    resolution TEXT,
    resolved_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    INDEX idx_equipment (equipment_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_date (created_at)
) ENGINE=InnoDB;

-- =============================================
-- USAGE AND SCHEDULING TABLES
-- =============================================

-- Lab sessions/bookings
CREATE TABLE lab_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    instructor_id INT NOT NULL,
    course_name VARCHAR(100),
    session_name VARCHAR(200) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    expected_students INT DEFAULT 0,
    actual_students INT DEFAULT 0,
    session_type ENUM('class', 'exam', 'maintenance', 'event', 'other') DEFAULT 'class',
    status ENUM('scheduled', 'active', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (instructor_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_room_time (room_id, start_time),
    INDEX idx_instructor (instructor_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Equipment usage logs
CREATE TABLE usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    user_id INT NOT NULL,
    session_id INT,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    usage_type ENUM('class', 'individual', 'maintenance', 'testing') DEFAULT 'individual',
    purpose TEXT,
    issues_reported TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (session_id) REFERENCES lab_sessions(id),
    INDEX idx_equipment_time (equipment_id, start_time),
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- =============================================
-- SYSTEM CONFIGURATION TABLES
-- =============================================

-- Software licenses
CREATE TABLE software_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    software_name VARCHAR(100) NOT NULL,
    license_type ENUM('per_device', 'per_user', 'site_license', 'subscription') NOT NULL,
    license_key VARCHAR(255),
    total_licenses INT DEFAULT 1,
    used_licenses INT DEFAULT 0,
    vendor VARCHAR(100),
    purchase_date DATE,
    expiry_date DATE,
    cost DECIMAL(10, 2),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_software_name (software_name),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB;

-- Software installations (many-to-many: equipment-software)
CREATE TABLE software_installations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    license_id INT NOT NULL,
    installation_date DATE NOT NULL,
    version VARCHAR(50),
    installation_path VARCHAR(255),
    license_key_used VARCHAR(255),
    installed_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES software_licenses(id),
    FOREIGN KEY (installed_by) REFERENCES users(id),
    UNIQUE KEY unique_installation (equipment_id, license_id),
    INDEX idx_equipment (equipment_id),
    INDEX idx_license (license_id)
) ENGINE=InnoDB;

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    data_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- =============================================
-- AUDIT AND REPORTING TABLES
-- =============================================

-- Audit log for all changes
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB;

-- =============================================
-- INSERT SAMPLE DATA
-- =============================================

-- Insert default admin user
INSERT INTO users (username, email, password_hash, full_name, role, department) VALUES 
('admin', 'admin@lab.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'IT Department');

-- Insert sample buildings
INSERT INTO buildings (name, address, description) VALUES 
('Main Building', '123 University Ave', 'Primary academic building'),
('Technology Center', '456 Tech Dr', 'Dedicated technology and computer labs');

-- Insert sample rooms
INSERT INTO rooms (building_id, room_number, name, capacity, room_type) VALUES 
(1, '101', 'Computer Lab A', 30, 'computer_lab'),
(1, '102', 'Computer Lab B', 25, 'computer_lab'),
(2, '201', 'Advanced Computing Lab', 40, 'computer_lab'),
(2, '301', 'Server Room', 0, 'server_room');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Computers', 'Desktop and laptop computers'),
('Peripherals', 'Keyboards, mice, monitors, etc.'),
('Network Equipment', 'Routers, switches, access points'),
('Storage', 'Hard drives, SSDs, USB drives'),
('Software', 'Licensed software and applications');

INSERT INTO categories (name, description, parent_id) VALUES 
('Desktop Computers', 'Desktop PC systems', 1),
('Laptops', 'Portable laptop computers', 1),
('Monitors', 'Display screens and monitors', 2),
('Input Devices', 'Keyboards and mice', 2);

-- Insert sample manufacturers
INSERT INTO manufacturers (name, website) VALUES 
('Dell', 'https://www.dell.com'),
('HP', 'https://www.hp.com'),
('Lenovo', 'https://www.lenovo.com'),
('ASUS', 'https://www.asus.com'),
('Cisco', 'https://www.cisco.com');

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_person, email, phone) VALUES 
('TechSupply Corp', 'John Smith', 'john@techsupply.com', '+1-555-0123'),
('Computer World', 'Jane Doe', 'jane@computerworld.com', '+1-555-0456');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description, data_type) VALUES 
('lab_name', 'University Computer Laboratory', 'Name of the laboratory', 'string'),
('maintenance_alert_days', '30', 'Days before maintenance due to send alert', 'integer'),
('auto_logout_minutes', '60', 'Minutes of inactivity before auto logout', 'integer'),
('enable_qr_codes', 'true', 'Enable QR code generation for equipment', 'boolean');

-- =============================================
-- CREATE VIEWS FOR COMMON QUERIES
-- =============================================

-- Equipment overview with location and category
CREATE VIEW equipment_overview AS
SELECT 
    e.id,
    e.asset_tag,
    e.name,
    c.name as category_name,
    m.name as manufacturer_name,
    e.model,
    e.serial_number,
    e.status,
    e.condition_status,
    r.name as room_name,
    b.name as building_name,
    u.full_name as assigned_to_name,
    e.purchase_date,
    e.warranty_end,
    e.current_value
FROM equipment e
LEFT JOIN categories c ON e.category_id = c.id
LEFT JOIN manufacturers m ON e.manufacturer_id = m.id
LEFT JOIN rooms r ON e.room_id = r.id
LEFT JOIN buildings b ON r.building_id = b.id
LEFT JOIN users u ON e.assigned_to = u.id
WHERE e.is_active = TRUE;

-- Active incidents summary
CREATE VIEW active_incidents AS
SELECT 
    i.id,
    i.title,
    i.incident_type,
    i.priority,
    i.status,
    e.asset_tag,
    e.name as equipment_name,
    r.name as room_name,
    reporter.full_name as reported_by_name,
    assignee.full_name as assigned_to_name,
    i.created_at
FROM incidents i
JOIN equipment e ON i.equipment_id = e.id
LEFT JOIN rooms r ON e.room_id = r.id
JOIN users reporter ON i.reported_by = reporter.id
LEFT JOIN users assignee ON i.assigned_to = assignee.id
WHERE i.status IN ('open', 'in_progress')
ORDER BY 
    CASE i.priority 
        WHEN 'critical' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
    END,
    i.created_at DESC;

-- Maintenance due alerts
CREATE VIEW maintenance_due AS
SELECT 
    e.id,
    e.asset_tag,
    e.name,
    e.status,
    cd.last_maintenance,
    cd.next_maintenance,
    r.name as room_name,
    DATEDIFF(cd.next_maintenance, CURDATE()) as days_until_due
FROM equipment e
JOIN computer_details cd ON e.id = cd.equipment_id
LEFT JOIN rooms r ON e.room_id = r.id
WHERE cd.next_maintenance IS NOT NULL 
    AND cd.next_maintenance <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND e.status NOT IN ('retired', 'lost', 'stolen')
ORDER BY cd.next_maintenance ASC;