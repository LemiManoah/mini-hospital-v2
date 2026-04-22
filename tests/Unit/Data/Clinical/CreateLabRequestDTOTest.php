<?php

declare(strict_types=1);

use App\Data\Clinical\CreateLabRequestDTO;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes create lab request input into a typed dto', function (): void {
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
    {
        public function __construct(private array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $data = CreateLabRequestDTO::fromRequest($request([
        'test_ids' => ['test-1', 'test-2', 'test-1'],
        'clinical_notes' => '  Rule out malaria  ',
        'priority' => 'urgent',
        'diagnosis_code' => '  B50  ',
        'is_stat' => true,
    ]));

    expect($data->testIds)->toBe(['test-1', 'test-2'])
        ->and($data->clinicalNotes)->toBe('Rule out malaria')
        ->and($data->priority)->toBe(Priority::URGENT)
        ->and($data->diagnosisCode)->toBe('B50')
        ->and($data->isStat)->toBeTrue();
});
