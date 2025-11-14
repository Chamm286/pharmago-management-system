<?php
// models/Branch.php

class Branch {
    private $conn;
    private $table_name = "branches";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy tất cả chi nhánh đang hoạt động
    public function getAllActiveBranches() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE is_active = 1 
                      ORDER BY display_order ASC, branch_name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getAllActiveBranches: " . $e->getMessage());
            return [];
        }
    }

    // Lấy chi nhánh theo ID
    public function getBranchById($branch_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE branch_id = :branch_id AND is_active = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':branch_id', $branch_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getBranchById: " . $e->getMessage());
            return null;
        }
    }

    // Lấy chi nhánh chính
    public function getMainBranch() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE is_active = 1 
                      ORDER BY display_order ASC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMainBranch: " . $e->getMessage());
            return null;
        }
    }
}
?>