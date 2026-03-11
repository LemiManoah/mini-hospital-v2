<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Clinic;

final readonly class CreateClinic
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Clinic
    {
        return Clinic::create($data);
    }
}
