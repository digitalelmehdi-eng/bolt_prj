<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $role_id;
    public $status;
    public $created_at;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT u.*, r.name as role_name 
                  FROM " . $this->table_name . " u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.username = ? AND u.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password_hash'])) {
                // Update last login
                $update_query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->execute([$user['id']]);
                
                return $user;
            }
        }
        
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, email, password_hash, first_name, last_name, role_id, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);
        
        return $stmt->execute([
            $this->username,
            $this->email,
            $this->password_hash,
            $this->first_name,
            $this->last_name,
            $this->role_id,
            $this->status
        ]);
    }

    public function read() {
        $query = "SELECT u.*, r.display_name as role_name 
                  FROM " . $this->table_name . " u 
                  JOIN roles r ON u.role_id = r.id 
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function read_single() {
        $query = "SELECT u.*, r.display_name as role_name 
                  FROM " . $this->table_name . " u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        
        return $stmt->fetch();
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username = ?, email = ?, first_name = ?, last_name = ?, 
                      role_id = ?, status = ? 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $this->username,
            $this->email,
            $this->first_name,
            $this->last_name,
            $this->role_id,
            $this->status,
            $this->id
        ]);
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([$this->id]);
    }
}
?>