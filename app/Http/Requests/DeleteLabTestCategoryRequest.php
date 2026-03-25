<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteLabTestCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
