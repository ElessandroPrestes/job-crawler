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

    /**
     * Normaliza string para deduplicação cross-source.
     * Remove acentos, lowercase, colapsa espaços e remove pontuação.
     */
    public static function normalize(string $value): string
    {
        // Transliterar acentos → ASCII
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        // Lowercase + remove pontuação desnecessária
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        // Colapsar múltiplos espaços
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
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

        $interval = $filters['since'] ?? '24h';
        $isSqlite = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';

        if ($interval === '3d') {
            if ($isSqlite) {
                $where[] = 'scraped_at >= datetime("now", "-3 days")';
            } else {
                $where[] = 'scraped_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)';
            }
        } else {
            if ($isSqlite) {
                $where[] = 'scraped_at >= datetime("now", "-1 day")';
            } else {
                $where[] = 'scraped_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
            }
        }

        if (!empty($filters['contract_type'])) {
            $where[]  = 'contract_type = ?';
            $params[] = $filters['contract_type'];
        }



        $sql = 'SELECT * FROM jobs';
        $sql .= ' WHERE ' . implode(' AND ', $where);
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

        $interval = $filters['since'] ?? '24h';
        $isSqlite = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';

        if ($interval === '3d') {
            if ($isSqlite) {
                $where[] = 'scraped_at >= datetime("now", "-3 days")';
            } else {
                $where[] = 'scraped_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)';
            }
        } else {
            if ($isSqlite) {
                $where[] = 'scraped_at >= datetime("now", "-1 day")';
            } else {
                $where[] = 'scraped_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
            }
        }

        if (!empty($filters['contract_type'])) {
            $where[]  = 'contract_type = ?';
            $params[] = $filters['contract_type'];
        }



        $sql = 'SELECT COUNT(*) FROM jobs';
        $sql .= ' WHERE ' . implode(' AND ', $where);

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
        $companyNorm = self::normalize($data['company'] ?? '');
        $titleNorm   = self::normalize($data['title'] ?? '');

        $isSqlite = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $now      = date('Y-m-d H:i:s');

        if ($isSqlite) {
            $params = [
                $data['external_id'],
                $data['source'],
                $data['title'],
                $titleNorm,
                $data['company'],
                $companyNorm,
                $data['location'] ?? null,
                $data['contract_type'] ?? null,
                $data['description'] ?? null,
                $data['requirements'] ?? null,
                $data['salary_range'] ?? null,
                $data['url'],
                $data['published_at'] ?? null,
                $now, // scraped_at
                isset($data['compatibility_score']) ? (int) $data['compatibility_score'] : null,
                $data['matched_skills'] ?? null,
            ];

            $sql = '
                INSERT INTO jobs
                    (external_id, source, title, title_normalized, company, company_normalized,
                     location, contract_type, description, requirements, salary_range,
                     url, published_at, scraped_at, compatibility_score, matched_skills)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(company_normalized, title_normalized) DO UPDATE SET
                    scraped_at          = excluded.scraped_at,
                    compatibility_score = COALESCE(excluded.compatibility_score, compatibility_score),
                    matched_skills      = COALESCE(excluded.matched_skills, matched_skills),
                    source              = CASE WHEN excluded.published_at < jobs.published_at OR jobs.published_at IS NULL THEN excluded.source ELSE jobs.source END,
                    published_at        = CASE WHEN excluded.published_at < jobs.published_at OR jobs.published_at IS NULL THEN excluded.published_at ELSE jobs.published_at END
            ';
        } else {
            $params = [
                $data['external_id'],
                $data['source'],
                $data['title'],
                $titleNorm,
                $data['company'],
                $companyNorm,
                $data['location'] ?? null,
                $data['contract_type'] ?? null,
                $data['description'] ?? null,
                $data['requirements'] ?? null,
                $data['salary_range'] ?? null,
                $data['url'],
                $data['published_at'] ?? null,
                isset($data['compatibility_score']) ? (int) $data['compatibility_score'] : null,
                $data['matched_skills'] ?? null,
            ];

            $sql = '
                INSERT INTO jobs
                    (external_id, source, title, title_normalized, company, company_normalized,
                     location, contract_type, description, requirements, salary_range,
                     url, published_at, scraped_at, compatibility_score, matched_skills)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE
                    scraped_at          = NOW(),
                    compatibility_score = COALESCE(VALUES(compatibility_score), compatibility_score),
                    matched_skills      = COALESCE(VALUES(matched_skills), matched_skills),
                    source              = IF(VALUES(published_at) < published_at OR published_at IS NULL, VALUES(source), source),
                    published_at        = IF(VALUES(published_at) < published_at OR published_at IS NULL, VALUES(published_at), published_at)
            ';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // rowCount() == 1: nova inserção; == 2: dedup update (não conta como nova); == 0: sem mudança
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
        $stmt->execute(array_values($ids));
    }
}
