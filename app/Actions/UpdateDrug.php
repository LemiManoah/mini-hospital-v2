<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Drug;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateDrug
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Drug $drug, array $attributes): Drug
    {
        return DB::transaction(function () use ($drug, $attributes): Drug {
            $drug->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $drug->refresh();
        });
    }
}
