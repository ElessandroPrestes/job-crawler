<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\CrawlLog;
use PDO;

final class CrawlLogRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function start(string $source, ?string $keyword, ?string $location): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crawl_logs (source, keyword, location, status, started_at)
             VALUES (?, ?, ?, \'running\', ?)'
        );
        $stmt->execute([$source, $keyword, $location, date('Y-m-d H:i:s')]);

        return (int) $this->pdo->lastInsertId();
    }

    public function finish(int $id, string $status, int $jobsFound, int $jobsNew, ?string $errorMsg = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE crawl_logs
             SET status = ?, jobs_found = ?, jobs_new = ?, error_msg = ?, finished_at = ?
             WHERE id = ?'
        );
        $stmt->execute([$status, $jobsFound, $jobsNew, $errorMsg, date('Y-m-d H:i:s'), $id]);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['source'])) {
            $where[]  = 'source = ?';
            $params[] = $filters['source'];
        }

        $sql = 'SELECT * FROM crawl_logs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY started_at DESC LIMIT ? OFFSET ?';

        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(CrawlLog::fromArray(...), $stmt->fetchAll());
    }

    public function count(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['source'])) {
            $where[]  = 'source = ?';
            $params[] = $filters['source'];
        }

        $sql = 'SELECT COUNT(*) FROM crawl_logs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}