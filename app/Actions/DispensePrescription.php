<?php

declare(strict_types=1);

namespace App\Actions;

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

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Prescription $prescription, array $attributes, array $items): DispensingRecord
    {
        return DB::transaction(function () use ($prescription, $attributes, $items): DispensingRecord {
            $record = $this->createDispensingRecord->handle($prescription, $attributes, $items);

            $postItems = $record->items
                ->map(function (DispensingRecordItem $recordItem) use ($items): array {
                    $matchingItem = collect($items)->first(
                        static fn (array $item): bool => ($item['prescription_item_id'] ?? null) === $recordItem->prescription_item_id,
                    );

                    return [
                        'dispensing_record_item_id' => $recordItem->id,
                        'allocations' => is_array($matchingItem['allocations'] ?? null)
                            ? $matchingItem['allocations']
                            : [],
                    ];
                })
                ->values()
                ->all();

            return $this->postDispense->handle($record, $postItems);
        });
    }
}
