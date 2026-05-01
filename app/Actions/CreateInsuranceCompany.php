<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsuranceCompany;
use Illuminate\Support\Facades\Auth;

final readonly class CreateInsuranceCompany
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): InsuranceCompany
    {
        return InsuranceCompany::query()->create([
            ...$data,
            'created_by' => Auth::id(),
        ]);
    }
}
