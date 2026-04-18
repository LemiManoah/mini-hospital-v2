<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\DispensingRecordStatus;
use App\Models\DispensingRecordItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class PrescriptionDispenseProgress
{
    /**
     * @return Collection<string, array{
     *     dispensed_quantity: float,
     *     external_quantity: float,
     *     covered_quantity: float,
     *     latest_dispensed_at: Carbon|null,
     *     external_pharmacy: bool
     * }>
     */
    public function postedLineSummaries(string $prescriptionId, ?string $ignoreRecordId = null): Collection
    {
        $query = DispensingRecordItem::query()
            ->selectRaw('dispensing_record_items.prescription_item_id')
            ->selectRaw('SUM(dispensing_record_items.dispensed_quantity) as dispensed_quantity')
            ->selectRaw('SUM(CASE WHEN dispensing_record_items.external_pharmacy = 1 THEN dispensing_record_items.balance_quantity ELSE 0 END) as external_quantity')
            ->selectRaw('MAX(dispensing_records.dispensed_at) as latest_dispensed_at')
            ->selectRaw('MAX(CASE WHEN dispensing_record_items.external_pharmacy = 1 THEN 1 ELSE 0 END) as external_pharmacy')
            ->join('dispensing_records', 'dispensing_records.id', '=', 'dispensing_record_items.dispensing_record_id')
            ->where('dispensing_records.prescription_id', $prescriptionId)
            ->where('dispensing_records.status', DispensingRecordStatus::POSTED->value);

        if (is_string($ignoreRecordId) && $ignoreRecordId !== '') {
            $query->where('dispensing_records.id', '!=', $ignoreRecordId);
        }

        return $query
            ->groupBy('dispensing_record_items.prescription_item_id')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                (string) $row->prescription_item_id => [
                    'dispensed_quantity' => (float) $row->dispensed_quantity,
                    'external_quantity' => (float) $row->external_quantity,
                    'covered_quantity' => round(
                        (float) $row->dispensed_quantity + (float) $row->external_quantity,
                        3,
                    ),
                    'latest_dispensed_at' => $row->latest_dispensed_at !== null
                        ? Date::parse((string) $row->latest_dispensed_at)
                        : null,
                    'external_pharmacy' => (bool) $row->external_pharmacy,
                ],
            ]);
    }
}
