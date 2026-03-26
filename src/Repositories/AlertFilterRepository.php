<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\AlertFilter;
use PDO;

final class AlertFilterRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(array $data): AlertFilter
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO alert_filters (email, keywords, locations, contract_types)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            json_encode($data['keywords'] ?? []),
            json_encode($data['locations'] ?? []),
            json_encode($data['contract_types'] ?? []),
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM alert_filters ORDER BY created_at DESC'
        );
        $stmt->execute();

        return array_map(AlertFilter::fromArray(...), $stmt->fetchAll());
    }

    public function findActive(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM alert_filters WHERE is_active = 1 ORDER BY created_at DESC'
        );
        $stmt->execute();

        return array_map(AlertFilter::fromArray(...), $stmt->fetchAll());
    }

    public function findById(int $id): AlertFilter
    {
        $stmt = $this->pdo->prepare('SELECT * FROM alert_filters WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return AlertFilter::fromArray($row);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM alert_filters WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
