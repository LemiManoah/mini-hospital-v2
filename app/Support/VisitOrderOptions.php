<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\InsurancePolicyType;
use App\Enums\PregnancyStatus;
use App\Enums\Priority;
use App\Models\FacilityService;
use App\Models\ImagingStudyCatalog;
use App\Models\InsurancePolicyItem;
use App\Models\InventoryItem;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;

final readonly class VisitOrderOptions
{
    /**
     * @return array<string, mixed>
     */
    public function forVisit(PatientVisit $visit): array
    {
        $labTests = LabTestCatalog::query()
            ->where('is_active', true)
            ->with(['chargeMaster', 'labCategory:id,name'])
            ->orderBy('test_name')
            ->get(['id', 'test_code', 'test_name', 'lab_test_category_id', 'base_price', 'charge_master_id']);

        $drugs = InventoryItem::query()
            ->drugs()
            ->where('is_active', true)
            ->with('chargeMaster')
            ->orderBy('generic_name')
            ->get(['id', 'generic_name', 'brand_name', 'strength', 'dosage_form', 'default_selling_price', 'charge_master_id']);

        $facilityServices = FacilityService::query()
            ->where('is_active', true)
            ->with('chargeMaster')
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'service_code', 'name', 'category', 'selling_price', 'charge_master_id', 'is_billable']);

        $imagingStudies = ImagingStudyCatalog::query()
            ->where('is_active', true)
            ->with('chargeMaster')
            ->orderBy('modality')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'modality', 'body_part', 'base_price', 'charge_master_id']);

        $labPriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::TEST, $this->normalizeStringIds($labTests->pluck('id')->all()));
        $drugPriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::DRUG, $this->normalizeStringIds($drugs->pluck('id')->all()));
        $servicePriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::SERVICE, $this->normalizeStringIds($facilityServices->pluck('id')->all()));
        $imagingPriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::IMAGING, $this->normalizeStringIds($imagingStudies->pluck('id')->all()));

        return [
            'labTestOptions' => $labTests
                ->map(fn (LabTestCatalog $test): array => [
                    'id' => $test->id,
                    'test_code' => $test->test_code,
                    'test_name' => $test->test_name,
                    'category' => $test->category,
                    'base_price' => $test->base_price,
                    'quoted_price' => $labPriceMap[$test->id] ?? $this->chargeMasterPrice($test) ?? $test->base_price,
                    'price_source' => isset($labPriceMap[$test->id]) ? 'insurance_package' : 'catalog_base',
                ])
                ->all(),
            'drugOptions' => $drugs
                ->map(fn (InventoryItem $drug): array => [
                    'id' => $drug->id,
                    'generic_name' => $drug->generic_name,
                    'brand_name' => $drug->brand_name,
                    'strength' => $drug->strength,
                    'dosage_form' => $drug->dosage_form?->value,
                    'default_selling_price' => $drug->default_selling_price,
                    'quoted_price' => $drugPriceMap[$drug->id] ?? $this->chargeMasterPrice($drug) ?? $drug->default_selling_price,
                    'price_source' => isset($drugPriceMap[$drug->id]) ? 'insurance_package' : 'catalog_base',
                ])
                ->all(),
            'labPriorities' => collect(Priority::cases())
                ->map(static fn (Priority $priority): array => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                ])
                ->values()
                ->all(),
            'imagingModalities' => collect(ImagingModality::cases())
                ->map(static fn (ImagingModality $modality): array => [
                    'value' => $modality->value,
                    'label' => $modality->label(),
                ])
                ->values()
                ->all(),
            'imagingPriorities' => collect(ImagingPriority::cases())
                ->map(static fn (ImagingPriority $priority): array => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                ])
                ->values()
                ->all(),
            'imagingLateralities' => collect(ImagingLaterality::cases())
                ->map(static fn (ImagingLaterality $laterality): array => [
                    'value' => $laterality->value,
                    'label' => $laterality->label(),
                ])
                ->values()
                ->all(),
            'pregnancyStatuses' => collect(PregnancyStatus::cases())
                ->map(static fn (PregnancyStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
            'imagingStudyOptions' => $imagingStudies
                ->map(fn (ImagingStudyCatalog $study): array => [
                    'id' => $study->id,
                    'code' => $study->code,
                    'name' => $study->name,
                    'modality' => $study->modality->value,
                    'body_part' => $study->body_part,
                    'base_price' => $study->base_price,
                    'quoted_price' => $imagingPriceMap[$study->id] ?? $this->chargeMasterPrice($study) ?? $study->base_price,
                    'price_source' => isset($imagingPriceMap[$study->id]) ? 'insurance_package' : 'catalog_base',
                ])
                ->all(),
            'facilityServiceOptions' => $facilityServices
                ->map(fn (FacilityService $service): array => [
                    'id' => $service->id,
                    'service_code' => $service->service_code,
                    'name' => $service->name,
                    'category' => $service->category->value,
                    'selling_price' => $service->selling_price,
                    'quoted_price' => $servicePriceMap[$service->id] ?? $this->chargeMasterPrice($service) ?? $service->selling_price,
                    'price_source' => isset($servicePriceMap[$service->id]) ? 'insurance_package' : 'catalog_base',
                    'is_billable' => $service->is_billable,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<int, string>  $billableIds
     * @return array<string, float>
     */
    private function activeInsurancePriceMap(PatientVisit $visit, BillableItemType $type, array $billableIds): array
    {
        $insurancePackageId = $visit->payer?->insurance_package_id;
        $branchId = $visit->facility_branch_id;
        $policyType = InsurancePolicyType::fromBillableItemType($type);

        if ($insurancePackageId === null || $billableIds === [] || ! $policyType instanceof InsurancePolicyType) {
            return [];
        }

        $today = now()->toDateString();

        return InsurancePolicyItem::query()
            ->where('tenant_id', $visit->tenant_id)
            ->where('item_type', $type->value)
            ->whereIn('item_id', $billableIds)
            ->where('status', GeneralStatus::ACTIVE->value)
            ->whereHas('policy', function (Builder $query) use ($branchId, $insurancePackageId, $policyType, $today): void {
                $query
                    ->where('facility_branch_id', $branchId)
                    ->where('insurance_package_id', $insurancePackageId)
                    ->where('policy_type', $policyType->value)
                    ->where('status', GeneralStatus::ACTIVE->value)
                    ->where(function (Builder $rangeQuery) use ($today): void {
                        $rangeQuery->whereNull('effective_from')
                            ->orWhere('effective_from', '<=', $today);
                    })
                    ->where(function (Builder $rangeQuery) use ($today): void {
                        $rangeQuery->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $today);
                    });
            })
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $today);
            })
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->get(['item_id', 'price'])
            ->unique('item_id')
            ->mapWithKeys(static fn (InsurancePolicyItem $price): array => [
                $price->item_id => (float) $price->price,
            ])
            ->all();
    }

    /**
     * @param  array<array-key, mixed>  $ids
     * @return list<string>
     */
    private function normalizeStringIds(array $ids): array
    {
        return array_values(array_filter(
            $ids,
            static fn (mixed $id): bool => is_string($id) && $id !== '',
        ));
    }

    private function chargeMasterPrice(FacilityService|ImagingStudyCatalog|InventoryItem|LabTestCatalog $model): ?float
    {
        return $model->chargeMaster === null ? null : (float) $model->chargeMaster->unit_price;
    }
}
