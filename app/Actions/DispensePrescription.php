<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Pharmacy\DispensePrescriptionDTO;
use App\Data\Pharmacy\DispensePrescriptionItemDTO;
use App\Data\Pharmacy\PostDispenseDTO;
use App\Data\Pharmacy\PostDispenseItemDTO;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItem;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

            /** @var array<string, DispensePrescriptionItemDTO> $sourceItemsByPrescriptionItem */
            $sourceItemsByPrescriptionItem = collect($data->items)
                ->mapWithKeys(static fn (DispensePrescriptionItemDTO $item): array => [
                    $item->prescriptionItemId => $item,
                ]);

            /** @var list<PostDispenseItemDTO> $postItems */
            $postItems = $record->items
                ->map(function (DispensingRecordItem $recordItem) use ($sourceItemsByPrescriptionItem): PostDispenseItemDTO {
                    $matchingItem = $sourceItemsByPrescriptionItem[$recordItem->prescription_item_id] ?? null;
                    throw_unless($matchingItem instanceof DispensePrescriptionItemDTO, RuntimeException::class, 'Dispensing record item could not be matched back to the prescription dispense payload.');

                    return new PostDispenseItemDTO(
                        dispensingRecordItemId: $recordItem->id,
                        allocations: $matchingItem->allocations,
                    );
                })
                ->values()
                ->all();

            $postDto = new PostDispenseDTO(
                items: $postItems,
            );

            return $this->postDispense->handle($record, $postDto);
        });
    }
}
