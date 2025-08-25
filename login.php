<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'includes/functions.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $user = new User($db);
            $user_data = $user->login($username, $password);
            
            if ($user_data) {
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['role'] = $user_data['role_name'];
                $_SESSION['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                $_SESSION['last_activity'] = time();
                
                // Redirect based on role
                switch ($user_data['role_name']) {
                    case 'admin':
                        header('Location: pages/admin_dashboard.php');
                        break;
                    case 'operator':
                        header('Location: pages/operator_dashboard.php');
                        break;
                    default:
                        header('Location: pages/customer_ticket_generation.php');
                }
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } else {
            $error_message = 'Database connection failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Queue Management System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-background min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="card p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users-cog text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-semibold text-text-primary mb-2">Queue Management System</h1>
                <p class="text-secondary-500">Please sign in to continue</p>
            </div>

            <!-- Error Message -->
            <?php if ($error_message): ?>
                <div class="bg-error-100 border border-error-200 text-error-600 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-text-primary mb-2">Username</label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-text-primary mb-2">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="form-input pr-10" placeholder="Enter your password" required>
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-secondary-400"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full btn btn-primary">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-8 p-4 bg-secondary-50 rounded-lg">
                <h3 class="text-sm font-medium text-text-primary mb-2">Demo Credentials:</h3>
                <div class="text-xs text-secondary-600 space-y-1">
                    <p><strong>Admin:</strong> admin / admin123</p>
                    <p><strong>Operator:</strong> operator / operator123</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>