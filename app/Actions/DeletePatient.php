<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Patient;

final class DeletePatient
{
    public function handle(Patient $patient): bool
    {
        return $patient->delete();
    }
}
