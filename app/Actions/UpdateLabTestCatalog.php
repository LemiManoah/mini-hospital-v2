<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\UpdateLabTestCatalogDTO;
use App\Models\LabTestCatalog;
use Illuminate\Support\Facades\DB;

final readonly class UpdateLabTestCatalog
{
    public function __construct(
        private SyncLabTestCatalogConfiguration $syncLabTestCatalogConfiguration,
    ) {}

    public function handle(LabTestCatalog $labTestCatalog, UpdateLabTestCatalogDTO $data): LabTestCatalog
    {
        return DB::transaction(function () use ($labTestCatalog, $data): LabTestCatalog {
            $labTestCatalog->update($data->toAttributes());

            $this->syncLabTestCatalogConfiguration->handle($labTestCatalog, $data);

            return $labTestCatalog->refresh()->load([
                'labCategory:id,name',
                'specimenTypes:id,name',
                'resultTypeDefinition:id,code,name',
                'resultOptions:id,lab_test_catalog_id,label,sort_order,is_active',
                'resultParameters:id,lab_test_catalog_id,label,unit,reference_range,value_type,sort_order,is_active',
            ]);
        });
    }
}
