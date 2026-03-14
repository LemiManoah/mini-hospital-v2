<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Consultation;
use App\Models\Drug;
use App\Models\Prescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CreatePrescription
{
    public function handle(Consultation $consultation, array $data, string $staffId): Prescription
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = $data['items'] ?? [];

        /** @var Collection<int, Drug> $drugs */
        $drugs = Drug::query()
            ->whereIn('id', collect($items)->pluck('drug_id')->filter()->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($consultation, $data, $staffId, $items, $drugs): Prescription {
            $prescription = Prescription::query()->create([
                'visit_id' => $consultation->visit_id,
                'consultation_id' => $consultation->id,
                'prescribed_by' => $staffId,
                'prescription_date' => now(),
                'is_discharge_medication' => (bool) ($data['is_discharge_medication'] ?? false),
                'is_long_term' => (bool) ($data['is_long_term'] ?? false),
                'primary_diagnosis' => $this->nullableText($data['primary_diagnosis'] ?? $consultation->primary_diagnosis),
                'pharmacy_notes' => $this->nullableText($data['pharmacy_notes'] ?? null),
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                $drug = $drugs->get($item['drug_id']);
                if ($drug === null) {
                    continue;
                }

                $prescription->items()->create([
                    'drug_id' => $drug->id,
                    'dosage' => $this->stringValue($item['dosage'] ?? null),
                    'frequency' => $this->stringValue($item['frequency'] ?? null),
                    'route' => $this->stringValue($item['route'] ?? null),
                    'duration_days' => (int) ($item['duration_days'] ?? 0),
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'instructions' => $this->nullableText($item['instructions'] ?? null),
                    'is_prn' => (bool) ($item['is_prn'] ?? false),
                    'prn_reason' => $this->nullableText($item['prn_reason'] ?? null),
                    'is_external_pharmacy' => (bool) ($item['is_external_pharmacy'] ?? false),
                    'status' => 'pending',
                ]);
            }

            return $prescription->loadMissing([
                'prescribedBy:id,first_name,last_name',
                'items.drug:id,generic_name,brand_name,strength,dosage_form',
            ]);
        });
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
