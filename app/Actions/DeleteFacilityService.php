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
        if ($service->orders()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'This facility service cannot be deleted because it has existing service orders.',
            ]);
        }

        try {
            DB::transaction(fn () => $service->delete());
        } catch (QueryException $exception) {
            if ($service->orders()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This facility service cannot be deleted because it has existing service orders.',
                ]);
            }

            throw $exception;
        }
    }
}
