<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Clinic;

final readonly class UpdateClinic
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Clinic $clinic, array $data): bool
    {
        return $clinic->update($data);
    }
}
