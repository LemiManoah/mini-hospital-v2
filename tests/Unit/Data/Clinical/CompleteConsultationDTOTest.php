<?php

declare(strict_types=1);

use App\Data\Clinical\CompleteConsultationDTO;
use Illuminate\Foundation\Http\FormRequest;

function completeConsultationRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
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
}

it('builds a complete consultation dto from validated input', function (): void {
    $dto = CompleteConsultationDTO::fromRequest(completeConsultationRequest([
        'intent' => 'complete',
        'chief_complaint' => '  Severe cough  ',
        'primary_diagnosis' => '  Pneumonia  ',
        'outcome' => 'follow_up_required',
        'follow_up_instructions' => '  Review in one week  ',
        'follow_up_days' => 7,
        'is_referred' => true,
        'referred_to_department' => '  Pulmonology  ',
        'referred_to_facility' => '',
        'referral_reason' => '  Needs specialist review  ',
    ]));

    expect($dto->chiefComplaint)->toBe('Severe cough')
        ->and($dto->primaryDiagnosis)->toBe('Pneumonia')
        ->and($dto->outcome)->toBe('follow_up_required')
        ->and($dto->followUpDays)->toBe(7)
        ->and($dto->isReferred)->toBeTrue()
        ->and($dto->referredToDepartment)->toBe('Pulmonology')
        ->and($dto->referredToFacility)->toBeNull()
        ->and($dto->referralReason)->toBe('Needs specialist review');
});
