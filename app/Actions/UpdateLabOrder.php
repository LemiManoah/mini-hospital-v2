<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\UpdateLabOrderDTO;
use App\Models\ChargeMaster;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTestCatalog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateLabOrder
{
    public function __construct(
        private SyncLabTestCatalogChargeMaster $syncLabTestCatalogChargeMaster,
        private SyncLabOrderCharge $syncLabOrderCharge,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabOrder $labOrder, UpdateLabOrderDTO $data): LabOrder
    {
        /** @var Collection<int, LabTestCatalog> $tests */
        $tests = LabTestCatalog::query()
            ->with('chargeMaster')
            ->whereIn('id', $data->testIds)
            ->where('is_active', true)
            ->get(['id', 'tenant_id', 'test_code', 'test_name', 'base_price', 'charge_master_id', 'is_active']);

        $this->ensureNoPendingDuplicates($labOrder, $data->testIds);

        return DB::transaction(function () use ($labOrder, $data, $tests): LabOrder {
            $user = Auth::user();
            $oldValues = [
                'test_ids' => $labOrder->items()->pluck('test_id')->all(),
                'clinical_notes' => $labOrder->clinical_notes,
                'priority' => $labOrder->priority?->value,
                'diagnosis_code' => $labOrder->diagnosis_code,
                'is_stat' => $labOrder->is_stat,
            ];

            $labOrder->forceFill([
                'clinical_notes' => $data->clinicalNotes,
                'priority' => $data->priority,
                'diagnosis_code' => $data->diagnosisCode,
                'is_stat' => $data->isStat,
            ])->save();

            $labOrder->items()->delete();

            foreach ($tests as $test) {
                $labOrder->items()->create([
                    'test_id' => $test->id,
                    'status' => 'pending',
                    'price' => $this->priceFor($test),
                    'is_external' => false,
                ]);
            }

            $labOrder->loadMissing(['visit.payer']);
            $labOrder->unsetRelation('items');
            $labOrder->load([
                'requestedBy:id,first_name,last_name',
                'items.test:id,tenant_id,test_name,test_code,lab_test_category_id,result_type_id,base_price,charge_master_id,is_active',
                'items.test.chargeMaster',
                'items.test.labCategory:id,name',
                'items.test.specimenTypes:id,name',
                'items.test.resultTypeDefinition:id,code,name',
            ]);

            $this->syncLabOrderCharge->handle($labOrder);

            $labOrder = $labOrder->refresh()->load([
                'requestedBy:id,first_name,last_name',
                'items.test:id,tenant_id,test_name,test_code,lab_test_category_id,result_type_id,base_price,charge_master_id,is_active',
                'items.test.chargeMaster',
                'items.test.labCategory:id,name',
                'items.test.specimenTypes:id,name',
                'items.test.resultTypeDefinition:id,code,name',
            ]);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_order.updated',
                subject: $labOrder,
                description: 'Laboratory request updated.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $labOrder->requested_by,
                oldValues: $oldValues,
                newValues: [
                    'test_ids' => $labOrder->items->pluck('test_id')->all(),
                    'clinical_notes' => $labOrder->clinical_notes,
                    'priority' => $labOrder->priority?->value,
                    'diagnosis_code' => $labOrder->diagnosis_code,
                    'is_stat' => $labOrder->is_stat,
                ],
            );

            return $labOrder;
        });
    }

    /**
     * @param  array<int, string>  $testIds
     */
    private function ensureNoPendingDuplicates(LabOrder $labOrder, array $testIds): void
    {
        if ($testIds === []) {
            return;
        }

        $hasPendingDuplicate = LabOrderItem::query()
            ->whereIn('test_id', $testIds)
            ->where('status', 'pending')
            ->where('lab_order_id', '!=', $labOrder->id)
            ->whereHas('order', static function (Builder $query) use ($labOrder): void {
                $query->where('visit_id', $labOrder->visit_id);
            })
            ->exists();

        if (! $hasPendingDuplicate) {
            return;
        }

        throw ValidationException::withMessages([
            'test_ids' => 'One or more selected lab tests already have pending orders for this visit.',
        ]);
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
}
