'use client';

import {
    Bot,
    CalendarDays,
    FlaskConical,
    HeartPulse,
    LayoutGrid,
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
};

function filterItems(
    items: NavChild[],
    hasPermission: (permission: string) => boolean,
): Array<{ title: string; url: string }> {
    return items
        .filter((item) =>
            item.permission ? hasPermission(item.permission) : true,
        )
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
            url: dashboard(),
            icon: FlaskConical,
            items: filterItems(
                [
                    {
                        title: 'Lab Queue',
                        url: dashboard().url,
                        permission: 'dashboard.view',
                    },
                    {
                        title: 'Results',
                        url: dashboard().url,
                        permission: 'dashboard.view',
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
                        title: 'Drugs',
                        url: '/drugs',
                        permission: 'drugs.view',
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
