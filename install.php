<?php
// Installation script to set up the database and create default users
require_once 'config/config.php';
require_once 'config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        
        // Create default admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $operator_password = password_hash('operator123', PASSWORD_DEFAULT);
        
        // Insert default users
        $users_sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, status) VALUES
                      ('admin', 'admin@company.com', ?, 'System', 'Administrator', 1, 'active'),
                      ('operator', 'operator@company.com', ?, 'Service', 'Operator', 2, 'active')";
        
        $stmt = $db->prepare($users_sql);
        $stmt->execute([$admin_password, $operator_password]);
        
        // Create default desks
        $desks_sql = "INSERT INTO desks (desk_number, desk_name, location, status, services) VALUES
                      ('1', 'Desktop 1', 'Main Hall', 'active', '[1,4]'),
                      ('2', 'Desktop 2', 'Main Hall', 'active', '[2]'),
                      ('3', 'Desktop 3', 'Main Hall', 'active', '[3]'),
                      ('4', 'Desktop 4', 'Main Hall', 'active', '[4]')";
        
        $stmt = $db->prepare($desks_sql);
        $stmt->execute();
        
        $message = 'Installation completed successfully! You can now login with the default credentials.';
        
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Queue Management System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-background min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="card p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cog text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-semibold text-text-primary mb-2">System Installation</h1>
                <p class="text-secondary-500">Set up your Queue Management System</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-success-100 border border-success-200 text-success-600 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Go to Login
                    </a>
                </div>
            <?php elseif ($error): ?>
                <div class="bg-error-100 border border-error-200 text-error-600 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="POST" action="">
                    <div class="space-y-4">
                        <div class="bg-secondary-50 p-4 rounded-lg">
                            <h3 class="font-medium text-text-primary mb-2">Installation Steps:</h3>
                            <ul class="text-sm text-secondary-600 space-y-1">
                                <li>1. Create database tables (run schema.sql first)</li>
                                <li>2. Create default admin and operator users</li>
                                <li>3. Set up default service desks</li>
                                <li>4. Initialize queue status</li>
                            </ul>
                        </div>

                        <div class="bg-warning-100 p-4 rounded-lg">
                            <h3 class="font-medium text-warning-600 mb-2">Prerequisites:</h3>
                            <ul class="text-sm text-warning-600 space-y-1">
                                <li>• MySQL database created</li>
                                <li>• Database schema imported (schema.sql)</li>
                                <li>• Database connection configured</li>
                            </ul>
                        </div>

                        <button type="submit" class="w-full btn btn-primary">
                            <i class="fas fa-play mr-2"></i>
                            Start Installation
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>