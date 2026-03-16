<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppointmentCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAppointmentCategory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(AppointmentCategory $category, array $attributes): AppointmentCategory
    {
        return DB::transaction(function () use ($category, $attributes): AppointmentCategory {
            $category->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $category->refresh();
        });
    }
}
