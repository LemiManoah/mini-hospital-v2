<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequestItem;
use App\Models\LabSpecimen;
use App\Models\SpecimenType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CollectLabSpecimen
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(LabRequestItem $labRequestItem, array $payload, string $staffId): LabRequestItem
    {
        if ($labRequestItem->status === LabRequestItemStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Cancelled lab items cannot have samples picked.',
            ]);
        }

        if ($labRequestItem->approved_at !== null || $labRequestItem->status === LabRequestItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Released lab items cannot have their sample picking changed.',
            ]);
        }

        $labRequestItem->loadMissing('test.specimenTypes:id,name');

        /** @var SpecimenType|null $specimenType */
        $specimenType = $labRequestItem->test?->specimenTypes
            ?->firstWhere('id', $payload['specimen_type_id'] ?? null);

        if (! $specimenType instanceof SpecimenType) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Choose one of the specimen types configured for this test.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $payload, $staffId, $specimenType): LabRequestItem {
            $existingSpecimen = $labRequestItem->specimen()->first();
            $collectedAt = $existingSpecimen?->collected_at ?? now();

            $labRequestItem->specimen()->updateOrCreate(
                [],
                [
                    'accession_number' => $existingSpecimen?->accession_number ?? $this->generateAccessionNumber(),
                    'specimen_type_id' => $specimenType->id,
                    'specimen_type_name' => $specimenType->name,
                    'status' => LabSpecimenStatus::COLLECTED,
                    'collected_by' => $staffId,
                    'collected_at' => $collectedAt,
                    'outside_sample' => (bool) ($payload['outside_sample'] ?? false),
                    'outside_sample_origin' => $this->nullableText($payload['outside_sample_origin'] ?? null),
                    'notes' => $this->nullableText($payload['notes'] ?? null),
                ],
            );

            $labRequestItem->forceFill([
                'received_by' => $staffId,
                'received_at' => $labRequestItem->received_at ?? $collectedAt,
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh()->loadMissing([
                'specimen',
                'specimen.specimenType',
                'specimen.collectedBy',
            ]);
        });
    }

    private function generateAccessionNumber(): string
    {
        do {
            $candidate = sprintf('LAB-%s-%s', now()->format('YmdHis'), mb_strtoupper((string) str()->random(4)));
        } while (LabSpecimen::query()->where('accession_number', $candidate)->exists());

        return $candidate;
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
