<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateLabRequestDTO;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateLabRequest
{
    public function __construct(
        private SyncLabRequestCharge $syncLabRequestCharge,
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    public function handle(Consultation|PatientVisit $context, CreateLabRequestDTO $data, string $staffId): LabRequest
    {
        [$visit, $consultation] = $this->resolveContext($context);

        /** @var Collection<int, LabTestCatalog> $tests */
        $tests = LabTestCatalog::query()
            ->whereIn('id', $data->testIds)
            ->where('is_active', true)
            ->get(['id', 'base_price']);

        $this->ensureNoPendingDuplicates($visit, $data->testIds);

        return DB::transaction(function () use ($visit, $consultation, $data, $staffId, $tests): LabRequest {
            $request = LabRequest::query()->create([
                'tenant_id' => $visit->tenant_id,
                'facility_branch_id' => $visit->facility_branch_id,
                'visit_id' => $visit->id,
                'consultation_id' => $consultation?->id,
                'requested_by' => $staffId,
                'request_date' => now(),
                'clinical_notes' => $data->clinicalNotes,
                'priority' => $data->priority,
                'status' => 'requested',
                'diagnosis_code' => $data->diagnosisCode ?? $consultation?->primary_icd10_code,
                'is_stat' => $data->isStat,
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
                'items.test:id,test_name,test_code,lab_test_category_id,result_type_id',
                'items.test.labCategory:id,name',
                'items.test.specimenTypes:id,name',
                'items.test.resultTypeDefinition:id,code,name',
            ]);

            $this->syncLabRequestCharge->handle($request);
            $this->ensureVisitInProgress($visit);

            return $request;
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

    private function ensureVisitInProgress(PatientVisit $visit): void
    {
        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }
    }

    /**
     * @param  array<int, string>  $testIds
     */
    private function ensureNoPendingDuplicates(PatientVisit $visit, array $testIds): void
    {
        if ($testIds === []) {
            return;
        }

        $hasPendingDuplicate = LabRequestItem::query()
            ->whereIn('test_id', $testIds)
            ->where('status', 'pending')
            ->whereHas('request', static function (Builder $query) use ($visit): void {
                $query->where('visit_id', $visit->id);
            })
            ->exists();

        if (! $hasPendingDuplicate) {
            return;
        }

        throw ValidationException::withMessages([
            'test_ids' => 'One or more selected lab tests already have pending orders for this visit.',
        ]);
    }
}
