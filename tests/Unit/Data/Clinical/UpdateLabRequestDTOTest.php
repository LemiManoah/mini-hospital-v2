<?php

declare(strict_types=1);

use App\Data\Clinical\UpdateLabRequestDTO;
use App\Enums\Priority;

it('normalizes update lab request input into a typed dto', function (): void {
    $data = UpdateLabRequestDTO::fromRequest([
        'test_ids' => ['test-3', 'test-3', 'test-4'],
        'clinical_notes' => '   ',
        'priority' => Priority::ROUTINE,
        'diagnosis_code' => '',
        'is_stat' => false,
    ]);

    expect($data->testIds)->toBe(['test-3', 'test-4'])
        ->and($data->clinicalNotes)->toBeNull()
        ->and($data->priority)->toBe(Priority::ROUTINE)
        ->and($data->diagnosisCode)->toBeNull()
        ->and($data->isStat)->toBeFalse();
});
