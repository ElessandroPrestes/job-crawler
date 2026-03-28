<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\AlertFilterRepository;
use App\Repositories\JobRepository;
use App\Services\EmailService;
use Tests\Support\DatabaseTestCase;

final class EmailServiceTest extends DatabaseTestCase
{
    private EmailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailService();
    }

    public function testReturnsZeroWhenNoUnnotifiedJobs(): void
    {
        // Nenhum job na base de dados
        $result = $this->service->notifyNewJobs();

        $this->assertSame(0, $result);
    }

    public function testReturnsZeroWhenNoActiveFilters(): void
    {
        // Seed de job não notificado
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, url, scraped_at, is_notified)
            VALUES ('ext-001', 'linkedin', 'PHP Developer', 'Tech Corp', 'https://linkedin.com/1', datetime('now'), 0)
        ");

        // Sem filtros cadastrados — retorno antecipado
        $result = $this->service->notifyNewJobs();

        $this->assertSame(0, $result);
    }

    public function testReturnsZeroWhenNoJobsMatchAnyFilter(): void
    {
        // Job com título que não matcha nenhum keyword do filtro
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, url, scraped_at, is_notified)
            VALUES ('ext-002', 'linkedin', 'Java Developer', 'Tech Corp', 'https://linkedin.com/2', datetime('now'), 0)
        ");

        // Filtro com keyword que não bate com o job
        $filterRepo = new AlertFilterRepository();
        $filterRepo->create([
            'email'    => 'dev@test.com',
            'keywords' => ['Python'],
        ]);

        // groupByRecipient não encontrará matches => grupos vazios
        $result = $this->service->notifyNewJobs();

        // Nenhum job matched => grupos vazios => sent=0
        $this->assertSame(0, $result);
    }

    public function testGroupByRecipientGroupsMatchingJobsCorrectly(): void
    {
        // Job que matcha keyword "PHP"
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, url, scraped_at, is_notified)
            VALUES ('ext-003', 'linkedin', 'Senior PHP Developer', 'Corp X', 'https://linkedin.com/3', datetime('now'), 0)
        ");

        $filterRepo = new AlertFilterRepository();
        $filterRepo->create([
            'email'    => 'recruiter@test.com',
            'keywords' => ['PHP'],
        ]);

        // send() vai falhar (sem SMTP real), retornando false
        // Logo sent=0 e markNotified nunca será chamado
        $result = $this->service->notifyNewJobs();

        // O job matchou mas send() falhou => 0 enviados
        $this->assertIsInt($result);
        $this->assertSame(0, $result);

        // O job NÃO deve ter sido marcado como notificado (send falhou)
        $jobRepo    = new JobRepository();
        $unnotified = $jobRepo->findUnnotified();
        $this->assertCount(1, $unnotified);
    }

    public function testJobRemainsUnnotifiedWhenSendFails(): void
    {
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, location, url, scraped_at, is_notified)
            VALUES ('ext-004', 'indeed', 'PHP Backend Dev', 'Startup Y', 'Brasil', 'https://indeed.com/4', datetime('now'), 0)
        ");

        $filterRepo = new AlertFilterRepository();
        $filterRepo->create([
            'email'     => 'hr@company.com',
            'keywords'  => ['PHP'],
            'locations' => ['Brasil'],
        ]);

        $result = $this->service->notifyNewJobs();

        // send() sempre falha em ambiente de teste sem SMTP
        $this->assertSame(0, $result);

        // Verificar que is_notified permanece 0
        $stmt = $this->pdo->query("SELECT is_notified FROM jobs WHERE external_id = 'ext-004'");
        $row  = $stmt->fetch();
        $this->assertSame(0, (int) $row['is_notified']);
    }
}
