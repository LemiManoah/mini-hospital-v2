<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Pharmacy\DispensePrescriptionDTO;
use App\Data\Pharmacy\PostDispenseDTO;
use App\Data\Pharmacy\PostDispenseItemDTO;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItem;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;

final readonly class DispensePrescription
{
    public function __construct(
        private CreateDispensingRecord $createDispensingRecord,
        private PostDispense $postDispense,
    ) {}

    public function handle(Prescription $prescription, DispensePrescriptionDTO $data): DispensingRecord
    {
        return DB::transaction(function () use ($prescription, $data): DispensingRecord {
            $record = $this->createDispensingRecord->handle($prescription, $data->toCreateDispensingRecordDTO());

            $sourceItemsByPrescriptionItem = collect($data->items)
                ->mapWithKeys(static fn ($item): array => [
                    $item->prescriptionItemId => $item,
                ]);

            $postDto = new PostDispenseDTO(
                items: $record->items
                    ->map(function (DispensingRecordItem $recordItem) use ($sourceItemsByPrescriptionItem): PostDispenseItemDTO {
                        $matchingItem = $sourceItemsByPrescriptionItem->get($recordItem->prescription_item_id);

                        return new PostDispenseItemDTO(
                            dispensingRecordItemId: $recordItem->id,
                            allocations: $matchingItem?->allocations ?? [],
                        );
                    })
                    ->values()
                    ->all(),
            );

            return $this->postDispense->handle($record, $postDto);
        });
    }
}
