<?php
class Permission {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Grant permission to a user for a folder
    public function grant($user_id, $folder_id, $permission_level, $granted_by) {
        $stmt = $this->pdo->prepare("INSERT INTO permissions (user_id, folder_id, permission_level, granted_by) 
                                    VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE permission_level = ?");
        return $stmt->execute([$user_id, $folder_id, $permission_level, $granted_by, $permission_level]);
    }
    
    // Revoke permission
    public function revoke($user_id, $folder_id) {
        $stmt = $this->pdo->prepare("DELETE FROM permissions WHERE user_id = ? AND folder_id = ?");
        return $stmt->execute([$user_id, $folder_id]);
    }
    
    // Check if user has permission for a folder
    public function hasPermission($user_id, $folder_id, $required_level = 'read') {
        $stmt = $this->pdo->prepare("SELECT permission_level FROM permissions WHERE user_id = ? AND folder_id = ?");
        $stmt->execute([$user_id, $folder_id]);
        $permission = $stmt->fetch();
        
        if (!$permission) {
            return false;
        }
        
        // Check permission level hierarchy
        $levels = ['read' => 1, 'write' => 2, 'admin' => 3];
        return $levels[$permission['permission_level']] >= $levels[$required_level];
    }
    
    // Get user's permission level for a folder
    public function getPermissionLevel($user_id, $folder_id) {
        $stmt = $this->pdo->prepare("SELECT permission_level FROM permissions WHERE user_id = ? AND folder_id = ?");
        $stmt->execute([$user_id, $folder_id]);
        $permission = $stmt->fetch();
        
        return $permission ? $permission['permission_level'] : null;
    }
    
    // Get all permissions for a folder
    public function getPermissionsForFolder($folder_id) {
        $stmt = $this->pdo->prepare("SELECT p.*, u.username, u.first_name, u.last_name FROM permissions p 
                                    JOIN users u ON p.user_id = u.id WHERE p.folder_id = ?");
        $stmt->execute([$folder_id]);
        return $stmt->fetchAll();
    }
    
    // Get all permissions granted by a user
    public function getPermissionsGrantedBy($user_id) {
        $stmt = $this->pdo->prepare("SELECT p.*, u.username as granted_to_username, f.name as folder_name 
                                    FROM permissions p JOIN users u ON p.user_id = u.id 
                                    JOIN folders f ON p.folder_id = f.id WHERE p.granted_by = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}
?>