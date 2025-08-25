# Queue Management System - PHP Backend

This is the PHP/MySQL backend for the Queue Management System.

## Installation Instructions

### 1. Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

### 2. Database Setup
1. Create a MySQL database named `queue_management_system`
2. Import the schema:
   ```bash
   mysql -u root -p queue_management_system < schema.sql
   ```

### 3. Configuration
1. Update database credentials in `config/database.php`
2. Modify `config/config.php` for your environment
3. Set proper file permissions:
   ```bash
   chmod 755 pages/
   chmod 644 config/*.php
   chmod 644 models/*.php
   ```

### 4. Installation
1. Navigate to `http://yoursite.com/install.php`
2. Click "Start Installation" to create default users and data
3. Login with default credentials:
   - **Admin**: admin / admin123
   - **Operator**: operator / operator123

### 5. File Structure
```
/
├── config/
│   ├── database.php      # Database connection
│   └── config.php        # App configuration
├── models/
│   ├── User.php          # User model
│   ├── Service.php       # Service model
│   └── Ticket.php        # Ticket model
├── api/
│   ├── generate_ticket.php
│   ├── call_next_ticket.php
│   └── get_queue_status.php
├── includes/
│   └── functions.php     # Utility functions
├── pages/               # Your existing HTML pages (convert to PHP)
├── login.php           # Login page
├── logout.php          # Logout handler
└── install.php         # Installation script
```

## Converting HTML Pages to PHP

To make your existing HTML pages dynamic, you need to:

### 1. Rename files from .html to .php
```bash
mv pages/admin_dashboard.html pages/admin_dashboard.php
mv pages/operator_dashboard.html pages/operator_dashboard.php
# etc...
```

### 2. Add PHP authentication to each page
Add this at the top of each PHP page:
```php
<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check authentication
check_authentication();

// Check role permissions (adjust as needed)
if (!has_permission('operator')) {
    header('Location: ../login.php');
    exit();
}
?>
```

### 3. Replace static data with dynamic PHP
Example for customer ticket generation:
```php
<?php
// Get services from database
require_once '../models/Service.php';
$database = new Database();
$db = $database->getConnection();
$service = new Service($db);
$services_result = $service->get_queue_status();
?>

<!-- Then in HTML -->
<?php while ($service_data = $services_result->fetch()): ?>
<div class="card" onclick="selectService('<?php echo $service_data['id']; ?>', '<?php echo $service_data['prefix']; ?>')">
    <h3><?php echo htmlspecialchars($service_data['display_name']); ?></h3>
    <p>People Waiting: <?php echo $service_data['current_queue_count']; ?></p>
    <!-- etc... -->
</div>
<?php endwhile; ?>
```

### 4. Update JavaScript to use AJAX
Replace static JavaScript with API calls:
```javascript
// Generate ticket
function selectService(serviceId, prefix) {
    fetch('/api/generate-ticket', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            service_id: serviceId,
            priority_level: 'normal'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTicketModal(data.ticket);
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// Real-time updates
setInterval(() => {
    fetch('/api/queue-status')
        .then(response => response.json())
        .then(data => {
            updateQueueDisplay(data);
        });
}, 5000);
```

## API Endpoints

- `POST /api/generate-ticket` - Generate new ticket
- `POST /api/call-next` - Call next ticket in queue
- `GET /api/queue-status` - Get current queue status
- `POST /api/complete-ticket` - Mark ticket as completed

## Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session management with timeout
- Role-based access control
- Activity logging for audit trail

## Customization

1. **Add new services**: Use the admin interface or directly in database
2. **Modify queue logic**: Edit `models/Ticket.php`
3. **Add new user roles**: Update `roles` table and permission checks
4. **Customize UI**: Modify the existing CSS/HTML structure

## Troubleshooting

1. **Database connection issues**: Check credentials in `config/database.php`
2. **Permission errors**: Ensure proper file permissions
3. **Session issues**: Check PHP session configuration
4. **API errors**: Check error logs and enable PHP error reporting

## Production Deployment

1. Disable error reporting in production
2. Use HTTPS for security
3. Set up proper backup procedures
4. Configure log rotation
5. Use environment variables for sensitive config