<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConsultationTariff;
use Illuminate\Support\Facades\Auth;

final class CreateConsultationTariff
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): ConsultationTariff
    {
        return ConsultationTariff::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
