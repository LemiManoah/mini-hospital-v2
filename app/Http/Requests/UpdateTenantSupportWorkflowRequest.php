<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TenantSupportPriority;
use App\Enums\TenantSupportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantSupportWorkflowRequest extends FormRequest
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
            'status' => ['required', Rule::enum(TenantSupportStatus::class)],
            'priority' => ['required', Rule::enum(TenantSupportPriority::class)],
            'follow_up_at' => ['nullable', 'date'],
            'last_contacted_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->input('status')) ? mb_trim($this->input('status')) : $this->input('status'),
            'priority' => is_string($this->input('priority')) ? mb_trim($this->input('priority')) : $this->input('priority'),
            'follow_up_at' => $this->filled('follow_up_at') ? $this->input('follow_up_at') : null,
            'last_contacted_at' => $this->filled('last_contacted_at') ? $this->input('last_contacted_at') : null,
        ]);
    }
}
