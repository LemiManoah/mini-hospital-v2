<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class UpdateLabTestCatalogDTO
{
    /**
     * @param  list<string>  $specimenTypeIds
     * @param  list<LabTestCatalogResultOptionDTO>  $resultOptions
     * @param  list<LabTestCatalogResultParameterDTO>  $resultParameters
     */
    public function __construct(
        public string $testCode,
        public string $testName,
        public string $labTestCategoryId,
        public string $resultTypeId,
        public ?string $description,
        public int|float|string $basePrice,
        public bool $isActive,
        public array $specimenTypeIds,
        public array $resultOptions,
        public array $resultParameters,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     test_code: string,
         *     test_name: string,
         *     lab_test_category_id: string,
         *     result_type_id: string,
         *     description?: string|null,
         *     base_price: int|float|string,
         *     is_active?: bool,
         *     specimen_type_ids: list<string>,
         *     result_options?: list<array{label: string}>,
         *     result_parameters?: list<array{
         *         label: string,
         *         unit?: string|null,
         *         reference_range?: string|null,
         *         value_type?: string|null
         *     }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            testCode: $validated['test_code'],
            testName: $validated['test_name'],
            labTestCategoryId: $validated['lab_test_category_id'],
            resultTypeId: $validated['result_type_id'],
            description: self::nullableString($validated['description'] ?? null),
            basePrice: $validated['base_price'],
            isActive: $validated['is_active'] ?? true,
            specimenTypeIds: $validated['specimen_type_ids'],
            resultOptions: array_map(
                LabTestCatalogResultOptionDTO::fromPayload(...),
                $validated['result_options'] ?? [],
            ),
            resultParameters: array_map(
                LabTestCatalogResultParameterDTO::fromPayload(...),
                $validated['result_parameters'] ?? [],
            ),
        );
    }

    /**
     * @return array{
     *      test_code: string,
     *      test_name: string,
     *      lab_test_category_id: string,
     *      result_type_id: string,
     *      description: ?string,
     *      base_price: int|float|string,
     *      is_active: bool
     *  }
     */
    public function toAttributes(): array
    {
        return [
            'test_code' => $this->testCode,
            'test_name' => $this->testName,
            'lab_test_category_id' => $this->labTestCategoryId,
            'result_type_id' => $this->resultTypeId,
            'description' => $this->description,
            'base_price' => $this->basePrice,
            'is_active' => $this->isActive,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
