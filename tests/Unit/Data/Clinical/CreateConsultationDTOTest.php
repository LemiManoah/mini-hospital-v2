<?php

declare(strict_types=1);

use App\Data\Clinical\CreateConsultationDTO;
use Illuminate\Foundation\Http\FormRequest;

function createConsultationRequest(array $validated): FormRequest
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

it('builds a create consultation dto from validated input', function (): void {
    $dto = CreateConsultationDTO::fromRequest(createConsultationRequest([
        'chief_complaint' => '  Headache  ',
        'assessment' => '  Migraine suspected  ',
        'primary_diagnosis' => '',
    ]));

    expect($dto->chiefComplaint)->toBe('Headache')
        ->and($dto->assessment)->toBe('Migraine suspected')
        ->and($dto->primaryDiagnosis)->toBeNull();
});
