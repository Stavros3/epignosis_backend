<?php

class VacationGateway 
{
    private PDO $pdo;

    public function __construct(Database $database)
    {
        $this->pdo = $database->getConnection();
    }

    /**
     * Get all vacations (admin only) - ordered by status (pending first)
     */
    public function getAllVacations(): array
    {
        $stmt = $this->pdo->query(
            "SELECT v.*, u.name as user_name, u.username, vs.status as status_name 
             FROM vacations v
             LEFT JOIN users u ON v.user_id = u.id
             LEFT JOIN vacations_status vs ON v.status_id = vs.id
             ORDER BY v.status_id DESC, v.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get vacations by user ID
     */
    public function getVacationsByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*, vs.status as status_name 
             FROM vacations v
             LEFT JOIN vacations_status vs ON v.status_id = vs.id
             WHERE v.user_id = :user_id
             ORDER BY v.created_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single vacation by ID
     */
    public function getVacationById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*, u.name as user_name, u.username, vs.status as status_name 
             FROM vacations v
             LEFT JOIN users u ON v.user_id = u.id
             LEFT JOIN vacations_status vs ON v.status_id = vs.id
             WHERE v.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $vacation = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vacation ?: null;
    }

    /**
     * Create new vacation request
     */
    public function createVacation(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO vacations (user_id, date_from, date_to, reason, status_id, created_at, updated_at) 
             VALUES (:user_id, :date_from, :date_to, :reason, :status_id, NOW(), NOW())"
        );

        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':date_from', $data['date_from'], PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $data['date_to'], PDO::PARAM_STR);
        $stmt->bindValue(':reason', $data['reason'], PDO::PARAM_STR);
        $stmt->bindValue(':status_id', VacationStatus::PENDING->value, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update vacation status (admin only)
     */
    public function updateVacationStatus(int $id, int $statusId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE vacations 
             SET status_id = :status_id, updated_at = NOW() 
             WHERE id = :id"
        );

        $stmt->bindValue(':status_id', $statusId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Update vacation details (user can update their own pending vacations)
     */
    public function updateVacation(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE vacations 
             SET date_from = :date_from, date_to = :date_to, reason = :reason, updated_at = NOW() 
             WHERE id = :id"
        );

        $stmt->bindValue(':date_from', $data['date_from'], PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $data['date_to'], PDO::PARAM_STR);
        $stmt->bindValue(':reason', $data['reason'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete vacation
     */
    public function deleteVacation(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM vacations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Check if vacation exists
     */
    public function vacationExists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vacations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if user owns the vacation
     */
    public function isVacationOwner(int $vacationId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vacations WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $vacationId, 'user_id' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get vacation status
     */
    public function getVacationStatus(int $vacationId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT status_id FROM vacations WHERE id = :id");
        $stmt->execute(['id' => $vacationId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    /**
     * Get all vacation statuses
     */
    public function getAllStatuses(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM vacations_status ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
