<?php
class Organization {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new organization
    public function create($name, $description = '', $requested_by = null) {
        $stmt = $this->pdo->prepare("INSERT INTO organizations (name, description, requested_by) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $description, $requested_by])) {
            return $this->pdo->lastInsertId();
        }
        return false;
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
        $stmt = $this->pdo->prepare("SELECT o.*, u.username as approved_by_username 
                                    FROM organizations o 
                                    LEFT JOIN users u ON o.approved_by = u.id 
                                    ORDER BY o.name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Gathers statistics for all organizations, including user count and storage usage.
     * @return array An array of organizations with their stats.
     */
    public function getOrganizationStats() {
        $sql = "SELECT 
                    o.id, 
                    o.name, 
                    COUNT(DISTINCT u.id) as user_count, 
                    COALESCE(SUM(f.file_size), 0) as storage_used
                FROM organizations o
                LEFT JOIN users u ON o.id = u.organization_id
                LEFT JOIN files f ON o.id = f.organization_id
                WHERE o.approved = 1
                GROUP BY o.id, o.name
                ORDER BY o.name";
        $stmt = $this->pdo->prepare($sql);
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
        $stmt = $this->pdo->prepare("SELECT o.*, u.username as requested_by_username
                                    FROM organizations o
                                    LEFT JOIN users u ON o.requested_by = u.id
                                    WHERE o.approved = FALSE ORDER BY o.created_at");
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

