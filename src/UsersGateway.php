<?php

class UsersGateway 
{
    private PDO $pdo;

    public function __construct(Database $database)
    {
        $this->pdo = $database->getConnection();
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, email, username, employ_code, roles_id FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email, username, employ_code, roles_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function createUser(array $data): int
    {
          $stmt = $this->pdo->prepare("INSERT INTO users (name, email, username, employ_code, roles_id, password) VALUES (:name, :email, :username, :employ_code, :roles_id, :password)");

          $stmt -> bindValue(':name', $data['name'], PDO::PARAM_STR);
          $stmt -> bindValue(':email', $data['email'], PDO::PARAM_STR);
          $stmt -> bindValue(':username', $data['username'], PDO::PARAM_STR);
          $stmt -> bindValue(':employ_code', $data['employ_code'], PDO::PARAM_STR);
          $stmt -> bindValue(':roles_id', $data['roles_id'] ?? 2, PDO::PARAM_INT);
          $stmt -> bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT), PDO::PARAM_STR);
          $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    public function updateUser(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET name = :name, email = :email, username = :username, employ_code = :employ_code, roles_id = :roles_id WHERE id = :id");

        $stmt -> bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt -> bindValue(':email', $data['email'], PDO::PARAM_STR);
        $stmt -> bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt -> bindValue(':employ_code', $data['employ_code'], PDO::PARAM_STR);
        $stmt -> bindValue(':roles_id', $data['roles_id'] ?? null, PDO::PARAM_INT);
        $stmt -> bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function userExists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    //authenticate user
    public function authenticateUser(string $username, string $password): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }
}