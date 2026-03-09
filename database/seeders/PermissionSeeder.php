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
            'appointments.create',
            'appointments.update',
            'appointments.delete',
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
            'appointments.create',
            'appointments.update',
            'appointments.delete',
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
            'appointments.create',
            'appointments.update',
            'appointments.delete',
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
