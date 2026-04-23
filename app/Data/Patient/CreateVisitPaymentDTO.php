<?php

declare(strict_types=1);

namespace App\Data\Patient;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateVisitPaymentDTO
{
    public function __construct(
        public float $amount,
        public string $paymentMethod,
        public ?string $paymentDate,
        public ?string $referenceNumber,
        public ?string $notes,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     amount: int|float|string,
         *     payment_method: string,
         *     payment_date?: string|null,
         *     reference_number?: string|null,
         *     notes?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            amount: self::numericValue($validated['amount']),
            paymentMethod: $validated['payment_method'],
            paymentDate: self::nullableString($validated['payment_date'] ?? null),
            referenceNumber: self::nullableString($validated['reference_number'] ?? null),
            notes: self::nullableString($validated['notes'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function numericValue(int|float|string $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $trimmed = mb_trim($value);

        return is_numeric($trimmed) ? (float) $trimmed : 0.0;
    }
}
