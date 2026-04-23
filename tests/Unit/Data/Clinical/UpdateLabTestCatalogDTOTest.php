<?php

declare(strict_types=1);

use App\Data\Clinical\UpdateLabTestCatalogDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes update lab test catalog input into a typed dto', function (): void {
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

    $dto = UpdateLabTestCatalogDTO::fromRequest($request([
        'test_code' => 'MAL-001',
        'test_name' => 'Malaria Microscopy',
        'lab_test_category_id' => 'category-1',
        'result_type_id' => 'result-type-2',
        'description' => ' Updated malaria workflow ',
        'base_price' => 18000,
        'is_active' => true,
        'specimen_type_ids' => ['specimen-1'],
        'result_options' => [
            ['label' => 'Positive'],
            ['label' => 'Negative'],
        ],
    ]));

    expect($dto->testName)->toBe('Malaria Microscopy')
        ->and($dto->description)->toBe('Updated malaria workflow')
        ->and($dto->resultOptions)->toHaveCount(2)
        ->and($dto->resultOptions[0]->label)->toBe('Positive');
});
