<?php

declare(strict_types=1);

use App\Data\Clinical\CreateLabTestCatalogDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes create lab test catalog input into a typed dto', function (): void {
    /**
     * @param  array<string, mixed>  $validated
     */
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
    {
        /**
         * @param  array<string, mixed>  $validatedInput
         */
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        /**
         * @return array<string, mixed>
         */
        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $dto = CreateLabTestCatalogDTO::fromRequest($request([
        'test_code' => 'CBC-001',
        'test_name' => 'Complete Blood Count',
        'lab_test_category_id' => 'category-1',
        'result_type_id' => 'result-type-1',
        'description' => ' Baseline hematology panel ',
        'base_price' => '25000',
        'is_active' => true,
        'specimen_type_ids' => ['specimen-1', 'specimen-2'],
        'result_parameters' => [
            [
                'label' => 'WBC',
                'unit' => 'x10^9/L',
                'reference_range' => '4 - 11',
                'value_type' => 'numeric',
            ],
        ],
    ]));

    expect($dto->testCode)->toBe('CBC-001')
        ->and($dto->description)->toBe('Baseline hematology panel')
        ->and($dto->specimenTypeIds)->toBe(['specimen-1', 'specimen-2'])
        ->and($dto->resultParameters)->toHaveCount(1)
        ->and($dto->resultParameters[0]->label)->toBe('WBC')
        ->and($dto->resultParameters[0]->unit)->toBe('x10^9/L');
});
