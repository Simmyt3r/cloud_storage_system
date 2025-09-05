<?php
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new user
    public function create($organization_id, $username, $email, $password, $first_name, $last_name, $role = 'user', $is_active = 0) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role, is_active) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$organization_id, $username, $email, $password_hash, $first_name, $last_name, $role, $is_active])) {
            return $this->pdo->lastInsertId();
        }
        return false;
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

    /**
     * Updates a user's details. Can handle partial updates.
     *
     * @param int $user_id The user ID.
     * @param array $data Associative array of data to update.
     * @return bool
     */
    public function update($user_id, $data) {
        $fields = [];
        $params = [];

        if (isset($data['first_name'])) { $fields[] = 'first_name = ?'; $params[] = $data['first_name']; }
        if (isset($data['last_name'])) { $fields[] = 'last_name = ?'; $params[] = $data['last_name']; }
        if (isset($data['email'])) { $fields[] = 'email = ?'; $params[] = $data['email']; }
        if (!empty($data['password'])) { $fields[] = 'password_hash = ?'; $params[] = password_hash($data['password'], PASSWORD_DEFAULT); }
        if (isset($data['role'])) { $fields[] = 'role = ?'; $params[] = $data['role']; }
        if (isset($data['organization_id'])) { $fields[] = 'organization_id = ?'; $params[] = $data['organization_id']; }
        if (isset($data['is_active'])) { $fields[] = 'is_active = ?'; $params[] = $data['is_active']; }
        
        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
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
                                    ORDER BY o.name, u.username");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Activates a user account.
     * @param int $user_id The ID of the user to activate.
     * @return bool True on success, false on failure.
     */
    public function activate($user_id) {
        return $this->update($user_id, ['is_active' => 1]);
    }

    /**
     * Updates a user's role.
     * @param int $user_id The ID of the user to update.
     * @param string $role The new role.
     * @return bool
     */
    public function updateUserRole($user_id, $role) {
        return $this->update($user_id, ['role' => $role]);
    }

    /**
     * Deletes a user from the database.
     * @param int $user_id The ID of the user to delete.
     * @return bool
     */
    public function delete($user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
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

    /**
     * Generates a password reset token for a user.
     * @param string $email The user's email address.
     * @return string|false The token on success, or false on failure.
     */
    public function generatePasswordResetToken(string $email) {
        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        try {
            $token = bin2hex(random_bytes(32));
            $expires = new DateTime('+1 hour');
            $expires_str = $expires->format('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
            if ($stmt->execute([$token, $expires_str, $user['id']])) {
                return $token;
            }
            return false;
        } catch (Exception $e) {
            error_log("Token generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds a user by a valid (non-expired) password reset token.
     * @param string $token The reset token.
     * @return mixed The user record or false if not found/expired.
     */
    public function findUserByResetToken(string $token) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Updates a user's password and clears the reset token.
     * @param string $token The reset token.
     * @param string $newPassword The new password.
     * @return bool True on success, false on failure.
     */
    public function resetPassword(string $token, string $newPassword) {
        $user = $this->findUserByResetToken($token);
        if (!$user) {
            return false; // Token is invalid or expired
        }

        $new_password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
        return $stmt->execute([$new_password_hash, $user['id']]);
    }
}
?>

