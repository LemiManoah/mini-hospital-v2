<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePolicyItem;

final readonly class DeleteInsurancePolicyItem
{
    public function handle(InsurancePolicyItem $item): bool
    {
        return $item->delete() === true;
    }
}
