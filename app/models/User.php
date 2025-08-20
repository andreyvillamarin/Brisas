<?php

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT id, username, email, role FROM users ORDER BY username ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all users: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user by ID: " . $e->getMessage());
            return false;
        }
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)";
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':role' => $data['role']
            ];
            
            $stmt->execute($params);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $params = ['id' => $id, 'username' => $data['username'], 'email' => $data['email'], 'role' => $data['role']];
            $sql = "UPDATE users SET username = :username, email = :email, role = :role";

            if (!empty($data['password'])) {
                $sql .= ", password_hash = :password_hash";
                $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function update2FASecret($id, $secret) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET google_2fa_secret = :secret WHERE id = :id");
            return $stmt->execute(['id' => $id, 'secret' => $secret]);
        } catch (PDOException $e) {
            error_log("Error updating 2FA secret: " . $e->getMessage());
            return false;
        }
    }

    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return false;
        }
    }
    
    // Aquí irán métodos para crear, editar, etc.
}
?>