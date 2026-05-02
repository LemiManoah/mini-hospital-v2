<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBillingWriteOffRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function amount(): float
    {
        return round((float) $this->validated('amount'), 2);
    }

    public function reason(): string
    {
        return (string) $this->validated('reason');
    }

    public function notes(): ?string
    {
        $notes = $this->validated('notes');

        return is_string($notes) && $notes !== '' ? $notes : null;
    }
}
