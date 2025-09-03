<?php
class Organization {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new organization
    public function create($name, $description = '') {
        $stmt = $this->pdo->prepare("INSERT INTO organizations (name, description) VALUES (?, ?)");
        return $stmt->execute([$name, $description]);
    }
    
    // Find organization by ID
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Find organization by name
    public function findByName($name) {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }
    
    // Get all organizations
    public function getAll() {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Get all approved organizations
    public function getApproved() {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE approved = TRUE ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Get all pending organizations
    public function getPending() {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE approved = FALSE ORDER BY created_at");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Approve an organization
    public function approve($id, $approved_by) {
        $stmt = $this->pdo->prepare("UPDATE organizations SET approved = TRUE, approved_at = CURRENT_TIMESTAMP, 
                                    approved_by = ? WHERE id = ?");
        return $stmt->execute([$approved_by, $id]);
    }
    
    // Reject/Delete an organization
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM organizations WHERE id = ?");
        return $stmt->execute([$id]);
    }

    

}
?>