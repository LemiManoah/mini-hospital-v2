<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabTestCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

final readonly class CreateLabTestCatalog
{
    public function __construct(
        private SyncLabTestCatalogConfiguration $syncLabTestCatalogConfiguration,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): LabTestCatalog
    {
        return DB::transaction(function () use ($attributes): LabTestCatalog {
            $labTestCatalog = LabTestCatalog::query()->create(
                Arr::except($attributes, ['specimen_type_ids', 'result_options', 'result_parameters']),
            );

            $this->syncLabTestCatalogConfiguration->handle($labTestCatalog, $attributes);

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
