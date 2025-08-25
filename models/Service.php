<?php
require_once 'config/database.php';

class Service {
    private $conn;
    private $table_name = "services";

    public $id;
    public $name;
    public $display_name;
    public $description;
    public $prefix;
    public $estimated_duration;
    public $is_priority;
    public $status;
    public $icon_class;
    public $color_scheme;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'active' ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function read_single() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        return $stmt->fetch();
    }

    public function get_queue_status() {
        $query = "SELECT s.*, qs.current_serving_number, qs.total_waiting, qs.average_wait_time,
                  COUNT(t.id) as current_queue_count
                  FROM " . $this->table_name . " s
                  LEFT JOIN queue_status qs ON s.id = qs.service_id
                  LEFT JOIN tickets t ON s.id = t.service_id AND t.status = 'waiting'
                  WHERE s.status = 'active'
                  GROUP BY s.id
                  ORDER BY s.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, display_name, description, prefix, estimated_duration, is_priority, status, icon_class, color_scheme) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $this->name,
            $this->display_name,
            $this->description,
            $this->prefix,
            $this->estimated_duration,
            $this->is_priority,
            $this->status,
            $this->icon_class,
            $this->color_scheme
        ]);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = ?, display_name = ?, description = ?, estimated_duration = ?, 
                      is_priority = ?, status = ?, icon_class = ?, color_scheme = ? 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $this->name,
            $this->display_name,
            $this->description,
            $this->estimated_duration,
            $this->is_priority,
            $this->status,
            $this->icon_class,
            $this->color_scheme,
            $this->id
        ]);
    }
}
?>