<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConsultationTariff;

final class DeleteConsultationTariff
{
    public function handle(ConsultationTariff $consultationTariff): void
    {
        $consultationTariff->delete();
    }
}
