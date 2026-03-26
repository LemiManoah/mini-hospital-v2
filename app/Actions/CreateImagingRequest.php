<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Consultation;
use App\Models\ImagingRequest;
use App\Models\PatientVisit;

final readonly class CreateImagingRequest
{
    public function handle(Consultation|PatientVisit $context, array $data, string $staffId): ImagingRequest
    {
        [$visit, $consultation] = $this->resolveContext($context);

        return ImagingRequest::query()->create([
            'visit_id' => $visit->id,
            'consultation_id' => $consultation?->id,
            'requested_by' => $staffId,
            'modality' => $data['modality'],
            'body_part' => $this->stringValue($data['body_part']),
            'laterality' => $data['laterality'] ?? 'na',
            'clinical_history' => $this->stringValue($data['clinical_history']),
            'indication' => $this->stringValue($data['indication']),
            'priority' => $data['priority'],
            'status' => 'requested',
            'requires_contrast' => (bool) ($data['requires_contrast'] ?? false),
            'contrast_allergy_status' => $this->nullableText($data['contrast_allergy_status'] ?? null),
            'pregnancy_status' => $data['pregnancy_status'] ?? 'unknown',
        ])->loadMissing('requestedBy:id,first_name,last_name');
    }

    /**
     * @return array{0: PatientVisit, 1: Consultation|null}
     */
    private function resolveContext(Consultation|PatientVisit $context): array
    {
        if ($context instanceof Consultation) {
            return [$context->visit()->firstOrFail(), $context];
        }

        return [$context, $context->consultation];
    }

    private function stringValue(mixed $value): string
    {
        return (string) $this->nullableText($value);
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
