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
        $amount = $this->validated('paid_amount');

        return round(is_numeric($amount) ? (float) $amount : 0.0, 2);
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
            ->filter(static fn (mixed $allocation): bool => is_array($allocation))
            ->map(static function (mixed $allocation): array {
                $allocatedAmount = $allocation['allocated_amount'] ?? 0;

                return [
                    'insured_visit_claim_id' => isset($allocation['insured_visit_claim_id']) && is_string($allocation['insured_visit_claim_id'])
                        ? $allocation['insured_visit_claim_id']
                        : '',
                    'allocated_amount' => is_int($allocatedAmount) || is_float($allocatedAmount) || is_string($allocatedAmount)
                        ? $allocatedAmount
                        : 0,
                    'notes' => isset($allocation['notes']) && is_string($allocation['notes']) ? $allocation['notes'] : null,
                ];
            })
            ->values()
            ->all();
    }
}
