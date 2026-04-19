<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabSpecimen;
use App\Models\LabTestCatalog;
use App\Models\SpecimenType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        /** @var LabTestCatalog|null $labTest */
        $labTest = $labRequestItem->test()->first();

        /** @var SpecimenType|null $specimenType */
        $specimenType = $labTest instanceof LabTestCatalog
            ? $labTest->specimenTypes()
                ->whereKey($payload['specimen_type_id'] ?? null)
                ->first(['specimen_types.id', 'specimen_types.name'])
            : null;

        if (! $specimenType instanceof SpecimenType) {
            throw ValidationException::withMessages([
                'specimen_type_id' => 'Choose one of the specimen types configured for this test.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $payload, $staffId, $specimenType): LabRequestItem {
            /** @var LabSpecimen|null $existingSpecimen */
            $existingSpecimen = $labRequestItem->specimen()->first();
            $collectedAt = $existingSpecimen instanceof LabSpecimen
                ? $existingSpecimen->collected_at ?? now()
                : now();
            $accessionNumber = $existingSpecimen instanceof LabSpecimen
                ? $existingSpecimen->accession_number ?? $this->generateAccessionNumber()
                : $this->generateAccessionNumber();

            $labRequestItem->specimen()->updateOrCreate(
                [],
                [
                    'accession_number' => $accessionNumber,
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

            /** @var LabRequest $labRequest */
            $labRequest = $labRequestItem->request()->firstOrFail();

            $this->syncLabRequestProgress->handle($labRequest);

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
            $candidate = sprintf('LAB-%s-%s', now()->format('YmdHis'), Str::upper(Str::random(4)));
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
