<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Ticket.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_response(['error' => 'Invalid JSON input'], 400);
    }
    
    // Validate required fields
    if (!isset($input['service_id']) || empty($input['service_id'])) {
        json_response(['error' => 'Service ID is required'], 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        json_response(['error' => 'Database connection failed'], 500);
    }
    
    $ticket = new Ticket($db);
    $ticket->service_id = sanitize_input($input['service_id']);
    $ticket->customer_name = isset($input['customer_name']) ? sanitize_input($input['customer_name']) : null;
    $ticket->customer_phone = isset($input['customer_phone']) ? sanitize_input($input['customer_phone']) : null;
    $ticket->priority_level = isset($input['priority_level']) ? sanitize_input($input['priority_level']) : 'normal';
    $ticket->created_by = $_SESSION['user_id'] ?? null;
    
    if ($ticket->generate()) {
        // Get the generated ticket details
        $ticket_query = "SELECT t.*, s.display_name as service_name, s.prefix 
                        FROM tickets t 
                        JOIN services s ON t.service_id = s.id 
                        WHERE t.id = ?";
        $stmt = $db->prepare($ticket_query);
        $stmt->execute([$ticket->id]);
        $ticket_data = $stmt->fetch();
        
        json_response([
            'success' => true,
            'ticket' => $ticket_data,
            'message' => 'Ticket generated successfully'
        ]);
    } else {
        json_response(['error' => 'Failed to generate ticket'], 500);
    }
    
} catch (Exception $e) {
    error_log('Ticket generation error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
?>