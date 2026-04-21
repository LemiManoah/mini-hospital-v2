<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTenantSupportNoteRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:4000'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->input('title')) ? mb_trim($this->input('title')) : null,
            'body' => is_string($this->input('body')) ? mb_trim($this->input('body')) : null,
            'is_pinned' => $this->boolean('is_pinned'),
        ]);
    }
}


