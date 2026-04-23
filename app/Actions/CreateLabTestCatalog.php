<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateLabTestCatalogDTO;
use App\Models\LabTestCatalog;
use Illuminate\Support\Facades\DB;

final readonly class CreateLabTestCatalog
{
    public function __construct(
        private SyncLabTestCatalogConfiguration $syncLabTestCatalogConfiguration,
    ) {}

    public function handle(CreateLabTestCatalogDTO $data): LabTestCatalog
    {
        return DB::transaction(function () use ($data): LabTestCatalog {
            $labTestCatalog = LabTestCatalog::query()->create($data->toAttributes());

            $this->syncLabTestCatalogConfiguration->handle($labTestCatalog, $data);

            return $labTestCatalog->load([
                'labCategory:id,name',
                'specimenTypes:id,name',
                'resultTypeDefinition:id,code,name',
                'resultOptions:id,lab_test_catalog_id,label,sort_order,is_active',
                'resultParameters:id,lab_test_catalog_id,label,unit,reference_range,value_type,sort_order,is_active',
            ]);
        });
    }
}
