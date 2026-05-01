<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInsuranceCompanyInvoicePaymentRequest extends FormRequest
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
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['nullable', 'date'],
            'receipt' => ['nullable', 'string', 'max:100'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.insured_visit_claim_id' => ['required', 'uuid', 'distinct'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'gt:0'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    public function paidAmount(): float
    {
        return round((float) $this->validated('paid_amount'), 2);
    }

    public function paymentDate(): ?string
    {
        $value = $this->validated('payment_date');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function receipt(): ?string
    {
        $value = $this->validated('receipt');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<int, array{insured_visit_claim_id: string, allocated_amount: int|float|string, notes?: string|null}>
     */
    public function allocations(): array
    {
        $allocations = $this->validated('allocations');

        if (! is_array($allocations)) {
            return [];
        }

        return collect($allocations)
            ->map(static fn (array $allocation): array => [
                'insured_visit_claim_id' => (string) $allocation['insured_visit_claim_id'],
                'allocated_amount' => $allocation['allocated_amount'],
                'notes' => isset($allocation['notes']) && is_string($allocation['notes']) ? $allocation['notes'] : null,
            ])
            ->values()
            ->all();
    }
}
