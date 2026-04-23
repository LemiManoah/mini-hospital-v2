<?php

declare(strict_types=1);

namespace App\Data\Clinical;

final readonly class LabTestCatalogResultOptionDTO
{
    public function __construct(
        public string $label,
    ) {}

    /**
     * @param  array{label: string}  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            label: $payload['label'],
        );
    }

    /**
     * @return array{label: string, sort_order: int, is_active: bool}
     */
    public function toRecordPayload(int $sortOrder): array
    {
        return [
            'label' => $this->label,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ];
    }
}
