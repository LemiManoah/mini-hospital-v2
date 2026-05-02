<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReverseBillingWriteOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reversal_reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function reversalReason(): string
    {
        return (string) $this->validated('reversal_reason');
    }
}
