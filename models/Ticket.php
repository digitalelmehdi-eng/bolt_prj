<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

class Ticket {
    private $conn;
    private $table_name = "tickets";

    public $id;
    public $ticket_number;
    public $service_id;
    public $customer_name;
    public $customer_phone;
    public $priority_level;
    public $status;
    public $queue_position;
    public $estimated_wait_time;
    public $assigned_desk_id;
    public $notes;
    public $created_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function generate() {
        try {
            $this->conn->beginTransaction();
            
            // Generate ticket number
            $service_query = "SELECT prefix FROM services WHERE id = ?";
            $service_stmt = $this->conn->prepare($service_query);
            $service_stmt->execute([$this->service_id]);
            $service = $service_stmt->fetch();
            
            $this->ticket_number = generate_ticket_number($service['prefix']);
            
            // Calculate queue position
            $position_query = "SELECT COUNT(*) + 1 as position FROM tickets 
                              WHERE service_id = ? AND status IN ('waiting', 'called')";
            $position_stmt = $this->conn->prepare($position_query);
            $position_stmt->execute([$this->service_id]);
            $this->queue_position = $position_stmt->fetch()['position'];
            
            // Calculate estimated wait time
            $this->estimated_wait_time = calculate_wait_time($this->service_id);
            
            // Insert ticket
            $query = "INSERT INTO " . $this->table_name . " 
                      (ticket_number, service_id, customer_name, customer_phone, priority_level, 
                       status, queue_position, estimated_wait_time, created_by) 
                      VALUES (?, ?, ?, ?, ?, 'waiting', ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $this->ticket_number,
                $this->service_id,
                $this->customer_name,
                $this->customer_phone,
                $this->priority_level,
                $this->queue_position,
                $this->estimated_wait_time,
                $this->created_by
            ]);
            
            if ($result) {
                $this->id = $this->conn->lastInsertId();
                
                // Update queue status
                $this->update_queue_status();
                
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollBack();
            return false;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function call_next($desk_id, $operator_id) {
        try {
            $this->conn->beginTransaction();
            
            // Get next ticket in queue
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE status = 'waiting' 
                      ORDER BY priority_level DESC, queue_position ASC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                $this->conn->rollBack();
                return false;
            }
            
            // Update ticket status
            $update_query = "UPDATE " . $this->table_name . " 
                            SET status = 'called', assigned_desk_id = ?, called_at = NOW() 
                            WHERE id = ?";
            
            $update_stmt = $this->conn->prepare($update_query);
            $result = $update_stmt->execute([$desk_id, $ticket['id']]);
            
            if ($result) {
                // Update queue positions
                $this->reorder_queue($ticket['service_id']);
                
                // Update queue status
                $this->update_queue_status();
                
                // Log activity
                log_activity($operator_id, 'CALL_TICKET', 'tickets', $ticket['id']);
                
                $this->conn->commit();
                return $ticket;
            }
            
            $this->conn->rollBack();
            return false;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function complete($operator_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'completed', completed_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$this->id]);
        
        if ($result) {
            log_activity($operator_id, 'COMPLETE_TICKET', 'tickets', $this->id);
            $this->update_queue_status();
        }
        
        return $result;
    }

    public function get_queue_by_service($service_id) {
        $query = "SELECT t.*, s.display_name as service_name, s.prefix 
                  FROM " . $this->table_name . " t
                  JOIN services s ON t.service_id = s.id
                  WHERE t.service_id = ? AND t.status IN ('waiting', 'called')
                  ORDER BY t.priority_level DESC, t.queue_position ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$service_id]);
        return $stmt;
    }

    public function get_current_serving() {
        $query = "SELECT t.*, s.display_name as service_name, s.prefix, d.desk_name
                  FROM " . $this->table_name . " t
                  JOIN services s ON t.service_id = s.id
                  LEFT JOIN desks d ON t.assigned_desk_id = d.id
                  WHERE t.status IN ('called', 'in_progress')
                  ORDER BY t.called_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function update_queue_status() {
        // Update queue status for all services
        $query = "UPDATE queue_status qs
                  SET total_waiting = (
                      SELECT COUNT(*) FROM tickets 
                      WHERE service_id = qs.service_id AND status = 'waiting'
                  ),
                  current_serving_number = (
                      SELECT ticket_number FROM tickets 
                      WHERE service_id = qs.service_id AND status IN ('called', 'in_progress')
                      ORDER BY called_at DESC LIMIT 1
                  )";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    private function reorder_queue($service_id) {
        $query = "SET @pos = 0; 
                  UPDATE " . $this->table_name . " 
                  SET queue_position = (@pos := @pos + 1) 
                  WHERE service_id = ? AND status = 'waiting' 
                  ORDER BY priority_level DESC, generated_at ASC";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$service_id]);
    }
}
?>