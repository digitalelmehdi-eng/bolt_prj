<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Ticket.php';
require_once '../includes/functions.php';

// Check authentication
check_authentication();

if (!has_permission('operator')) {
    json_response(['error' => 'Insufficient permissions'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['desk_id'])) {
        json_response(['error' => 'Desk ID is required'], 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        json_response(['error' => 'Database connection failed'], 500);
    }
    
    $ticket = new Ticket($db);
    $called_ticket = $ticket->call_next($_POST['desk_id'], $_SESSION['user_id']);
    
    if ($called_ticket) {
        json_response([
            'success' => true,
            'ticket' => $called_ticket,
            'message' => 'Ticket called successfully'
        ]);
    } else {
        json_response([
            'success' => false,
            'message' => 'No tickets in queue'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Call ticket error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
?>