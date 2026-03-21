<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\LabTestCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CreateLabRequest
{
    public function __construct(
        private SyncLabRequestCharge $syncLabRequestCharge,
    ) {}

    public function handle(Consultation $consultation, array $data, string $staffId): LabRequest
    {
        /** @var array<int, string> $testIds */
        $testIds = array_values(array_unique(array_filter($data['test_ids'] ?? [], is_string(...))));

        /** @var Collection<int, LabTestCatalog> $tests */
        $tests = LabTestCatalog::query()
            ->whereIn('id', $testIds)
            ->where('is_active', true)
            ->get(['id', 'base_price']);

        return DB::transaction(function () use ($consultation, $data, $staffId, $tests): LabRequest {
            $request = LabRequest::query()->create([
                'tenant_id' => $consultation->tenant_id,
                'facility_branch_id' => $consultation->facility_branch_id,
                'visit_id' => $consultation->visit_id,
                'consultation_id' => $consultation->id,
                'requested_by' => $staffId,
                'request_date' => now(),
                'clinical_notes' => $this->nullableText($data['clinical_notes'] ?? null),
                'priority' => $data['priority'],
                'status' => 'requested',
                'diagnosis_code' => $this->nullableText($data['diagnosis_code'] ?? $consultation->primary_icd10_code),
                'is_stat' => (bool) ($data['is_stat'] ?? false),
                'billing_status' => 'pending',
            ]);

            foreach ($tests as $test) {
                $request->items()->create([
                    'test_id' => $test->id,
                    'status' => 'pending',
                    'price' => $test->base_price ?? 0,
                    'is_external' => false,
                ]);
            }

            $request = $request->loadMissing([
                'visit.payer',
                'requestedBy:id,first_name,last_name',
                'items.test:id,test_name,test_code,category',
            ]);

            $this->syncLabRequestCharge->handle($request);

            return $request;
        });
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
