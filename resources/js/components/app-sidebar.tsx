'use client';

import {
    Bot,
    Boxes,
    CalendarDays,
    FlaskConical,
    HeartPulse,
    LayoutGrid,
    PillBottle,
    Settings2,
    UserCog,
    UserRoundSearch,
    Users,
} from 'lucide-react';
import * as React from 'react';

import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index as indexAddresses } from '@/routes/addresses';
import { index as indexAllergens } from '@/routes/allergens';
import { index as indexClinics } from '@/routes/clinics';
import { index as indexCurrencies } from '@/routes/currencies';
import { index as indexDepartments } from '@/routes/departments';
import { index as indexFacilitySwitcher } from '@/routes/facility-switcher';
import { index as indexInsuranceCompanies } from '@/routes/insurance-companies';
import { index as indexInsurancePackages } from '@/routes/insurance-packages';
import {
    create as createPatients,
    index as indexPatients,
    returning as returningPatients,
} from '@/routes/patients';
import { index as indexRoles } from '@/routes/roles';
import { index as indexStaff } from '@/routes/staff';
import { index as indexStaffPositions } from '@/routes/staff-positions';
import { index as indexSubscriptionPackages } from '@/routes/subscription-packages';
import { index as indexUnits } from '@/routes/units';
import { create as createUsers, index as indexUsers } from '@/routes/users';

type NavChild = {
    title: string;
    url: string;
    permission?: string;
    permissions?: string[];
};

