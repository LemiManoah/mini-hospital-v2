<?php

declare(strict_types=1);

use App\Data\Patient\CreateVisitPaymentDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes visit payment input into a typed dto', function (): void {
    /**
     * @param  array<string, int|float|string|null>  $validated
     */
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
    {
        /**
         * @param  array<string, int|float|string|null>  $validatedInput
         */
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        /**
         * @return array<string, int|float|string|null>
         */
        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $dto = CreateVisitPaymentDTO::fromRequest($request([
        'amount' => '125.50',
        'payment_method_id' => '4e4b02d5-5fdf-4660-b08d-4b6985b4f008',
        'payment_date' => '2026-04-23',
        'reference_number' => ' MM-001 ',
        'notes' => ' Settled at desk ',
    ]));

    expect($dto->amount)->toBe(125.5)
        ->and($dto->paymentMethodId)->toBe('4e4b02d5-5fdf-4660-b08d-4b6985b4f008')
        ->and($dto->paymentDate)->toBe('2026-04-23')
        ->and($dto->referenceNumber)->toBe('MM-001')
        ->and($dto->notes)->toBe('Settled at desk');
});
