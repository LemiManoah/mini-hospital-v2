<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Clinic;

final readonly class DeleteClinic
{
    public function handle(Clinic $clinic): bool
    {
        return (bool) $clinic->delete();
    }
}
