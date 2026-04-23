<?php

declare(strict_types=1);

use App\Data\Clinical\StoreLabResultEntryDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes lab result entry input into a typed dto', function (): void {
    $request = new class(['result_notes' => '  Sample looks good  ', 'free_entry_value' => null, 'selected_option_label' => '', 'parameter_values' => [['lab_test_result_parameter_id' => 'parameter-1', 'value' => ' 13.4 ']]]) extends FormRequest
    {
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $dto = StoreLabResultEntryDTO::fromRequest($request);

    expect($dto->resultNotes)->toBe('Sample looks good')
        ->and($dto->freeEntryValue)->toBeNull()
        ->and($dto->selectedOptionLabel)->toBeNull()
        ->and($dto->parameterValues)->toHaveCount(1)
        ->and($dto->parameterValues[0]->labTestResultParameterId)->toBe('parameter-1')
        ->and($dto->parameterValues[0]->value)->toBe('13.4');
});
