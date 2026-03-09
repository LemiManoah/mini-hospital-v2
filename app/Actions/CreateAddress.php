<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateAddress
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Address
    {
        return DB::transaction(fn (): Address => Address::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
