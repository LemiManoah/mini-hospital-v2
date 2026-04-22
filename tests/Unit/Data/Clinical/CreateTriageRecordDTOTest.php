<?php

declare(strict_types=1);

use App\Data\Clinical\CreateTriageRecordDTO;
use Illuminate\Foundation\Http\FormRequest;

function createTriageRecordRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
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
}

it('builds a triage dto from validated input', function (): void {
    $dto = CreateTriageRecordDTO::fromRequest(createTriageRecordRequest([
        'triage_grade' => 'yellow',
        'attendance_type' => 'new',
        'news_score' => 5,
        'conscious_level' => 'alert',
        'mobility_status' => 'independent',
        'chief_complaint' => '  Chest pain  ',
        'history_of_presenting_illness' => '  Started this morning  ',
        'assigned_clinic_id' => '',
        'requires_priority' => true,
        'is_pediatric' => false,
        'poisoning_case' => true,
        'poisoning_agent' => '  Kerosene  ',
        'snake_bite_case' => false,
        'referred_by' => '  Nearby clinic  ',
        'nurse_notes' => '  Needs review  ',
    ]));

    expect($dto->triageGrade)->toBe('yellow')
        ->and($dto->chiefComplaint)->toBe('  Chest pain  ')
        ->and($dto->historyOfPresentingIllness)->toBe('Started this morning')
        ->and($dto->assignedClinicId)->toBeNull()
        ->and($dto->poisoningAgent)->toBe('Kerosene')
        ->and($dto->referredBy)->toBe('Nearby clinic')
        ->and($dto->nurseNotes)->toBe('Needs review')
        ->and($dto->requiresPriority)->toBeTrue();
});
