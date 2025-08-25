<?php
// Application Configuration
define('APP_NAME', 'Queue Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/queue-management');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'queue_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Queue Configuration
define('MAX_QUEUE_SIZE', 100);
define('TICKET_EXPIRY_HOURS', 2);
define('AUTO_CALL_INTERVAL', 30);

// Timezone
date_default_timezone_set('UTC');

// Start session
session_start();
?>