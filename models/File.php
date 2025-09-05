<?php
class File {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Upload a new file
    public function upload($folder_id, $organization_id, $name, $file_path, $file_size, $mime_type, $uploaded_by) {
        $stmt = $this->pdo->prepare("INSERT INTO files (folder_id, organization_id, name, file_path, file_size, 
                                    mime_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$folder_id, $organization_id, $name, $file_path, $file_size, $mime_type, $uploaded_by]);
    }
    
    // Find file by ID
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Get files by folder
    public function getFilesByFolder($folder_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY name");
        $stmt->execute([$folder_id]);
        return $stmt->fetchAll();
    }
    
    // Get files by organization
    public function getFilesByOrganization($organization_id) {
        $stmt = $this->pdo->prepare("SELECT f.*, u.username as uploaded_by_username FROM files f 
                                    JOIN users u ON f.uploaded_by = u.id WHERE f.organization_id = ? ORDER BY f.name");
        $stmt->execute([$organization_id]);
        return $stmt->fetchAll();
    }

    /**
     * Gets the distribution of file types across the entire system.
     * @return array An array of file types and their counts.
     */
    public function getFileTypeDistribution() {
        $sql = "SELECT 
                    SUBSTRING_INDEX(name, '.', -1) as extension, 
                    COUNT(id) as count
                FROM files
                GROUP BY extension
                ORDER BY count DESC
                LIMIT 10"; // Get top 10 file types
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Delete file
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM files WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Log file download
    public function logDownload($file_id, $user_id, $ip_address) {
        $stmt = $this->pdo->prepare("INSERT INTO file_download_logs (file_id, user_id, ip_address) VALUES (?, ?, ?)");
        return $stmt->execute([$file_id, $user_id, $ip_address]);
    }
    
    // Log folder access
    public function logFolderAccess($folder_id, $user_id, $ip_address) {
        $stmt = $this->pdo->prepare("INSERT INTO folder_access_logs (folder_id, user_id, ip_address) VALUES (?, ?, ?)");
        return $stmt->execute([$folder_id, $user_id, $ip_address]);
    }
}
?>
