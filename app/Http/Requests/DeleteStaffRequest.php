<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteStaffRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
