<?php
class Folder {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new folder
    public function create($organization_id, $parent_folder_id, $name, $description, $created_by, $password = null) {
        $password_hash = null;
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO folders (organization_id, parent_folder_id, name, description, 
                                    created_by, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$organization_id, $parent_folder_id, $name, $description, $created_by, $password_hash]);
    }
    
    // Find folder by ID
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Get folders by organization
    public function getFoldersByOrganization($organization_id, $parent_folder_id = null) {
        if ($parent_folder_id === null) {
            $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE organization_id = ? AND parent_folder_id IS NULL 
                                        ORDER BY name");
            $stmt->execute([$organization_id]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE organization_id = ? AND parent_folder_id = ? 
                                        ORDER BY name");
            $stmt->execute([$organization_id, $parent_folder_id]);
        }
        return $stmt->fetchAll();
    }
    
/**
     * Get all folders for a specific organization, regardless of parent.
     * This is used to build a lookup map for performance.
     *
     * @param int $organization_id The ID of the organization.
     * @return array A list of all folders.
     */
    public function getAllFoldersByOrganization($organization_id) {
        // Prepares the SQL statement to select all folders for the given organization.
        $stmt = $this->pdo->prepare("SELECT id, name FROM folders WHERE organization_id = ?");
        
        // Executes the statement with the provided organization ID.
        $stmt->execute([$organization_id]);
        
        // Returns all matching folders as an associative array.
        return $stmt->fetchAll();
    }

    // Get subfolders of a folder
    public function getSubfolders($folder_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE parent_folder_id = ? ORDER BY name");
        $stmt->execute([$folder_id]);
        return $stmt->fetchAll();
    }
    
    // Check if folder has password protection
    public function isPasswordProtected($folder_id) {
        $folder = $this->findById($folder_id);
        return $folder && !empty($folder['password_hash']);
    }
    
    // Verify folder password
    public function verifyPassword($folder_id, $password) {
        $folder = $this->findById($folder_id);
        if ($folder && !empty($folder['password_hash'])) {
            return password_verify($password, $folder['password_hash']);
        }
        return false;
    }
    
    // Update folder
    public function update($id, $name, $description) {
        $stmt = $this->pdo->prepare("UPDATE folders SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?");
        return $stmt->execute([$name, $description, $id]);
    }
    
    // Delete folder
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM folders WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Get folder path (breadcrumb)
    public function getFolderPath($folder_id) {
        $path = [];
        $current_id = $folder_id;
        
        while ($current_id) {
            $folder = $this->findById($current_id);
            if (!$folder) break;
            
            array_unshift($path, [
                'id' => $folder['id'],
                'name' => $folder['name']
            ]);
            
            $current_id = $folder['parent_folder_id'];
        }
        
        return $path;
    }
}
?>