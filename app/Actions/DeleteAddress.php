<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Address;

final readonly class DeleteAddress
{
    public function handle(Address $address): bool
    {
        return $address->delete();
    }
}
