<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosSaleStatus;
use App\Models\PharmacyPosPayment;
use App\Models\PharmacyPosSale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordPharmacyPosPaymentAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(PharmacyPosSale $sale, array $attributes): PharmacyPosPayment
    {
        return DB::transaction(function () use ($sale, $attributes): PharmacyPosPayment {
            $sale = PharmacyPosSale::query()
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->status !== PharmacyPosSaleStatus::Completed) {
                throw ValidationException::withMessages([
                    'sale' => 'Only completed sales can receive additional payments.',
                ]);
            }

            $amount = max(0.0, round((float) ($attributes['amount'] ?? 0), 2));

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $payment = $sale->payments()->create([
                'amount' => $amount,
                'payment_method' => $attributes['payment_method'] ?? 'cash',
                'reference_number' => $this->nullableText($attributes['reference_number'] ?? null),
                'payment_date' => now(),
                'is_refund' => false,
                'notes' => $this->nullableText($attributes['notes'] ?? null),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $newPaidAmount = round((float) $sale->paid_amount + $amount, 2);
            $newBalanceAmount = max(0.0, round((float) $sale->gross_amount - (float) $sale->discount_amount - $newPaidAmount, 2));
            $newChangeAmount = max(0.0, round($newPaidAmount - ((float) $sale->gross_amount - (float) $sale->discount_amount), 2));

            $sale->update([
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $newBalanceAmount,
                'change_amount' => $newChangeAmount,
                'updated_by' => Auth::id(),
            ]);

            return $payment;
        });
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
