<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeleteFacilityService
{
    public function handle(FacilityService $service): void
    {
        if ($this->hasExistingOrders($service)) {
            throw ValidationException::withMessages([
                'delete' => 'This facility service cannot be deleted because it has existing service orders.',
            ]);
        }

        try {
            DB::transaction(fn () => $service->delete());
        } catch (QueryException $queryException) {
            if ($this->hasExistingOrders($service)) {
                throw ValidationException::withMessages([
                    'delete' => 'This facility service cannot be deleted because it has existing service orders.',
                ]);
            }

            throw $queryException;
        }
    }

    /** @phpstan-impure */
    private function hasExistingOrders(FacilityService $service): bool
    {
        return $service->orders()->exists();
    }
}
