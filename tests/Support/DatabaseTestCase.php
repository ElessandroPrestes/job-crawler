<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Config\Database;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        Database::reset();
        $this->pdo = Database::connection();
        $this->createSchema($this->pdo);
    }

    protected function tearDown(): void
    {
        Database::reset();
        parent::tearDown();
    }

    protected function createSchema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS jobs (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                external_id         TEXT    NOT NULL,
                source              TEXT    NOT NULL,
                title               TEXT    NOT NULL,
                title_normalized    TEXT    NOT NULL DEFAULT \'\',
                company             TEXT    NOT NULL,
                company_normalized  TEXT    NOT NULL DEFAULT \'\',
                location            TEXT,
                contract_type       TEXT,
                description         TEXT,
                requirements        TEXT,
                salary_range        TEXT,
                url                 TEXT    NOT NULL DEFAULT \'\',
                published_at        TEXT,
                scraped_at          TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                is_notified         INTEGER NOT NULL DEFAULT 0,
                compatibility_score INTEGER,
                matched_skills      TEXT,
                UNIQUE (company_normalized, title_normalized)
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crawl_logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                source      TEXT    NOT NULL,
                keyword     TEXT,
                location    TEXT,
                status      TEXT    NOT NULL DEFAULT \'running\',
                jobs_found  INTEGER NOT NULL DEFAULT 0,
                jobs_new    INTEGER NOT NULL DEFAULT 0,
                error_msg   TEXT,
                started_at  TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                finished_at TEXT
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS alert_filters (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                email          TEXT    NOT NULL,
                keywords       TEXT    NOT NULL DEFAULT \'[]\',
                locations      TEXT,
                contract_types TEXT,
                is_active      INTEGER NOT NULL DEFAULT 1,
                created_at     TEXT    NOT NULL DEFAULT (datetime(\'now\'))
            )
        ');
    }
}
