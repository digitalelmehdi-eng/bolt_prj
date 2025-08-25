-- Queue Management System Database Schema
-- MySQL 8.0+ Compatible

-- Create database
CREATE DATABASE queue_management_system;
USE queue_management_system;

-- 1. Users table for system authentication and user management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- 2. Roles table for user permissions and access control
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) UNIQUE NOT NULL,
    display_name VARCHAR(50) NOT NULL,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Services table for queue service types
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    prefix CHAR(1) UNIQUE NOT NULL, -- A, B, C, D, E, P
    estimated_duration INT DEFAULT 10, -- in minutes
    is_priority BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    icon_class VARCHAR(50), -- Font Awesome class
    color_scheme VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Service desks/counters table
CREATE TABLE desks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    desk_number VARCHAR(10) UNIQUE NOT NULL,
    desk_name VARCHAR(50) NOT NULL,
    location VARCHAR(100),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    current_operator_id INT NULL,
    services JSON, -- Array of service IDs this desk can handle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Tickets table for queue tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(10) UNIQUE NOT NULL,
    service_id INT NOT NULL,
    customer_name VARCHAR(100) NULL,
    customer_phone VARCHAR(20) NULL,
    priority_level ENUM('normal', 'priority', 'urgent') DEFAULT 'normal',
    status ENUM('waiting', 'called', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'waiting',
    queue_position INT NOT NULL,
    estimated_wait_time INT, -- in minutes
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    assigned_desk_id INT NULL,
    notes TEXT,
    created_by INT NULL, -- operator who manually created ticket
    INDEX idx_status (status),
    INDEX idx_service_status (service_id, status),
    INDEX idx_generated_date (generated_at)
);

-- 6. Queue status table for real-time queue management
CREATE TABLE queue_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    current_serving_number VARCHAR(10),
    total_waiting INT DEFAULT 0,
    average_wait_time INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_service (service_id)
);

-- 7. System settings table for configuration
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT
);

-- 8. Activity logs table for audit trail
CREATE TABLE activity_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_table_record (table_name, record_id)
);

-- 9. Announcements table for system messages
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    target_audience ENUM('all', 'customers', 'operators', 'admins') DEFAULT 'all',
    is_active BOOLEAN DEFAULT TRUE,
    display_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    display_until TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Statistics table for reporting and analytics
CREATE TABLE daily_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    service_id INT NOT NULL,
    total_tickets INT DEFAULT 0,
    completed_tickets INT DEFAULT 0,
    cancelled_tickets INT DEFAULT 0,
    no_show_tickets INT DEFAULT 0,
    average_wait_time DECIMAL(5,2) DEFAULT 0,
    average_service_time DECIMAL(5,2) DEFAULT 0,
    peak_hour_start TIME,
    peak_hour_end TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_service (date, service_id)
);

-- Foreign Key Constraints
ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id);
ALTER TABLE desks ADD FOREIGN KEY (current_operator_id) REFERENCES users(id);
ALTER TABLE tickets ADD FOREIGN KEY (service_id) REFERENCES services(id);
ALTER TABLE tickets ADD FOREIGN KEY (assigned_desk_id) REFERENCES desks(id);
ALTER TABLE tickets ADD FOREIGN KEY (created_by) REFERENCES users(id);
ALTER TABLE queue_status ADD FOREIGN KEY (service_id) REFERENCES services(id);
ALTER TABLE system_settings ADD FOREIGN KEY (updated_by) REFERENCES users(id);
ALTER TABLE activity_logs ADD FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE announcements ADD FOREIGN KEY (created_by) REFERENCES users(id);
ALTER TABLE daily_statistics ADD FOREIGN KEY (service_id) REFERENCES services(id);

-- Insert default roles
INSERT INTO roles (name, display_name, description) VALUES
('admin', 'Administrator', 'Full system access and management'),
('operator', 'Operator', 'Service desk operator with queue management access'),
('supervisor', 'Supervisor', 'Department supervisor with reporting access'),
('customer', 'Customer', 'Limited access for ticket generation');

-- Insert default services based on your system
INSERT INTO services (name, display_name, description, prefix, estimated_duration, is_priority, icon_class, color_scheme) VALUES
('general', 'General Service', 'Account inquiries, general banking, and customer support', 'A', 10, FALSE, 'fas fa-user-tie', 'primary'),
('account', 'Account Opening', 'New account setup, documentation, and verification', 'B', 20, FALSE, 'fas fa-user-plus', 'success'),
('loan', 'Loan Services', 'Personal loans, mortgages, and loan consultations', 'C', 15, FALSE, 'fas fa-hand-holding-usd', 'accent'),
('support', 'Customer Support', 'Complaints, feedback, and technical assistance', 'D', 8, FALSE, 'fas fa-headset', 'error'),
('cash', 'Cash Services', 'Deposits, withdrawals, and cash transactions', 'E', 5, FALSE, 'fas fa-money-bill-wave', 'success'),
('priority', 'Priority Service', 'Senior citizens, disabled persons, and VIP customers', 'P', 12, TRUE, 'fas fa-star', 'accent');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('max_queue_size', '100', 'number', 'Maximum tickets per service queue'),
('ticket_expiry_hours', '2', 'number', 'Hours after which unserved tickets expire'),
('auto_call_interval', '30', 'number', 'Seconds between automatic ticket calls'),
('priority_multiplier', '0.5', 'number', 'Priority ticket wait time multiplier'),
('display_refresh_rate', '5', 'number', 'Queue display refresh rate in seconds'),
('enable_sms_notifications', 'false', 'boolean', 'Send SMS notifications to customers'),
('business_hours_start', '09:00', 'string', 'Business hours start time'),
('business_hours_end', '17:00', 'string', 'Business hours end time');

-- Initialize queue status for all services
INSERT INTO queue_status (service_id, current_serving_number, total_waiting, average_wait_time)
SELECT id, CONCAT(prefix, '001'), 0, estimated_duration FROM services;