<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\PregnancyStatus;
use App\Enums\Priority;
use App\Models\FacilityService;
use App\Models\InventoryItem;
use App\Models\InsurancePackagePrice;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;

final readonly class VisitOrderOptions
{
    /**
     * @return array<string, mixed>
     */
    public function forVisit(PatientVisit $visit): array
    {
        $labTests = LabTestCatalog::query()
            ->where('is_active', true)
            ->with('labCategory:id,name')
            ->orderBy('test_name')
            ->get(['id', 'test_code', 'test_name', 'lab_test_category_id', 'base_price']);

        $drugs = InventoryItem::query()
            ->drugs()
            ->where('is_active', true)
            ->orderBy('generic_name')
            ->get(['id', 'generic_name', 'brand_name', 'strength', 'dosage_form']);

        $facilityServices = FacilityService::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'service_code', 'name', 'category', 'selling_price', 'is_billable']);

        $labPriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::TEST, $labTests->pluck('id')->all());
        $drugPriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::DRUG, $drugs->pluck('id')->all());
        $servicePriceMap = $this->activeInsurancePriceMap($visit, BillableItemType::SERVICE, $facilityServices->pluck('id')->all());

        return [
            'labTestOptions' => $labTests
                ->map(static fn (LabTestCatalog $test): array => [
                    'id' => $test->id,
                    'test_code' => $test->test_code,
                    'test_name' => $test->test_name,
                    'category' => $test->category,
                    'base_price' => $test->base_price,
                    'quoted_price' => $labPriceMap[$test->id] ?? $test->base_price,
                    'price_source' => isset($labPriceMap[$test->id]) ? 'insurance_package' : 'catalog_base',
                ])
                ->all(),
            'drugOptions' => $drugs
                ->map(static fn (InventoryItem $drug): array => [
                    'id' => $drug->id,
                    'generic_name' => $drug->generic_name,
                    'brand_name' => $drug->brand_name,
                    'strength' => $drug->strength,
                    'dosage_form' => $drug->dosage_form?->value,
                    'quoted_price' => $drugPriceMap[$drug->id] ?? null,
                    'price_source' => isset($drugPriceMap[$drug->id]) ? 'insurance_package' : null,
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
            'facilityServiceOptions' => $facilityServices
                ->map(static fn (FacilityService $service): array => [
                    'id' => $service->id,
                    'service_code' => $service->service_code,
                    'name' => $service->name,
                    'category' => $service->category->value,
                    'selling_price' => $service->selling_price,
                    'quoted_price' => $servicePriceMap[$service->id] ?? $service->selling_price,
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

        if ($insurancePackageId === null || $branchId === null || $billableIds === []) {
            return [];
        }

        $today = now()->toDateString();

        return InsurancePackagePrice::query()
            ->where('tenant_id', $visit->tenant_id)
            ->where('facility_branch_id', $branchId)
            ->where('insurance_package_id', $insurancePackageId)
            ->where('billable_type', $type->value)
            ->whereIn('billable_id', $billableIds)
            ->where('status', GeneralStatus::ACTIVE->value)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->get(['billable_id', 'price'])
            ->unique('billable_id')
            ->mapWithKeys(static fn (InsurancePackagePrice $price): array => [
                $price->billable_id => (float) $price->price,
            ])
            ->all();
    }
}
