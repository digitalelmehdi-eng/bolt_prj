<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Service.php';
require_once '../models/Ticket.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        json_response(['error' => 'Database connection failed'], 500);
    }
    
    $service = new Service($db);
    $services_result = $service->get_queue_status();
    
    $services = [];
    while ($row = $services_result->fetch()) {
        $services[] = $row;
    }
    
    // Get currently serving tickets
    $ticket = new Ticket($db);
    $serving_result = $ticket->get_current_serving();
    
    $currently_serving = [];
    while ($row = $serving_result->fetch()) {
        $currently_serving[] = $row;
    }
    
    json_response([
        'success' => true,
        'services' => $services,
        'currently_serving' => $currently_serving,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('Queue status error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
?>