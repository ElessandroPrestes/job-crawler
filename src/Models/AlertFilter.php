<?php

declare(strict_types=1);

namespace App\Models;

final readonly class AlertFilter
{
    public function __construct(
        public int     $id,
        public string  $email,
        public array   $keywords,
        public array   $locations,
        public array   $contractTypes,
        public bool    $isActive,
        public string  $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:            (int) ($data['id'] ?? 0),
            email:         (string) ($data['email'] ?? ''),
            keywords:      self::decodeJson($data['keywords'] ?? '[]'),
            locations:     self::decodeJson($data['locations'] ?? '[]'),
            contractTypes: self::decodeJson($data['contract_types'] ?? '[]'),
            isActive:      (bool) ($data['is_active'] ?? true),
            createdAt:     (string) ($data['created_at'] ?? date('Y-m-d H:i:s')),
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'email'          => $this->email,
            'keywords'       => $this->keywords,
            'locations'      => $this->locations,
            'contract_types' => $this->contractTypes,
            'is_active'      => $this->isActive,
            'created_at'     => $this->createdAt,
        ];
    }

    private static function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
