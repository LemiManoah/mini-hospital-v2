<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view',

            'settings.update',

            'countries.view',
            'countries.create',
            'countries.update',
            'countries.delete',

            'addresses.view',
            'addresses.create',
            'addresses.update',
            'addresses.delete',

            'currencies.view',
            'currencies.create',
            'currencies.update',
            'currencies.delete',

            'subscription_packages.view',
            'subscription_packages.create',
            'subscription_packages.update',
            'subscription_packages.delete',

            'allergens.view',
            'allergens.create',
            'allergens.update',
            'allergens.delete',

            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',

            'visits.view',
            'visits.create',
            'visits.update',
            'visits.delete',

            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',
            'appointments.confirm',
            'appointments.check_in',
            'appointments.cancel',
            'appointments.no_show',
            'appointments.reschedule',

            'doctor_schedules.view',
            'doctor_schedules.create',
            'doctor_schedules.update',
            'doctor_schedules.delete',

            'appointment_categories.view',
            'appointment_categories.create',
            'appointment_categories.update',
            'appointment_categories.delete',

            'appointment_modes.view',
            'appointment_modes.create',
            'appointment_modes.update',
            'appointment_modes.delete',

            'triage.view',
            'triage.create',
            'triage.update',

            'consultations.view',
            'consultations.create',
            'consultations.update',

            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',

            'facility_branches.view',
            'facility_branches.create',
            'facility_branches.update',
            'facility_branches.delete',

            'staff_positions.view',
            'staff_positions.create',
            'staff_positions.update',
            'staff_positions.delete',

            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',

            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',

            'clinics.view',
            'clinics.create',
            'clinics.update',
            'clinics.delete',

            'units.view',
            'units.create',
            'units.update',
            'units.delete',

            'drugs.view',
            'drugs.create',
            'drugs.update',
            'drugs.delete',

            'facility_services.view',
            'facility_services.create',
            'facility_services.update',
            'facility_services.delete',

            
        ];

        $roles = [
            'super_admin',
            'admin',
            'doctor',
            'nurse',
            'lab_technician',
            'pharmacist',
            'receptionist',
            'accountant',
            'cashier',
            'human_resource',
            'store_keeper',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach ($roles as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::query()->where('name', 'super_admin')->first();
        $superAdmin->givePermissionTo($permissions);

        $admin = Role::query()->where('name', 'admin')->first();
        $admin->givePermissionTo($permissions);

        $doctor = Role::query()->where('name', 'doctor')->first();
        $doctor->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'visits.view',
            'visits.create',
            'visits.update',
            'visits.delete',
            'appointments.view',
            'appointments.update',
            'consultations.view',
            'consultations.create',
            'consultations.update',
        ]);

        $nurse = Role::query()->where('name', 'nurse')->first();
        $nurse->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'visits.view',
            'visits.create',
            'visits.update',
            'visits.delete',
            'appointments.view',
            'appointments.update',
            'triage.view',
            'triage.create',
            'triage.update',
        ]);

        $labTechnician = Role::query()->where('name', 'lab_technician')->first();
        $labTechnician->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'visits.view',
            'visits.update',
            'appointments.view',
        ]);

        $pharmacist = Role::query()->where('name', 'pharmacist')->first();
        $pharmacist->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'visits.view',
            'visits.create',
            'visits.update',
            'visits.delete',
            'appointments.view',
            'drugs.view',
            'drugs.create',
            'drugs.update',
            'drugs.delete',
            'units.view',
            'units.create',
            'units.update',
            'units.delete',
            'facility_services.view',
            'facility_services.create',
            'facility_services.update',
            'facility_services.delete',
        ]);

        $receptionist = Role::query()->where('name', 'receptionist')->first();
        $receptionist->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'visits.view',
            'visits.create',
            'visits.update',
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.confirm',
            'appointments.check_in',
            'appointments.cancel',
            'appointments.no_show',
            'appointments.reschedule',
            'doctor_schedules.view',
            'appointment_categories.view',
            'appointment_modes.view',
        ]);

        $accountant = Role::query()->where('name', 'accountant')->first();
        $accountant->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'visits.view',
            'visits.create',
            'visits.update',
            'appointments.view',
        ]);

        $cashier = Role::query()->where('name', 'cashier')->first();
        $cashier->givePermissionTo([
            'dashboard.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'visits.view',
            'visits.create',
            'visits.update',
            'appointments.view',
        ]);

        $humanResource = Role::query()->where('name', 'human_resource')->first();
        $humanResource->givePermissionTo($permissions);

        $storeKeeper = Role::query()->where('name', 'store_keeper')->first();
        $storeKeeper->givePermissionTo($permissions);
    }
}
