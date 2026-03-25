<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabTestCatalog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateLabTestCatalog
{
    public function __construct(
        private SyncLabTestCatalogConfiguration $syncLabTestCatalogConfiguration,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(LabTestCatalog $labTestCatalog, array $attributes): LabTestCatalog
    {
        return DB::transaction(function () use ($labTestCatalog, $attributes): LabTestCatalog {
            $labTestCatalog->update(
                Arr::except($attributes, ['specimen_type_ids', 'result_options', 'result_parameters']),
            );

            $this->syncLabTestCatalogConfiguration->handle($labTestCatalog, $attributes);

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
