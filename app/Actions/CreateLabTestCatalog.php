<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabTestCatalog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateLabTestCatalog
{
    public function __construct(
        private SyncLabTestCatalogConfiguration $syncLabTestCatalogConfiguration,
    ) {}

    /**
     * @param  array{
     *      tenant_id?: string|null,
     *      test_code: string,
     *      test_name: string,
     *      lab_test_category_id: string,
     *      result_type_id: string,
     *      description?: string|null,
     *      base_price?: float|int|string,
     *      is_active?: bool,
     *      specimen_type_ids?: list<string>,
     *      result_options?: list<array<string, mixed>>,
     *      result_parameters?: list<array<string, mixed>>
     *  }  $attributes
     */
    public function handle(array $attributes): LabTestCatalog
    {
        return DB::transaction(function () use ($attributes): LabTestCatalog {
            /** @var array<string, mixed> $createAttributes */
            $createAttributes = Arr::except($attributes, ['specimen_type_ids', 'result_options', 'result_parameters']);

            $labTestCatalog = LabTestCatalog::query()->create(
                $createAttributes,
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
