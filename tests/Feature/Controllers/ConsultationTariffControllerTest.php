<?php

declare(strict_types=1);

use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createConsultationTariffContext(): array
{
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    $serviceId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $serviceId,
        'tenant_id' => $tenant->id,
        'service_code' => 'SVC-CONSULT-001',
        'name' => 'General Consultation',
        'category' => 'other',
        'selling_price' => 20000,
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$tenant, $branch, $user, $serviceId];
}

it('shows the consultation tariff registry for the active branch', function (): void {
    [$tenant, $branch, $user, $serviceId] = createConsultationTariffContext();
    $user->givePermissionTo('consultation_tariffs.view');

    DB::table('consultation_tariffs')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_type' => 'outpatient',
        'consultation_type' => 'opd',
        'facility_service_id' => $serviceId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('consultation-tariffs.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('consultation-tariff/index')
            ->where('consultationTariffs.data.0.consultation_type', 'opd')
            ->where('consultationTariffs.data.0.facility_service.name', 'General Consultation'));
});

it('creates a consultation tariff for the active branch', function (): void {
    [, $branch, $user, $serviceId] = createConsultationTariffContext();
    $user->givePermissionTo('consultation_tariffs.create');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('consultation-tariffs.store'), [
            'visit_type' => 'new',
            'consultation_type' => 'new',
            'facility_service_id' => $serviceId,
            'is_active' => true,
        ])
        ->assertRedirect(route('consultation-tariffs.index'))
        ->assertSessionHas('success', 'Consultation tariff created successfully.');

    $this->assertDatabaseHas('consultation_tariffs', [
        'facility_branch_id' => $branch->id,
        'visit_type' => 'new',
        'consultation_type' => 'new',
        'facility_service_id' => $serviceId,
    ]);
});

it('updates a consultation tariff mapping', function (): void {
    [$tenant, $branch, $user, $serviceId] = createConsultationTariffContext();
    $user->givePermissionTo('consultation_tariffs.update');

    $updatedServiceId = (string) Str::uuid();

    DB::table('facility_services')->insert([
        'id' => $updatedServiceId,
        'tenant_id' => $tenant->id,
        'service_code' => 'SVC-CONSULT-002',
        'name' => 'Emergency Consultation',
        'category' => 'other',
        'selling_price' => 35000,
        'is_billable' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tariffId = (string) Str::uuid();

    DB::table('consultation_tariffs')->insert([
        'id' => $tariffId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_type' => 'outpatient',
        'consultation_type' => 'opd',
        'facility_service_id' => $serviceId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->put(route('consultation-tariffs.update', $tariffId), [
            'visit_type' => 'emergency',
            'consultation_type' => 'emergency',
            'facility_service_id' => $updatedServiceId,
            'is_active' => false,
        ])
        ->assertRedirect(route('consultation-tariffs.index'))
        ->assertSessionHas('success', 'Consultation tariff updated successfully.');

    $this->assertDatabaseHas('consultation_tariffs', [
        'id' => $tariffId,
        'visit_type' => 'emergency',
        'consultation_type' => 'emergency',
        'facility_service_id' => $updatedServiceId,
        'is_active' => false,
    ]);
});
