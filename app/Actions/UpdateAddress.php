<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAddress
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Address $address, array $attributes): Address
    {
        return DB::transaction(function () use ($address, $attributes): Address {
            $address->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $address;
        });
    }
}
