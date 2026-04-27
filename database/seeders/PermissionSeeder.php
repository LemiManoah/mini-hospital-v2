<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionCatalog = $this->permissionCatalog();
        $allPermissions = $this->expandPermissions($permissionCatalog);

        foreach ($allPermissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach (array_keys($this->roleDefinitions()) as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        foreach ($this->resolvedRolePermissions($allPermissions) as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->firstOrFail();
            $role->syncPermissions($permissions);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function permissionCatalog(): array
    {
        return [
            'dashboard' => ['view'],
            'settings' => ['update'],
            'countries' => ['view', 'create', 'update', 'delete'],
            'addresses' => ['view', 'create', 'update', 'delete'],
            'currencies' => ['view', 'create', 'update', 'delete'],
            'currency_exchange_rates' => ['view', 'create', 'update', 'delete'],
            'general_settings' => ['view', 'update'],
            'subscription_packages' => ['view', 'create', 'update', 'delete'],
            'allergens' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
            'patients' => ['view', 'create', 'update', 'delete'],
            'patient_allergies' => ['view', 'create', 'update', 'delete'],
            'visits' => ['view', 'create', 'update', 'delete'],
            'appointments' => ['view', 'create', 'update', 'delete', 'confirm', 'check_in', 'cancel', 'no_show', 'reschedule'],
            'doctor_schedules' => ['view', 'create', 'update', 'delete'],
            'doctor_schedule_exceptions' => ['view', 'create', 'update', 'delete'],
            'appointment_categories' => ['view', 'create', 'update', 'delete'],
            'appointment_modes' => ['view', 'create', 'update', 'delete'],
            'triage' => ['view', 'create', 'update'],
            'consultations' => ['view', 'create', 'update'],
            'tenants' => ['view', 'create', 'update', 'delete', 'onboard', 'manage_subscription', 'impersonate'],
            'facility_branches' => ['view', 'create', 'update', 'delete'],
            'staff_positions' => ['view', 'create', 'update', 'delete'],
            'staff' => ['view', 'create', 'update', 'delete'],
            'departments' => ['view', 'create', 'update', 'delete'],
            'referral_facilities' => ['view', 'create', 'update', 'delete'],
            'clinics' => ['view', 'create', 'update', 'delete'],
            'units' => ['view', 'create', 'update', 'delete'],
            'inventory_items' => ['view', 'create', 'update', 'delete'],
            'inventory_locations' => ['view', 'create', 'update', 'delete'],
            'suppliers' => ['view', 'create', 'update', 'delete'],
            'purchase_orders' => ['view', 'create', 'update', 'delete'],
            'goods_receipts' => ['view', 'create', 'update'],
            'inventory_requisitions' => ['view', 'create', 'submit', 'review', 'issue', 'cancel'],
            'stock_adjustments' => ['view', 'create', 'update'],
            'pharmacy_queue' => ['view'],
            'pharmacy_prescriptions' => ['view'],
            'pharmacy_dispensing' => ['view', 'create', 'post'],
            'pharmacy_pos' => ['view', 'create', 'complete', 'void', 'refund', 'view_history'],
            'lab_test_catalogs' => ['view', 'create', 'update', 'delete'],
            'lab_test_categories' => ['view', 'create', 'update', 'delete'],
            'specimen_types' => ['view', 'create', 'update', 'delete'],
            'result_types' => ['view', 'create', 'update', 'delete'],
            'lab_requests' => ['view', 'update'],
            'facility_services' => ['view', 'create', 'update', 'delete'],
            'insurance_companies' => ['view', 'create', 'update', 'delete'],
            'insurance_packages' => ['view', 'create', 'update', 'delete'],
            'insurance_claims' => ['view', 'create', 'update', 'delete'],
            'insurance_payments' => ['view', 'create', 'update', 'delete'],
            'switch_facility' => ['view', 'create', 'update', 'delete'],
            'visit_billings' => ['view', 'create', 'update', 'delete'],
            'visit_charges' => ['view', 'create', 'update', 'delete'],
            'payments' => ['view', 'create', 'update', 'delete'],
            'reports' => ['view'],
        ];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function roleDefinitions(): array
    {
        return [
            'super_admin' => [],
            'admin' => [],
            'doctor' => [
                'patients' => ['view', 'create', 'update', 'delete'],
                'patient_allergies' => ['view', 'create', 'update'],
                'visits' => ['view', 'create', 'update', 'delete'],
                'appointments' => ['view', 'update'],
                'consultations' => ['view', 'create', 'update'],
            ],
            'nurse' => [
                'patients' => ['view', 'create', 'update', 'delete'],
                'patient_allergies' => ['view', 'create', 'update'],
                'visits' => ['view', 'create', 'update', 'delete'],
                'appointments' => ['view', 'update'],
                'triage' => ['view', 'create', 'update'],
            ],
            'lab_technician' => [
                'patients' => ['view'],
                'visits' => ['view', 'update'],
                'appointments' => ['view'],
                'inventory_items' => ['view'],
                'inventory_locations' => ['view'],
                'goods_receipts' => ['view', 'create', 'update'],
                'inventory_requisitions' => ['view', 'create', 'submit', 'cancel'],
                'stock_adjustments' => ['view', 'create', 'update'],
                'lab_test_catalogs' => ['view', 'create', 'update', 'delete'],
                'lab_test_categories' => ['view', 'create', 'update', 'delete'],
                'specimen_types' => ['view', 'create', 'update', 'delete'],
                'result_types' => ['view', 'create', 'update', 'delete'],
                'lab_requests' => ['view', 'update'],
            ],
            'pharmacist' => [
                'patients' => ['view', 'create', 'update', 'delete'],
                'patient_allergies' => ['view'],
                'visits' => ['view', 'create', 'update', 'delete'],
                'appointments' => ['view'],
                'inventory_items' => ['view', 'create', 'update', 'delete'],
                'inventory_locations' => ['view', 'create', 'update', 'delete'],
                'suppliers' => ['view'],
                'purchase_orders' => ['view'],
                'goods_receipts' => ['view', 'create', 'update'],
                'inventory_requisitions' => ['view', 'create', 'submit', 'cancel'],
                'stock_adjustments' => ['view', 'create', 'update'],
                'pharmacy_queue' => ['view'],
                'pharmacy_prescriptions' => ['view'],
                'pharmacy_dispensing' => ['view', 'create', 'post'],
                'pharmacy_pos' => ['view', 'create', 'complete', 'void', 'refund', 'view_history'],
                'units' => ['view', 'create', 'update', 'delete'],
                'facility_services' => ['view', 'create', 'update', 'delete'],
                'reports' => ['view'],
            ],
            'receptionist' => [
                'patients' => ['view', 'create', 'update'],
                'patient_allergies' => ['view', 'create', 'update'],
                'visits' => ['view', 'create', 'update'],
                'appointments' => ['view', 'create', 'update', 'confirm', 'check_in', 'cancel', 'no_show', 'reschedule'],
                'doctor_schedules' => ['view'],
                'doctor_schedule_exceptions' => ['view', 'create', 'update', 'delete'],
                'appointment_categories' => ['view'],
                'appointment_modes' => ['view'],
            ],
            'accountant' => [
                'patients' => ['view'],
                'visits' => ['view'],
                'appointments' => ['view'],
                'reports' => ['view'],
            ],
            'cashier' => [
                'patients' => ['view', 'create', 'update'],
                'visits' => ['view', 'create', 'update'],
                'appointments' => ['view'],
                'reports' => ['view'],
            ],
            'human_resource' => [
                'users' => ['view', 'create', 'update'],
                'roles' => ['view'],
                'staff' => ['view', 'create', 'update', 'delete'],
                'staff_positions' => ['view', 'create', 'update', 'delete'],
                'departments' => ['view'],
                'referral_facilities' => ['view'],
                'clinics' => ['view'],
            ],
            'store_keeper' => [
                'inventory_items' => ['view', 'create', 'update', 'delete'],
                'inventory_locations' => ['view', 'create', 'update', 'delete'],
                'suppliers' => ['view', 'create', 'update', 'delete'],
                'purchase_orders' => ['view', 'create', 'update'],
                'goods_receipts' => ['view', 'create', 'update'],
                'inventory_requisitions' => ['view', 'review', 'issue'],
                'stock_adjustments' => ['view', 'create', 'update'],
                'units' => ['view', 'create', 'update', 'delete'],
                'facility_services' => ['view'],
            ],
        ];
    }

    /**
     * @param  array<string, list<string>>  $catalog
     * @return list<string>
     */
    private function expandPermissions(array $catalog): array
    {
        /** @var list<string> $permissions */
        $permissions = collect($catalog)
            ->flatMap(
                static fn (array $abilities, string $resource) => collect($abilities)
                    ->map(static fn (string $ability): string => sprintf('%s.%s', $resource, $ability))
            )
            ->values()
            ->all();

        return $permissions;
    }

    /**
     * @param  list<string>  $allPermissions
     * @return array<string, list<string>>
     */
    private function resolvedRolePermissions(array $allPermissions): array
    {
        return collect($this->roleDefinitions())
            ->map(function (array $definition, string $role) use ($allPermissions): array {
                if (in_array($role, ['super_admin', 'admin'], true)) {
                    return $allPermissions;
                }

                return $this->expandPermissions($this->withCommonTenantAccess($definition));
            })
            ->all();
    }

    /**
     * @param  array<string, list<string>>  $definition
     * @return array<string, list<string>>
     */
    private function withCommonTenantAccess(array $definition): array
    {
        return [
            'dashboard' => ['view'],
            'facility_branches' => ['view', 'update'],
            ...$definition,
        ];
    }
}
