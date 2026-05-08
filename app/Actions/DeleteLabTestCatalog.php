<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabTestCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeleteLabTestCatalog
{
    public function handle(LabTestCatalog $labTestCatalog): void
    {
        if ($this->hasExistingOrderItems($labTestCatalog)) {
            throw ValidationException::withMessages([
                'delete' => 'This lab test cannot be deleted because it has existing lab orders.',
            ]);
        }

        try {
            DB::transaction(fn () => $labTestCatalog->delete());
        } catch (QueryException $queryException) {
            if ($this->hasExistingOrderItems($labTestCatalog)) {
                throw ValidationException::withMessages([
                    'delete' => 'This lab test cannot be deleted because it has existing lab orders.',
                ]);
            }

            throw $queryException;
        }
    }

    /** @phpstan-impure */
    private function hasExistingOrderItems(LabTestCatalog $labTestCatalog): bool
    {
        return $labTestCatalog->orderItems()->exists();
    }
}
