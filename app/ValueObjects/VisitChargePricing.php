<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class VisitChargePricing
{
    public function __construct(
        public float $unitPrice,
        public float $copayAmount = 0.0,
        public ?string $insurancePolicyItemId = null,
    ) {}
}
