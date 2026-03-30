<?php
class User {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }
    
    public function register($firstName, $lastName, $email, $password, $gender, $role, $address = '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $gender, $role]);
    }
    
    public function authenticate($email, $password) {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, password, role, status, login_attempts FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['status'] === 'inactive') {
                return ['success' => false, 'message' => 'Account is inactive. Please contact administrator.'];
            }
            
            if (password_verify($password, $user['password'])) {
                $this->resetLoginAttempts($user['id']);
                return ['success' => true, 'user' => $user];
            } else {
                $newAttempts = $user['login_attempts'] + 1;
                $this->updateLoginAttempts($user['id'], $newAttempts);
                
                if ($newAttempts >= 3) {
                    $this->updateStatus($user['id'], 'inactive');
                    return ['success' => false, 'message' => 'Account blocked due to too many failed attempts.'];
                }
                
                return ['success' => false, 'message' => 'Invalid password. Attempts remaining: ' . (3 - $newAttempts)];
            }
        }
        
        return ['success' => false, 'message' => 'Email not found.'];
    }
    
    private function resetLoginAttempts($userId) {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    private function updateLoginAttempts($userId, $attempts) {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
        return $stmt->execute([$attempts, $userId]);
    }
    
    public function getAll() {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, gender, role, status, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function search($searchTerm) {
        $searchTerm = "%" . $searchTerm . "%";
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, gender, role, status, created_at FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    public function getById($userId) {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, gender, role, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, password, role, status, created_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    public function update($userId, $firstName, $lastName, $email, $gender, $role) {
        $stmt = $this->db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, gender = ?, role = ? WHERE id = ?");
        return $stmt->execute([$firstName, $lastName, $email, $gender, $role, $userId]);
    }
    
    public function delete($userId) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function updateStatus($userId, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }
}
