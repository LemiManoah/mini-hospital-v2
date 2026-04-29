<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConsultationTariff;
use Illuminate\Support\Facades\Auth;

final class UpdateConsultationTariff
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ConsultationTariff $consultationTariff, array $attributes): ConsultationTariff
    {
        $consultationTariff->update([
            ...$attributes,
            'updated_by' => Auth::id(),
        ]);

        return $consultationTariff->refresh();
    }
}
