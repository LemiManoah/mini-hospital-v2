<?php

declare(strict_types=1);

use App\Data\Clinical\UpdateConsultationDTO;
use Illuminate\Foundation\Http\FormRequest;

function updateConsultationRequest(array $validated): FormRequest
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

it('builds an update consultation dto from validated input', function (): void {
    $dto = UpdateConsultationDTO::fromRequest(updateConsultationRequest([
        'intent' => 'save_draft',
        'chief_complaint' => '  Updated complaint  ',
        'plan' => '  Return if worse  ',
    ]));

    expect($dto->chiefComplaint)->toBe('Updated complaint')
        ->and($dto->plan)->toBe('Return if worse');
});
