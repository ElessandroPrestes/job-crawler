<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\Job;
use PDO;

final class JobRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[]              = '(title LIKE ? OR company LIKE ? OR requirements LIKE ?)';
            $like                 = '%' . $filters['keyword'] . '%';
            $params[]             = $like;
            $params[]             = $like;
            $params[]             = $like;
        }

        if (!empty($filters['location'])) {
            $where[]  = 'location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['source'])) {
            $where[]  = 'source = ?';
            $params[] = $filters['source'];
        }

        if (!empty($filters['contract_type'])) {
            $where[]  = 'contract_type = ?';
            $params[] = $filters['contract_type'];
        }

        if (!empty($filters['since'])) {
            $where[]  = 'COALESCE(published_at, scraped_at) >= ?';
            $params[] = $filters['since'];
        }

        $sql = 'SELECT * FROM jobs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY COALESCE(published_at, scraped_at) DESC LIMIT ? OFFSET ?';

        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(Job::fromArray(...), $stmt->fetchAll());
    }

    public function count(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[]  = '(title LIKE ? OR company LIKE ? OR requirements LIKE ?)';
            $like     = '%' . $filters['keyword'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['location'])) {
            $where[]  = 'location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['source'])) {
            $where[]  = 'source = ?';
            $params[] = $filters['source'];
        }

        if (!empty($filters['contract_type'])) {
            $where[]  = 'contract_type = ?';
            $params[] = $filters['contract_type'];
        }

        if (!empty($filters['since'])) {
            $where[]  = 'COALESCE(published_at, scraped_at) >= ?';
            $params[] = $filters['since'];
        }

        $sql = 'SELECT COUNT(*) FROM jobs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?Job
    {
        $stmt = $this->pdo->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row !== false ? Job::fromArray($row) : null;
    }

    public function upsert(array $data): int
    {
        $params = [
            $data['external_id'],
            $data['source'],
            $data['title'],
            $data['company'],
            $data['location'] ?? null,
            $data['contract_type'] ?? null,
            $data['description'] ?? null,
            $data['requirements'] ?? null,
            $data['salary_range'] ?? null,
            $data['url'],
            $data['published_at'] ?? null,
        ];

        $isSqlite = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $now      = date('Y-m-d H:i:s');

        if ($isSqlite) {
            $sql = '
                INSERT INTO jobs
                    (external_id, source, title, company, location, contract_type,
                     description, requirements, salary_range, url, published_at, scraped_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(source, external_id) DO UPDATE SET
                    title         = excluded.title,
                    company       = excluded.company,
                    location      = excluded.location,
                    contract_type = excluded.contract_type,
                    description   = excluded.description,
                    requirements  = excluded.requirements,
                    salary_range  = excluded.salary_range,
                    url           = excluded.url,
                    published_at  = excluded.published_at,
                    scraped_at    = excluded.scraped_at
            ';
            $params[] = $now;
        } else {
            $sql = '
                INSERT INTO jobs
                    (external_id, source, title, company, location, contract_type,
                     description, requirements, salary_range, url, published_at, scraped_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    title         = VALUES(title),
                    company       = VALUES(company),
                    location      = VALUES(location),
                    contract_type = VALUES(contract_type),
                    description   = VALUES(description),
                    requirements  = VALUES(requirements),
                    salary_range  = VALUES(salary_range),
                    url           = VALUES(url),
                    published_at  = VALUES(published_at),
                    scraped_at    = NOW()
            ';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // rowCount() == 1: nova inserção; == 2: duplicate key update; == 0: sem mudança
        return $stmt->rowCount() === 1 ? 1 : 0;
    }

    public function findUnnotified(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM jobs WHERE is_notified = 0 ORDER BY COALESCE(published_at, scraped_at) DESC'
        );
        $stmt->execute();

        return array_map(Job::fromArray(...), $stmt->fetchAll());
    }

    public function markNotified(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "UPDATE jobs SET is_notified = 1 WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
    }
}
