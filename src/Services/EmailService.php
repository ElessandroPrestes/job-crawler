<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AlertFilter;
use App\Models\Job;
use App\Repositories\AlertFilterRepository;
use App\Repositories\JobRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

final class EmailService
{
    private JobRepository         $jobs;
    private AlertFilterRepository $filters;

    public function __construct()
    {
        $this->jobs    = new JobRepository();
        $this->filters = new AlertFilterRepository();
    }

    public function notifyNewJobs(): int
    {
        $unnotified = $this->jobs->findUnnotified();

        if (empty($unnotified)) {
            return 0;
        }

        $filters = $this->filters->findActive();

        if (empty($filters)) {
            return 0;
        }

        $byRecipient = $this->groupByRecipient($unnotified, $filters);
        $sent        = 0;
        $notifiedIds = [];

        foreach ($byRecipient as $email => $matchedJobs) {
            if (empty($matchedJobs)) {
                continue;
            }

            if ($this->send($email, $matchedJobs)) {
                $sent++;
                foreach ($matchedJobs as $job) {
                    $notifiedIds[] = $job->id;
                }
            }
        }

        if (!empty($notifiedIds)) {
            $this->jobs->markNotified(array_unique($notifiedIds));
        }

        return $sent;
    }

    private function groupByRecipient(array $jobs, array $filters): array
    {
        $groups = [];

        foreach ($filters as $filter) {
            /** @var AlertFilter $filter */
            $matched = [];

            foreach ($jobs as $job) {
                /** @var Job $job */
                if (!$job->matchesKeywords($filter->keywords)) {
                    continue;
                }

                if (!$job->matchesLocation($filter->locations)) {
                    continue;
                }

                if (!empty($filter->contractTypes) && $job->contractType !== null) {
                    if (!in_array($job->contractType, $filter->contractTypes, true)) {
                        continue;
                    }
                }

                $matched[] = $job;
            }

            if (!empty($matched)) {
                $groups[$filter->email] = array_merge($groups[$filter->email] ?? [], $matched);
            }
        }

        return $groups;
    }

    private function send(string $to, array $jobs): bool
    {
        $mailer = $this->buildMailer();

        try {
            $mailer->addAddress($to);
            $mailer->Subject = 'Novas vagas encontradas — Job Crawler';
            $mailer->Body    = $this->buildHtml($jobs);
            $mailer->AltBody = $this->buildText($jobs);

            return $mailer->send();
        } catch (\Exception) {
            return false;
        }
    }

    private function buildMailer(): PHPMailer
    {
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host       = $_ENV['MAIL_HOST'] ?? 'mailhog';
        $mailer->Port       = (int) ($_ENV['MAIL_PORT'] ?? 1025);
        $mailer->SMTPAuth   = false;
        $mailer->CharSet    = 'UTF-8';
        $mailer->isHTML(true);
        $mailer->setFrom(
            $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@jobcrawler.local',
            $_ENV['MAIL_FROM_NAME'] ?? 'Job Crawler'
        );

        $encryption = $_ENV['MAIL_ENCRYPTION'] ?? '';
        if ($encryption !== '') {
            $mailer->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $_ENV['MAIL_USERNAME'] ?? '';
            $mailer->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
        }

        return $mailer;
    }

    private function buildHtml(array $jobs): string
    {
        $count = count($jobs);
        $rows  = '';

        foreach ($jobs as $job) {
            /** @var Job $job */
            $title    = htmlspecialchars($job->title, ENT_QUOTES, 'UTF-8');
            $company  = htmlspecialchars($job->company, ENT_QUOTES, 'UTF-8');
            $location = htmlspecialchars($job->location ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $contract = htmlspecialchars($job->contractType ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $url      = htmlspecialchars($job->url, ENT_QUOTES, 'UTF-8');

            $rows .= "<tr>
                <td><a href=\"{$url}\">{$title}</a></td>
                <td>{$company}</td>
                <td>{$location}</td>
                <td>{$contract}</td>
            </tr>";
        }

        return "<!DOCTYPE html><html><body>
        <h2>Novas Vagas ({$count})</h2>
        <table border=\"1\" cellpadding=\"8\" cellspacing=\"0\">
            <thead><tr><th>Título</th><th>Empresa</th><th>Localização</th><th>Contrato</th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
        </body></html>";
    }

    private function buildText(array $jobs): string
    {
        $lines = ['Novas vagas encontradas:', ''];

        foreach ($jobs as $job) {
            /** @var Job $job */
            $lines[] = "- {$job->title} | {$job->company} | {$job->location}";
            $lines[] = "  {$job->url}";
        }

        return implode("\n", $lines);
    }
}
