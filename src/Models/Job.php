<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Job
{
    public function __construct(
        public int     $id,
        public string  $externalId,
        public string  $source,
        public string  $title,
        public string  $company,
        public ?string $location,
        public ?string $contractType,
        public ?string $description,
        public ?string $requirements,
        public ?string $salaryRange,
        public string  $url,
        public ?string $publishedAt,
        public string  $scrapedAt,
        public bool    $isNotified,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:           (int) ($data['id'] ?? 0),
            externalId:   (string) ($data['external_id'] ?? ''),
            source:       (string) ($data['source'] ?? ''),
            title:        (string) ($data['title'] ?? ''),
            company:      (string) ($data['company'] ?? ''),
            location:     $data['location'] ?? null,
            contractType: $data['contract_type'] ?? null,
            description:  $data['description'] ?? null,
            requirements: $data['requirements'] ?? null,
            salaryRange:  $data['salary_range'] ?? null,
            url:          (string) ($data['url'] ?? ''),
            publishedAt:  $data['published_at'] ?? null,
            scrapedAt:    (string) ($data['scraped_at'] ?? date('Y-m-d H:i:s')),
            isNotified:   (bool) ($data['is_notified'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'external_id'   => $this->externalId,
            'source'        => $this->source,
            'title'         => $this->title,
            'company'       => $this->company,
            'location'      => $this->location,
            'contract_type' => $this->contractType,
            'description'   => $this->description,
            'requirements'  => $this->requirements,
            'salary_range'  => $this->salaryRange,
            'url'           => $this->url,
            'published_at'  => $this->publishedAt,
            'scraped_at'    => $this->scrapedAt,
            'is_notified'   => $this->isNotified,
        ];
    }

    public function matchesKeywords(array $keywords): bool
    {
        if (empty($keywords)) {
            return true;
        }

        $haystack = strtolower(
            ($this->title ?? '') . ' ' .
            ($this->company ?? '') . ' ' .
            ($this->requirements ?? '') . ' ' .
            ($this->description ?? '')
        );

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, strtolower((string) $keyword))) {
                return true;
            }
        }

        return false;
    }

    public function matchesLocation(array $locations): bool
    {
        if (empty($locations) || $this->location === null) {
            return true;
        }

        $jobLocation = strtolower($this->location);

        foreach ($locations as $location) {
            if (str_contains($jobLocation, strtolower((string) $location))) {
                return true;
            }
        }

        return false;
    }
}
