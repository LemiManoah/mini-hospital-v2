<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateLabTestCatalogDTO;
use App\Data\Clinical\LabTestCatalogResultOptionDTO;
use App\Data\Clinical\LabTestCatalogResultParameterDTO;
use App\Data\Clinical\UpdateLabTestCatalogDTO;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;

final readonly class SyncLabTestCatalogConfiguration
{
    public function handle(LabTestCatalog $labTestCatalog, CreateLabTestCatalogDTO|UpdateLabTestCatalogDTO $data): void
    {
        $labTestCatalog->specimenTypes()->sync($data->specimenTypeIds);

        $resultTypeCode = LabResultType::query()
            ->whereKey($labTestCatalog->result_type_id)
            ->value('code');

        if ($resultTypeCode === 'defined_option') {
            $labTestCatalog->resultParameters()->delete();
            $this->syncResultOptions($labTestCatalog, $data->resultOptions);

            return;
        }

        if ($resultTypeCode === 'parameter_panel') {
            $labTestCatalog->resultOptions()->delete();
            $this->syncResultParameters($labTestCatalog, $data->resultParameters);

            return;
        }

        $labTestCatalog->resultOptions()->delete();
        $labTestCatalog->resultParameters()->delete();
    }

    /**
     * @param  list<LabTestCatalogResultOptionDTO>  $value
     */
    private function syncResultOptions(LabTestCatalog $labTestCatalog, array $value): void
    {
        $labTestCatalog->resultOptions()->delete();
        $payload = [];

        foreach ($value as $index => $option) {
            $payload[] = $option->toRecordPayload($index + 1);
        }

        if ($payload === []) {
            return;
        }

        $labTestCatalog->resultOptions()->createMany($payload);
    }

    /**
     * @param  list<LabTestCatalogResultParameterDTO>  $value
     */
    private function syncResultParameters(LabTestCatalog $labTestCatalog, array $value): void
    {
        $labTestCatalog->resultParameters()->delete();
        $payload = [];

        foreach ($value as $index => $parameter) {
            $payload[] = $parameter->toRecordPayload($index + 1);
        }

        if ($payload === []) {
            return;
        }

        $labTestCatalog->resultParameters()->createMany($payload);
    }
}