function filterItems(
    items: NavChild[],
    hasPermission: (permission: string) => boolean,
): Array<{ title: string; url: string }> {
    return items
        .filter((item) => {
            if (item.permission) {
                return hasPermission(item.permission);
            }

            if (item.permissions) {
                return item.permissions.some(hasPermission);
            }

            return true;
        })
        .map(({ title, url }) => ({ title, url }));
}

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { user, hasPermission, hasRole } = usePermissions();

    if (!user) {
        return null;
    }

    const tenantName = (user.tenant as { name?: string } | null | undefined)
        ?.name;
    const activeBranchName = (
        user.active_branch as { name?: string } | null | undefined
    )?.name;
    const canSwitchFacility =
        Boolean(user.is_support) || hasRole('super_admin');

    const navMain: React.ComponentProps<typeof NavMain>['items'] = [
        {
            title: 'Dashboard',
            url: dashboard(),
            icon: LayoutGrid,
            items: filterItems(
                [{ title: 'Overview', url: dashboard().url }],
                hasPermission,
            ),
        },
        {
            title: 'Outpatient',
            url: dashboard(),
            icon: Bot,
            items: filterItems(
                [
                    {
                        title: 'Register Patient',
                        url: createPatients().url,
                        permission: 'patients.create',
                    },
                    {
                        title: 'All Patients',
                        url: indexPatients().url,
                        permission: 'patients.view',
                    },
                    {
                        title: 'Active Visits',
                        url: '/visits',
                        permission: 'visits.view',
                    },
                    {
                        title: 'Returning Patients',
                        url: returningPatients().url,
                        permission: 'patients.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Appointments',
            url: '/appointments',
            icon: CalendarDays,
            items: filterItems(
                [
                    {
                        title: 'Bookings',
                        url: '/appointments',
                        permission: 'appointments.view',
                    },
                    {
                        title: 'Queue',
                        url: '/appointments/queue',
                        permission: 'appointments.view',
                    },
                    {
                        title: 'My Appointments',
                        url: '/appointments/my',
                        permission: 'appointments.view',
                    },
                    {
                        title: 'Schedules',
                        url: '/appointments/schedules',
                        permission: 'doctor_schedules.view',
                    },
                    {
                        title: 'Schedule Exceptions',
                        url: '/appointments/exceptions',
                        permission: 'doctor_schedule_exceptions.view',
                    },
                    {
                        title: 'Categories',
                        url: '/appointment-categories',
                        permission: 'appointment_categories.view',
                    },
                    {
                        title: 'Modes',
                        url: '/appointment-modes',
                        permission: 'appointment_modes.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Triage',
            url: '/triage',
            icon: HeartPulse,
            items: filterItems(
                [{ title: 'Queue', url: '/triage', permission: 'triage.view' }],
                hasPermission,
            ),
        },
        {
            title: 'Doctors',
            url: '/doctors/consultations',
            icon: UserRoundSearch,
            items: filterItems(
                [
                    {
                        title: 'Consultation',
                        url: '/doctors/consultations',
                        permission: 'consultations.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Laboratory',
            url: '/laboratory/dashboard',
            icon: FlaskConical,
            items: filterItems(
                [
                    {
                        title: 'Dashboard',
                        url: '/laboratory/dashboard',
                        permission: 'lab_requests.view',
                    },
                    {
                        title: 'Lab Stock',
                        url: '/laboratory/stock',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Lab Requisitions',
                        url: '/laboratory/requisitions',
                        permission: 'inventory_requisitions.view',
                    },
                    {
                        title: 'Lab Movements',
                        url: '/laboratory/movements',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Lab Receipts',
                        url: '/laboratory/receipts',
                        permission: 'goods_receipts.view',
                    },
                    {
                        title: 'Incoming Lab Investigations Queue',
                        url: '/laboratory/incoming-investigations',
                        permission: 'lab_requests.view',
                    },
                    {
                        title: 'Enter Results',
                        url: '/laboratory/enter-results',
                        permission: 'lab_requests.view',
                    },
                    {
                        title: 'Reviewing Results',
                        url: '/laboratory/review-results',
                        permission: 'lab_requests.view',
                    },
                    {
                        title: 'View Results',
                        url: '/laboratory/view-results',
                        permission: 'lab_requests.view',
                    },
                    {
                        title: 'Service Categories',
                        url: '/lab-test-categories',
                        permission: 'lab_test_categories.view',
                    },
                    {
                        title: 'Laboratory Services',
                        url: '/lab-test-catalogs',
                        permission: 'lab_test_catalogs.view',
                    },
                    {
                        title: 'Lab Management',
                        url: '/laboratory/management',
                        permissions: [
                            'specimen_types.view',
                            'result_types.view',
                        ],
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Pharmacy',
            url: '/pharmacy/stock',
            icon: PillBottle,
            items: filterItems(
                [
                    {
                        title: 'Pharmacy Stock',
                        url: '/pharmacy/stock',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Pharmacy Requisitions',
                        url: '/pharmacy/requisitions',
                        permission: 'inventory_requisitions.view',
                    },
                    {
                        title: 'Pharmacy Movements',
                        url: '/pharmacy/movements',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Pharmacy Receipts',
                        url: '/pharmacy/receipts',
                        permission: 'goods_receipts.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Inventory',
            url: '/inventory/dashboard',
            icon: Boxes,
            items: filterItems(
                [
                    {
                        title: 'Dashboard',
                        url: '/inventory/dashboard',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Items',
                        url: '/inventory-items',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Locations',
                        url: '/inventory-locations',
                        permission: 'inventory_locations.view',
                    },
                    {
                        title: 'Suppliers',
                        url: '/suppliers',
                        permission: 'suppliers.view',
                    },
                    {
                        title: 'Purchase Orders',
                        url: '/purchase-orders',
                        permission: 'purchase_orders.view',
                    },
                    {
                        title: 'Goods Receipts',
                        url: '/goods-receipts',
                        permission: 'goods_receipts.view',
                    },
                    {
                        title: 'Requisitions',
                        url: '/inventory-requisitions',
                        permission: 'inventory_requisitions.view',
                    },
                    {
                        title: 'Reconciliations',
                        url: '/reconciliations',
                        permission: 'stock_adjustments.view',
                    },
                    {
                        title: 'Stock By Location',
                        url: '/inventory/stock-by-location',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Stock Movements',
                        url: '/inventory/reports/movements',
                        permission: 'inventory_items.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'User Management',
            url: indexUsers(),
            icon: UserCog,
            items: filterItems(
                [
                    {
                        title: 'View Users',
                        url: indexUsers().url,
                        permission: 'users.view',
                    },
                    {
                        title: 'Register User',
                        url: createUsers().url,
                        permission: 'users.create',
                    },
                    {
                        title: 'Roles',
                        url: indexRoles().url,
                        permission: 'roles.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Staff Management',
            url: indexStaff(),
            icon: Users,
            items: filterItems(
                [
                    {
                        title: 'View Staff',
                        url: indexStaff().url,
                        permission: 'staff.view',
                    },
                    {
                        title: 'Staff Positions',
                        url: indexStaffPositions().url,
                        permission: 'staff_positions.view',
                    },
                    {
                        title: 'Departments',
                        url: indexDepartments().url,
                        permission: 'departments.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Settings',
            url: indexAddresses(),
            icon: Settings2,
            items: filterItems(
                [
                    {
                        title: 'Addresses',
                        url: indexAddresses().url,
                        permission: 'addresses.view',
                    },
                    {
                        title: 'Allergens',
                        url: indexAllergens().url,
                        permission: 'allergens.view',
                    },
                    {
                        title: 'Currencies',
                        url: indexCurrencies().url,
                        permission: 'currencies.view',
                    },
                    {
                        title: 'Subscription Packages',
                        url: indexSubscriptionPackages().url,
                        permission: 'subscription_packages.view',
                    },
                    {
                        title: 'Units',
                        url: indexUnits().url,
                        permission: 'units.view',
                    },
                    {
                        title: 'Inventory Items',
                        url: '/inventory-items',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Inventory Locations',
                        url: '/inventory-locations',
                        permission: 'inventory_locations.view',
                    },
                    {
                        title: 'Insurance Companies',
                        url: indexInsuranceCompanies().url,
                        permission: 'insurance_companies.view',
                    },
                    {
                        title: 'Clinics',
                        url: indexClinics().url,
                        permission: 'clinics.view',
                    },
                    {
                        title: 'Facility Branches',
                        url: '/facility-branches',
                        permission: 'facility_branches.view',
                    },
                    {
                        title: 'Insurance Packages',
                        url: indexInsurancePackages().url,
                        permission: 'insurance_packages.view',
                    },
                    {
                        title: 'Facility Services',
                        url: '/facility-services',
                        permission: 'facility_services.view',
                    },
                    ...(canSwitchFacility
                        ? [
                              {
                                  title: 'Facility Switcher',
                                  url: indexFacilitySwitcher().url,
                                  permission: 'tenants.view',
                              },
                          ]
                        : []),
                ],
                hasPermission,
            ),
        },
    ].filter((group) => group.items.length > 0);

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem className="px-2 py-1">
                        <div className="flex flex-col">
                            <span className="truncate text-sm font-semibold">
                                {tenantName ?? 'Mini Hospital'}
                            </span>
                            <span className="truncate text-xs text-muted-foreground">
                                {activeBranchName ??
                                    'No active branch selected'}
                            </span>
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                <NavMain items={navMain} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
