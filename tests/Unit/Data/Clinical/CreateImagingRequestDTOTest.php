<?php

declare(strict_types=1);

use App\Data\Clinical\CreateImagingRequestDTO;
use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\PregnancyStatus;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes create imaging request input into a typed dto', function (): void {
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

    $dto = CreateImagingRequestDTO::fromRequest($request([
        'modality' => 'xray',
        'body_part' => '  Chest  ',
        'laterality' => 'left',
        'clinical_history' => '  Fever and cough  ',
        'indication' => '  Rule out pneumonia  ',
        'priority' => 'urgent',
        'requires_contrast' => false,
        'contrast_allergy_status' => '   ',
        'pregnancy_status' => 'unknown',
    ]));

    expect($dto->modality)->toBe(ImagingModality::XRAY)
        ->and($dto->bodyPart)->toBe('Chest')
        ->and($dto->laterality)->toBe(ImagingLaterality::LEFT)
        ->and($dto->clinicalHistory)->toBe('Fever and cough')
        ->and($dto->indication)->toBe('Rule out pneumonia')
        ->and($dto->priority)->toBe(ImagingPriority::URGENT)
        ->and($dto->contrastAllergyStatus)->toBeNull()
        ->and($dto->pregnancyStatus)->toBe(PregnancyStatus::UNKNOWN);
});
