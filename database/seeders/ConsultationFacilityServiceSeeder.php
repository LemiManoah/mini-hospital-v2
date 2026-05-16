<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\SyncFacilityServiceChargeMaster;
use App\Enums\ConsultationType;
use App\Enums\FacilityServiceCategory;
use App\Models\FacilityService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

final class ConsultationFacilityServiceSeeder extends Seeder
{
    public function __construct(
        private readonly SyncFacilityServiceChargeMaster $syncFacilityServiceChargeMaster,
    ) {}

    public function run(): void
    {
        Tenant::query()
            ->orderBy('name')
            ->get()
            ->each(function (Tenant $tenant): void {
                $creator = User::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('email')
                    ->first();

                $this->seedTenantConsultationServices($tenant, $creator?->id);
            });
    }

    private function seedTenantConsultationServices(Tenant $tenant, ?string $creatorId): void
    {
        $prefix = mb_strtoupper($tenant->domain ?? 'TENANT');
        $definitions = [
            ['type' => ConsultationType::NEW, 'code' => 'NEW', 'amount' => 50000],
            ['type' => ConsultationType::FOLLOW_UP, 'code' => 'FUP', 'amount' => 35000],
            ['type' => ConsultationType::OPD, 'code' => 'OPD', 'amount' => 40000],
            ['type' => ConsultationType::EMERGENCY, 'code' => 'EMR', 'amount' => 80000],
            ['type' => ConsultationType::TELEMEDICINE, 'code' => 'TEL', 'amount' => 45000],
            ['type' => ConsultationType::REVIEW, 'code' => 'REV', 'amount' => 30000],
            ['type' => ConsultationType::GENERAL, 'code' => 'GEN', 'amount' => 40000],
        ];

        foreach ($definitions as $definition) {
            $service = FacilityService::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'service_code' => sprintf('%s-CONS-%s', $prefix, $definition['code']),
                ],
                [
                    'name' => $definition['type']->label(),
                    'category' => FacilityServiceCategory::CONSULTATION,
                    'description' => $definition['type']->label(),
                    'cost_price' => null,
                    'is_billable' => true,
                    'is_consultation' => true,
                    'consultation_type' => $definition['type'],
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ],
            );

            $this->syncFacilityServiceChargeMaster->handle($service, $definition['amount']);
        }
    }
}
