<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateLabOrderDTO;
use App\Enums\VisitStatus;
use App\Models\ChargeMaster;
use App\Models\Consultation;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;
use App\Notifications\LabOrderCreatedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateLabOrder
{
    public function __construct(
        private SyncLabTestCatalogChargeMaster $syncLabTestCatalogChargeMaster,
        private SyncLabOrderCharge $syncLabOrderCharge,
        private TransitionPatientVisitStatus $transitionStatus,
        private RecordAuditActivity $recordAuditActivity,
        private NotifyUsersWithPermission $notifyUsersWithPermission,
    ) {}

    public function handle(Consultation|PatientVisit $context, CreateLabOrderDTO $data, string $staffId): LabOrder
    {
        [$visit, $consultation] = $this->resolveContext($context);

        /** @var Collection<int, LabTestCatalog> $tests */
        $tests = LabTestCatalog::query()
            ->with('chargeMaster')
            ->whereIn('id', $data->testIds)
            ->where('is_active', true)
            ->get(['id', 'tenant_id', 'test_code', 'test_name', 'base_price', 'charge_master_id', 'is_active']);

        $this->ensureNoPendingDuplicates($visit, $data->testIds);

        $request = DB::transaction(function () use ($visit, $consultation, $data, $staffId, $tests): LabOrder {
            $request = LabOrder::query()->create([
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
                    'price' => $this->priceFor($test),
                    'is_external' => false,
                ]);
            }

            $request = $request->loadMissing([
                'visit.payer',
                'requestedBy:id,first_name,last_name',
                'items.test:id,tenant_id,test_name,test_code,lab_test_category_id,result_type_id,base_price,charge_master_id,is_active',
                'items.test.chargeMaster',
                'items.test.labCategory:id,name',
                'items.test.specimenTypes:id,name',
                'items.test.resultTypeDefinition:id,code,name',
            ]);

            $this->syncLabOrderCharge->handle($request);
            $this->ensureVisitInProgress($visit);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_order.created',
                subject: $request,
                description: 'Laboratory request created.',
                tenantId: $request->tenant_id,
                branchId: $request->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'visit_id' => $request->visit_id,
                    'consultation_id' => $request->consultation_id,
                    'test_ids' => $request->items->pluck('test_id')->all(),
                    'priority' => $request->priority?->value,
                    'is_stat' => $request->is_stat,
                ],
            );

            return $request;
        });

        if ($visit->tenant_id !== null) {
            $this->notifyUsersWithPermission->handle(
                $visit->tenant_id,
                ['lab_orders.view', 'lab_orders.update'],
                new LabOrderCreatedNotification($request),
            );
        }

        return $request;
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

    private function priceFor(LabTestCatalog $test): float
    {
        $test->loadMissing('chargeMaster');

        $chargeMaster = $test->chargeMaster instanceof ChargeMaster
            ? $test->chargeMaster
            : $this->syncLabTestCatalogChargeMaster->handle($test);

        return $chargeMaster instanceof ChargeMaster
            ? (float) $chargeMaster->unit_price
            : (float) ($test->base_price ?? 0);
    }

    /**
     * @param  array<int, string>  $testIds
     */
    private function ensureNoPendingDuplicates(PatientVisit $visit, array $testIds): void
    {
        if ($testIds === []) {
            return;
        }

        $hasPendingDuplicate = LabOrderItem::query()
            ->whereIn('test_id', $testIds)
            ->where('status', 'pending')
            ->whereHas('order', static function (Builder $query) use ($visit): void {
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
