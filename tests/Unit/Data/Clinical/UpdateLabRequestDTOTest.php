<?php

declare(strict_types=1);

use App\Data\Clinical\UpdateLabRequestDTO;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes update lab request input into a typed dto', function (): void {
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
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

    $data = UpdateLabRequestDTO::fromRequest($request([
        'test_ids' => ['test-3', 'test-3', 'test-4'],
        'clinical_notes' => '   ',
        'priority' => Priority::ROUTINE,
        'diagnosis_code' => '',
        'is_stat' => false,
    ]));

    expect($data->testIds)->toBe(['test-3', 'test-4'])
        ->and($data->clinicalNotes)->toBeNull()
        ->and($data->priority)->toBe(Priority::ROUTINE)
        ->and($data->diagnosisCode)->toBeNull()
        ->and($data->isStat)->toBeFalse();
});
