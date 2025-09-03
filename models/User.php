<?php
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new user
    public function create($organization_id, $username, $email, $password, $first_name, $last_name, $role = 'user') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$organization_id, $username, $email, $password_hash, $first_name, $last_name, $role]);
    }
    
    // Find user by username
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    // Find user by email
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    // Find user by ID
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Authenticate user
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }
    
    // Get all users in an organization
    public function getUsersByOrganization($organization_id) {
        $stmt = $this->pdo->prepare("SELECT id, username, email, first_name, last_name, role, is_active, created_at, last_login 
                                    FROM users WHERE organization_id = ? ORDER BY username");
        $stmt->execute([$organization_id]);
        return $stmt->fetchAll();
    }
    
    // Update last login timestamp
    public function updateLastLogin($user_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$user_id]);
    }
    
    // Get all users (for super admin)
    public function getAllUsers() {
        $stmt = $this->pdo->prepare("SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role, u.is_active, 
                                    u.created_at, u.last_login, o.name as organization_name 
                                    FROM users u LEFT JOIN organizations o ON u.organization_id = o.id 
                                    ORDER BY u.organization_id, u.username");
        $stmt->execute();
        return $stmt->fetchAll();
    }

   // Add these new methods to your existing User class in models/User.php

/**
 * Activates a user account.
 * @param int $user_id The ID of the user to activate.
 * @return bool True on success, false on failure.
 */
public function activate($user_id) {
    $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Finds the administrative user for a given organization.
 * @param int $organization_id The ID of the organization.
 * @return mixed The user record or false if not found.
 */
public function findAdminForOrg($organization_id) {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE organization_id = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$organization_id]);
    return $stmt->fetch();
}

}
?>