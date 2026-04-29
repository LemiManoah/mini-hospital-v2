<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ConsultationType;
use App\Enums\FacilityServiceCategory;
use App\Enums\VisitType;
use App\Models\ConsultationTariff;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class ConsultationTariffSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()
            ->with('branches')
            ->orderBy('name')
            ->get()
            ->each(function (Tenant $tenant): void {
                $creator = User::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('email')
                    ->first();

                $services = $this->seedConsultationServices($tenant, $creator?->id);

                $tenant->branches
                    ->sortByDesc('is_main_branch')
                    ->sortBy('name')
                    ->each(function (FacilityBranch $branch) use ($tenant, $services, $creator): void {
                        $this->seedBranchTariffs($tenant, $branch, $services, $creator?->id);
                    });
            });
    }

    /**
     * @return Collection<string, FacilityService>
     */
    private function seedConsultationServices(Tenant $tenant, ?string $creatorId): Collection
    {
        $prefix = mb_strtoupper($tenant->domain ?? 'TENANT');

        return collect([
            'new' => ['code' => sprintf('%s-CONS-NEW', $prefix), 'name' => 'New Consultation', 'amount' => 50000],
            'follow_up' => ['code' => sprintf('%s-CONS-FUP', $prefix), 'name' => 'Follow-up Consultation', 'amount' => 35000],
            'review' => ['code' => sprintf('%s-CONS-REV', $prefix), 'name' => 'Review Consultation', 'amount' => 30000],
            'opd' => ['code' => sprintf('%s-CONS-OPD', $prefix), 'name' => 'General OPD Consultation', 'amount' => 40000],
            'emergency' => ['code' => sprintf('%s-CONS-EMR', $prefix), 'name' => 'Emergency Consultation', 'amount' => 80000],
            'telemedicine' => ['code' => sprintf('%s-CONS-TEL', $prefix), 'name' => 'Telemedicine Consultation', 'amount' => 45000],
            'general' => ['code' => sprintf('%s-CONS-GEN', $prefix), 'name' => 'General Consultation', 'amount' => 40000],
        ])->map(fn (array $definition): FacilityService => FacilityService::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'service_code' => $definition['code'],
            ],
            [
                'name' => $definition['name'],
                'category' => FacilityServiceCategory::OTHER->value,
                'description' => sprintf('%s tariff for %s.', $definition['name'], $tenant->name),
                'cost_price' => 0,
                'selling_price' => $definition['amount'],
                'is_billable' => true,
                'is_active' => true,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
            ],
        ));
    }

    /**
     * @param  Collection<string, FacilityService>  $services
     */
    private function seedBranchTariffs(Tenant $tenant, FacilityBranch $branch, Collection $services, ?string $creatorId): void
    {
        $definitions = [
            ['visit_type' => VisitType::NEW, 'consultation_type' => ConsultationType::NEW, 'service' => 'new'],
            ['visit_type' => VisitType::FOLLOW_UP, 'consultation_type' => ConsultationType::FOLLOW_UP, 'service' => 'follow_up'],
            ['visit_type' => VisitType::OUTPATIENT, 'consultation_type' => ConsultationType::OPD, 'service' => 'opd'],
            ['visit_type' => VisitType::OPD_CONSULTATION, 'consultation_type' => ConsultationType::OPD, 'service' => 'opd'],
            ['visit_type' => VisitType::EMERGENCY, 'consultation_type' => ConsultationType::EMERGENCY, 'service' => 'emergency'],
            ['visit_type' => VisitType::TELEMEDICINE, 'consultation_type' => ConsultationType::TELEMEDICINE, 'service' => 'telemedicine'],
            ['visit_type' => null, 'consultation_type' => ConsultationType::REVIEW, 'service' => 'review'],
            ['visit_type' => null, 'consultation_type' => ConsultationType::GENERAL, 'service' => 'general'],
            ['visit_type' => null, 'consultation_type' => ConsultationType::OPD, 'service' => 'opd'],
        ];

        foreach ($definitions as $definition) {
            $service = $services->get($definition['service']);

            if (! $service instanceof FacilityService) {
                continue;
            }

            ConsultationTariff::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'facility_branch_id' => $branch->id,
                    'visit_type' => $definition['visit_type']?->value,
                    'consultation_type' => $definition['consultation_type']->value,
                ],
                [
                    'facility_service_id' => $service->id,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ],
            );
        }
    }
}
