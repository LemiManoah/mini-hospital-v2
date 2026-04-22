<?php

declare(strict_types=1);

use App\Data\Clinical\CreateLabRequestDTO;
use App\Enums\Priority;

it('normalizes create lab request input into a typed dto', function (): void {
    $data = CreateLabRequestDTO::fromRequest([
        'test_ids' => ['test-1', 'test-2', 'test-1'],
        'clinical_notes' => '  Rule out malaria  ',
        'priority' => 'urgent',
        'diagnosis_code' => '  B50  ',
        'is_stat' => true,
    ]);

    expect($data->testIds)->toBe(['test-1', 'test-2'])
        ->and($data->clinicalNotes)->toBe('Rule out malaria')
        ->and($data->priority)->toBe(Priority::URGENT)
        ->and($data->diagnosisCode)->toBe('B50')
        ->and($data->isStat)->toBeTrue();
});
