<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\UpdateLabRequestDTO;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTestCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateLabRequest
{
    public function __construct(
        private SyncLabRequestCharge $syncLabRequestCharge,
    ) {}

    public function handle(LabRequest $labRequest, UpdateLabRequestDTO $data): LabRequest
    {
        /** @var Collection<int, LabTestCatalog> $tests */
        $tests = LabTestCatalog::query()
            ->whereIn('id', $data->testIds)
            ->where('is_active', true)
            ->get(['id', 'base_price']);

        $this->ensureNoPendingDuplicates($labRequest, $data->testIds);

        return DB::transaction(function () use ($labRequest, $data, $tests): LabRequest {
            $labRequest->forceFill([
                'clinical_notes' => $data->clinicalNotes,
                'priority' => $data->priority,
                'diagnosis_code' => $data->diagnosisCode,
                'is_stat' => $data->isStat,
            ])->save();

            $labRequest->items()->delete();

            foreach ($tests as $test) {
                $labRequest->items()->create([
                    'test_id' => $test->id,
                    'status' => 'pending',
                    'price' => $test->base_price ?? 0,
                    'is_external' => false,
                ]);
            }

            $labRequest->loadMissing(['visit.payer']);
            $labRequest->unsetRelation('items');
            $labRequest->load([
                'requestedBy:id,first_name,last_name',
                'items.test:id,test_name,test_code,lab_test_category_id,result_type_id',
                'items.test.labCategory:id,name',
                'items.test.specimenTypes:id,name',
                'items.test.resultTypeDefinition:id,code,name',
            ]);

            $this->syncLabRequestCharge->handle($labRequest);

            return $labRequest->refresh()->load([
                'requestedBy:id,first_name,last_name',
                'items.test:id,test_name,test_code,lab_test_category_id,result_type_id',
                'items.test.labCategory:id,name',
                'items.test.specimenTypes:id,name',
                'items.test.resultTypeDefinition:id,code,name',
            ]);
        });
    }

    /**
     * @param  array<int, string>  $testIds
     */
    private function ensureNoPendingDuplicates(LabRequest $labRequest, array $testIds): void
    {
        if ($testIds === []) {
            return;
        }

        $hasPendingDuplicate = LabRequestItem::query()
            ->whereIn('test_id', $testIds)
            ->where('status', 'pending')
            ->where('request_id', '!=', $labRequest->id)
            ->whereHas('request', static function (Builder $query) use ($labRequest): void {
                $query->where('visit_id', $labRequest->visit_id);
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
