<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\PregnancyStatus;

final readonly class CreateImagingRequestDTO
{
    public function __construct(
        public ImagingModality $modality,
        public string $bodyPart,
        public ImagingLaterality $laterality,
        public string $clinicalHistory,
        public string $indication,
        public ImagingPriority $priority,
        public bool $requiresContrast,
        public ?string $contrastAllergyStatus,
        public PregnancyStatus $pregnancyStatus,
    ) {}

    /**
     * @param  array{
     *   modality: ImagingModality|string,
     *   body_part: string,
     *   laterality: ImagingLaterality|string,
     *   clinical_history: string,
     *   indication: string,
     *   priority: ImagingPriority|string,
     *   requires_contrast?: bool,
     *   contrast_allergy_status?: string|null,
     *   pregnancy_status: PregnancyStatus|string
     * }  $attributes
     */
    public static function fromRequest(array $attributes): self
    {
        return new self(
            modality: self::modality($attributes['modality']),
            bodyPart: self::requiredString($attributes['body_part']),
            laterality: self::laterality($attributes['laterality']),
            clinicalHistory: self::requiredString($attributes['clinical_history']),
            indication: self::requiredString($attributes['indication']),
            priority: self::priority($attributes['priority']),
            requiresContrast: $attributes['requires_contrast'] ?? false,
            contrastAllergyStatus: self::nullableString($attributes['contrast_allergy_status'] ?? null),
            pregnancyStatus: self::pregnancyStatus($attributes['pregnancy_status']),
        );
    }

    private static function modality(ImagingModality|string $modality): ImagingModality
    {
        return $modality instanceof ImagingModality
            ? $modality
            : ImagingModality::from($modality);
    }

    private static function laterality(ImagingLaterality|string $laterality): ImagingLaterality
    {
        return $laterality instanceof ImagingLaterality
            ? $laterality
            : ImagingLaterality::from($laterality);
    }

    private static function priority(ImagingPriority|string $priority): ImagingPriority
    {
        return $priority instanceof ImagingPriority
            ? $priority
            : ImagingPriority::from($priority);
    }

    private static function pregnancyStatus(PregnancyStatus|string $pregnancyStatus): PregnancyStatus
    {
        return $pregnancyStatus instanceof PregnancyStatus
            ? $pregnancyStatus
            : PregnancyStatus::from($pregnancyStatus);
    }

    private static function requiredString(string $value): string
    {
        return (string) self::nullableString($value);
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
