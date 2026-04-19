<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\Prescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CreatePrescription
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Consultation|PatientVisit $context, array $data, string $staffId): Prescription
    {
        [$visit, $consultation] = $this->resolveContext($context);

        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        /** @var Collection<int, InventoryItem> $inventoryItems */
        $inventoryItems = InventoryItem::query()
            ->drugs()
            ->whereIn('id', collect($items)->pluck('inventory_item_id')->filter()->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($visit, $consultation, $data, $staffId, $items, $inventoryItems): Prescription {
            $prescription = Prescription::query()->create([
                'visit_id' => $visit->id,
                'consultation_id' => $consultation?->id,
                'prescribed_by' => $staffId,
                'prescription_date' => now(),
                'is_discharge_medication' => (bool) ($data['is_discharge_medication'] ?? false),
                'is_long_term' => (bool) ($data['is_long_term'] ?? false),
                'primary_diagnosis' => $this->nullableText($data['primary_diagnosis'] ?? $consultation?->primary_diagnosis),
                'pharmacy_notes' => $this->nullableText($data['pharmacy_notes'] ?? null),
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                $inventoryItemId = is_string($item['inventory_item_id'] ?? null) ? $item['inventory_item_id'] : null;
                $inventoryItem = $inventoryItems->get($inventoryItemId);
                if ($inventoryItem === null) {
                    continue;
                }

                $prescription->items()->create([
                    'inventory_item_id' => $inventoryItem->id,
                    'dosage' => $this->stringValue($item['dosage'] ?? null),
                    'frequency' => $this->stringValue($item['frequency'] ?? null),
                    'route' => $this->stringValue($item['route'] ?? null),
                    'duration_days' => is_numeric($item['duration_days'] ?? null) ? (int) $item['duration_days'] : 0,
                    'quantity' => is_numeric($item['quantity'] ?? null) ? (int) $item['quantity'] : 0,
                    'instructions' => $this->nullableText($item['instructions'] ?? null),
                    'is_prn' => (bool) ($item['is_prn'] ?? false),
                    'prn_reason' => $this->nullableText($item['prn_reason'] ?? null),
                    'is_external_pharmacy' => (bool) ($item['is_external_pharmacy'] ?? false),
                    'status' => 'pending',
                ]);
            }

            $prescription = $prescription->loadMissing([
                'prescribedBy:id,first_name,last_name',
                'items.inventoryItem:id,generic_name,brand_name,strength,dosage_form',
            ]);

            $this->ensureVisitInProgress($visit);

            return $prescription;
        });
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

    private function ensureVisitInProgress(PatientVisit $visit): void
    {
        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }
    }
}
