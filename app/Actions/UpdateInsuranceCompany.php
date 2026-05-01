<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsuranceCompany;
use Illuminate\Support\Facades\Auth;

final class UpdateInsuranceCompany
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(InsuranceCompany $insuranceCompany, array $attributes): InsuranceCompany
    {
        $insuranceCompany->update([
            ...$attributes,
            'updated_by' => Auth::id(),
        ]);

        return $insuranceCompany;
    }
}
