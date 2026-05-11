<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePolicyItem;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateInsurancePolicyItem
{
    /**
     * @param  array{
     *     price: numeric-string,
     *     effective_from?: string|null,
     *     effective_to?: string|null,
     *     status: string
     * }  $data
     */
    public function handle(InsurancePolicyItem $item, array $data): InsurancePolicyItem
    {
        $item->update([
            'price' => $data['price'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return $item->refresh();
    }
}
