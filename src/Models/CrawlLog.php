<?php

declare(strict_types=1);

namespace App\Models;

final readonly class CrawlLog
{
    public function __construct(
        public int     $id,
        public string  $source,
        public ?string $keyword,
        public ?string $location,
        public string  $status,
        public int     $jobsFound,
        public int     $jobsNew,
        public ?string $errorMsg,
        public string  $startedAt,
        public ?string $finishedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:         (int) ($data['id'] ?? 0),
            source:     (string) ($data['source'] ?? ''),
            keyword:    $data['keyword'] ?? null,
            location:   $data['location'] ?? null,
            status:     (string) ($data['status'] ?? 'running'),
            jobsFound:  (int) ($data['jobs_found'] ?? 0),
            jobsNew:    (int) ($data['jobs_new'] ?? 0),
            errorMsg:   $data['error_msg'] ?? null,
            startedAt:  (string) ($data['started_at'] ?? date('Y-m-d H:i:s')),
            finishedAt: $data['finished_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'source'      => $this->source,
            'keyword'     => $this->keyword,
            'location'    => $this->location,
            'status'      => $this->status,
            'jobs_found'  => $this->jobsFound,
            'jobs_new'    => $this->jobsNew,
            'error_msg'   => $this->errorMsg,
            'started_at'  => $this->startedAt,
            'finished_at' => $this->finishedAt,
        ];
    }
}
