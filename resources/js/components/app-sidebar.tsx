'use client';

import {
    Bot,
    Building,
    Boxes,
    CalendarDays,
    FlaskConical,
    HeartPulse,
    LayoutGrid,
    PillBottle,
    Shield,
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
import { index as indexDepartments } from '@/routes/departments';
import {
    create as createPatients,
    index as indexPatients,
    returning as returningPatients,
} from '@/routes/patients';
import { index as indexRoles } from '@/routes/roles';
import { index as indexStaff } from '@/routes/staff';
import { index as indexStaffPositions } from '@/routes/staff-positions';
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
    const canAccessFacilityManager =
        hasPermission('tenants.view') &&
        (Boolean(user.is_support) || hasRole('super_admin'));
    const canAccessIncomingRequisitions =
        hasPermission('inventory_requisitions.view') &&
        (hasPermission('inventory_requisitions.review') ||
            hasPermission('inventory_requisitions.issue') ||
            Boolean(user.is_support) ||
            hasRole('super_admin') ||
            hasRole('admin') ||
            hasRole('store_keeper'));

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
                        title: 'Lab Management',
                        url: '/laboratory/management',
                        permissions: [
                            'specimen_types.view',
                            'result_types.view',
                        ],
                    },
                    {
                        title: 'Lab Stock Management',
                        url: '/laboratory/stock-management',
                        permissions: [
                            'inventory_items.view',
                            'inventory_requisitions.view',
                            'goods_receipts.view',
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
                    ...(canAccessIncomingRequisitions
                        ? [
                              {
                                  title: 'Incoming Requisitions',
                                  url: '/inventory-requisitions',
                              },
                          ]
                        : []),
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
            title: 'Administration',
            url: '/administration/general-settings',
            icon: Shield,
            items: filterItems(
                [
                    {
                        title: 'General Settings',
                        url: '/administration/general-settings',
                        permissions: [
                            'facility_branches.view',
                            'clinics.view',
                            'departments.view',
                            'facility_services.view',
                            'insurance_companies.view',
                            'insurance_packages.view',
                            'addresses.view',
                            'allergens.view',
                            'currencies.view',
                            'units.view',
                            'subscription_packages.view',
                            'tenants.view',
                        ],
                    },
                    {
                        title: 'Insurance Setup',
                        url: '/administration/insurance-setup',
                        permissions: [
                            'insurance_companies.view',
                            'insurance_packages.view',
                        ],
                    },
                    {
                        title: 'Master Data',
                        url: '/administration/master-data',
                        permissions: [
                            'addresses.view',
                            'allergens.view',
                            'currencies.view',
                            'units.view',
                            'clinics.view',
                            'departments.view',
                            'facility_services.view',
                        ],
                    },
                    ...((canSwitchFacility ||
                    hasPermission('subscription_packages.view') ||
                    hasPermission('facility_branches.view'))
                        ? [
                              {
                                  title: 'Platform',
                                  url: '/administration/platform',
                              },
                          ]
                        : []),
                ],
                hasPermission,
            ),
        },
        ...(canAccessFacilityManager
            ? [
                  {
                      title: 'Facility Manager',
                      url: '/facility-manager/dashboard',
                      icon: Building,
                      items: filterItems(
                          [
                              {
                                  title: 'Dashboard',
                                  url: '/facility-manager/dashboard',
                                  permission: 'tenants.view',
                              },
                              {
                                  title: 'Facilities',
                                  url: '/facility-manager/facilities',
                                  permission: 'tenants.view',
                              },
                          ],
                          hasPermission,
                      ),
                  },
              ]
            : []),
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
