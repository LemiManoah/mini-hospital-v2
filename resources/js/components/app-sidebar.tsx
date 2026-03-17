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
import { dashboard } from '@/routes';
import { index as indexAddresses } from '@/routes/addresses';
import { index as indexAllergens } from '@/routes/allergens';
import { index as indexClinics } from '@/routes/clinics';
import { index as indexCurrencies } from '@/routes/currencies';
import { index as indexDepartments } from '@/routes/departments';
// import { index as indexFacilityServices } from '@/routes/facility-services';
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
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    if (!user) {
        return null;
    }

    const tenantName = (user.tenant as { name?: string } | null | undefined)
        ?.name;
    const activeBranchName = (
        user.active_branch as { name?: string } | null | undefined
    )?.name;
    const userRoles = Array.isArray(user.roles) ? user.roles : [];
    const canSwitchFacility =
        Boolean(user.is_support) || userRoles.includes('super_admin');

    const navMain: React.ComponentProps<typeof NavMain>['items'] = [
        {
            title: 'Dashboard',
            url: dashboard(),
            icon: LayoutGrid,
            items: [
                {
                    title: 'Overview',
                    url: dashboard(),
                },
            ],
        },
        {
            title: 'Outpatient',
            url: dashboard(),
            icon: Bot,
            items: [
                {
                    title: 'Register Patient',
                    url: createPatients(),
                },
                {
                    title: 'All Patients',
                    url: indexPatients(),
                },
                {
                    title: 'Active Visits',
                    url: '/visits',
                },
                {
                    title: 'Returning Patients',
                    url: returningPatients(),
                },
            ],
        },
        {
            title: 'Appointments',
            url: '/appointments',
            icon: CalendarDays,
            items: [
                {
                    title: 'Bookings',
                    url: '/appointments',
                },
                {
                    title: 'Queue',
                    url: '/appointments/queue',
                },
                {
                    title: 'My Appointments',
                    url: '/appointments/my',
                },
                {
                    title: 'Schedules',
                    url: '/appointments/schedules',
                },
                {
                    title: 'Schedule Exceptions',
                    url: '/appointments/exceptions',
                },
                {
                    title: 'Categories',
                    url: '/appointment-categories',
                },
                {
                    title: 'Modes',
                    url: '/appointment-modes',
                },
            ],
        },
        {
            title: 'Triage',
            url: '/triage',
            icon: HeartPulse,
            items: [
                {
                    title: 'Queue',
                    url: '/triage',
                },
            ],
        },
        {
            title: 'Doctors',
            url: '/doctors/consultations',
            icon: UserRoundSearch,
            items: [
                {
                    title: 'Consultation',
                    url: '/doctors/consultations',
                },
            ],
        },
        {
            title: 'Laboratory',
            url: dashboard(),
            icon: FlaskConical,
            items: [
                {
                    title: 'Lab Queue',
                    url: dashboard(),
                },
                {
                    title: 'Results',
                    url: dashboard(),
                },
            ],
        },
        {
            title: 'User Management',
            url: indexUsers(),
            icon: UserCog,
            items: [
                {
                    title: 'View Users',
                    url: indexUsers(),
                },
                {
                    title: 'Register User',
                    url: createUsers(),
                },
                {
                    title: 'Roles',
                    url: indexRoles(),
                },
            ],
        },
        {
            title: 'Staff Management',
            url: indexStaff(),
            icon: Users,
            items: [
                {
                    title: 'View Staff',
                    url: indexStaff(),
                },
                {
                    title: 'Staff Positions',
                    url: indexStaffPositions(),
                },
                {
                    title: 'Departments',
                    url: indexDepartments(),
                },
            ],
        },
        {
            title: 'Settings',
            url: indexAddresses(),
            icon: Settings2,
            items: [
                {
                    title: 'Addresses',
                    url: indexAddresses(),
                },
                {
                    title: 'Allergens',
                    url: indexAllergens(),
                },
                {
                    title: 'Currencies',
                    url: indexCurrencies(),
                },
                {
                    title: 'Subscription Packages',
                    url: indexSubscriptionPackages(),
                },
                {
                    title: 'Units',
                    url: indexUnits(),
                },
                {
                    title: 'Drugs',
                    url: '/drugs',
                },
                {
                    title: 'Insurance Companies',
                    url: indexInsuranceCompanies(),
                },
                {
                    title: 'Clinics',
                    url: indexClinics(),
                },
                {
                    title: 'Insurance Packages',
                    url: indexInsurancePackages(),
                },
                {
                    title: 'Facility Services',
                    url: '/facility-services',
                },
                ...(canSwitchFacility
                    ? [
                          {
                              title: 'Facility Switcher',
                              url: indexFacilitySwitcher(),
                          },
                      ]
                    : []),
            ],
        },
    ];

    // const projects: React.ComponentProps<typeof NavProjects>['projects'] = [
    //     {
    //         name: 'Clinical Operations',
    //         url: '/doctors/consultations',
    //         icon: Stethoscope,
    //     },
    //     {
    //         name: 'HR & Departments',
    //         url: indexDepartments(),
    //         icon: Building2,
    //     },
    //     {
    //         name: 'Access Control',
    //         url: indexRoles(),
    //         icon: Shield,
    //     },
    // ];

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
                {/* <NavProjects projects={projects} /> */}
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
