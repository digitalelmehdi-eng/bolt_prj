<?php
// Utility Functions

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_ticket_number($service_prefix) {
    // Get next number for this service
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT MAX(CAST(SUBSTRING(ticket_number, 2) AS UNSIGNED)) as max_num 
              FROM tickets 
              WHERE ticket_number LIKE ? 
              AND DATE(generated_at) = CURDATE()";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$service_prefix . '%']);
    $result = $stmt->fetch();
    
    $next_num = ($result['max_num'] ?? 0) + 1;
    return $service_prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}

function calculate_wait_time($service_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Count waiting tickets for this service
    $query = "SELECT COUNT(*) as waiting_count FROM tickets 
              WHERE service_id = ? AND status = 'waiting'";
    $stmt = $db->prepare($query);
    $stmt->execute([$service_id]);
    $waiting_count = $stmt->fetch()['waiting_count'];
    
    // Get average service time
    $query = "SELECT estimated_duration FROM services WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$service_id]);
    $service_time = $stmt->fetch()['estimated_duration'];
    
    return $waiting_count * $service_time;
}

function log_activity($user_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $user_id,
        $action,
        $table_name,
        $record_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function check_authentication() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

function has_permission($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $role_hierarchy = ['customer' => 1, 'operator' => 2, 'supervisor' => 3, 'admin' => 4];
    $user_level = $role_hierarchy[$_SESSION['role']] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>