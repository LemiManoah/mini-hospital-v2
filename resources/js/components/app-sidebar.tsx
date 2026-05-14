'use client';

import {
    Bell,
    Bot,
    Boxes,
    Building,
    CalendarDays,
    CircleDollarSign,
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

import { NavMain, type NavMainItem } from '@/components/nav-main';
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
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

type SidebarNavItem = Omit<NavMainItem, 'items'> & {
    permission?: string;
    permissions?: string[];
    items?: SidebarNavItem[];
};

function filterNavItems(
    items: SidebarNavItem[],
    hasPermission: (permission: string) => boolean,
): NavMainItem[] {
    return items.flatMap((item): NavMainItem[] => {
        const filteredChildren = item.items
            ? filterNavItems(item.items, hasPermission)
            : [];
        const canViewItem = item.permission
            ? hasPermission(item.permission)
            : item.permissions
              ? item.permissions.some(hasPermission)
              : item.items === undefined;

        if (!canViewItem && filteredChildren.length === 0) {
            return [];
        }

        const navItem: NavMainItem = {
            title: item.title,
            url: item.url,
            icon: item.icon,
            isActive: item.isActive,
        };

        return [
            {
                ...navItem,
                items:
                    filteredChildren.length > 0 ? filteredChildren : undefined,
            },
        ];
    });
}

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { user, hasPermission, hasRole } = usePermissions();
    const { unread_notifications_count } = usePage<SharedData>().props;

    if (!user) {
        return null;
    }

    const tenantName = (user.tenant as { name?: string } | null | undefined)
        ?.name;
    const activeBranchName = (
        user.active_branch as { name?: string } | null | undefined
    )?.name;
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
            items: filterNavItems(
                [{ title: 'Overview', url: dashboard().url }],
                hasPermission,
            ),
        },
        {
            title: 'Notifications',
            url: '/notifications',
            icon: Bell,
            items: [
                {
                    title:
                        unread_notifications_count > 0
                            ? `Inbox (${unread_notifications_count})`
                            : 'Inbox',
                    url: '/notifications',
                },
            ],
        },
        {
            title: 'Outpatient',
            url: dashboard(),
            icon: Bot,
            items: filterNavItems(
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
            items: filterNavItems(
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
                        title: 'Configuration',
                        url: '/appointments/exceptions',
                        items: [
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
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Triage',
            url: '/triage',
            icon: HeartPulse,
            items: filterNavItems(
                [{ title: 'Queue', url: '/triage', permission: 'triage.view' }],
                hasPermission,
            ),
        },
        {
            title: 'Doctors',
            url: '/doctors/consultations',
            icon: UserRoundSearch,
            items: filterNavItems(
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
            items: filterNavItems(
                [
                    {
                        title: 'Dashboard',
                        url: '/laboratory/dashboard',
                        permission: 'lab_orders.view',
                    },
                    {
                        title: 'Incoming Lab Investigations Queue',
                        url: '/laboratory/incoming-investigations',
                        permission: 'lab_orders.view',
                    },
                    {
                        title: 'Enter Results',
                        url: '/laboratory/enter-results',
                        permission: 'lab_orders.view',
                    },
                    {
                        title: 'Reviewing Results',
                        url: '/laboratory/review-results',
                        permission: 'lab_orders.view',
                    },
                    {
                        title: 'View Results',
                        url: '/laboratory/view-results',
                        permission: 'lab_orders.view',
                    },
                    {
                        title: 'Lab Management',
                        url: '/laboratory/management',
                        items: [
                            // {
                            //     title: 'Overview',
                            //     url: '/laboratory/management',
                            //     permissions: [
                            //         'lab_test_categories.view',
                            //         'lab_test_catalogs.view',
                            //         'specimen_types.view',
                            //         'result_types.view',
                            //     ],
                            // },
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
                                title: 'Specimen Types',
                                url: '/specimen-types',
                                permission: 'specimen_types.view',
                            },
                            {
                                title: 'Result Types',
                                url: '/result-types',
                                permission: 'result_types.view',
                            },
                        ],
                    },
                    {
                        title: 'Stock Management',
                        url: '/laboratory/stock-management',
                        items: [
                            // {
                            //     title: 'Overview',
                            //     url: '/laboratory/stock-management',
                            //     permissions: [
                            //         'inventory_items.view',
                            //         'inventory_requisitions.view',
                            //         'goods_receipts.view',
                            //     ],
                            // },
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
                        ],
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Pharmacy',
            url: '/pharmacy/queue',
            icon: PillBottle,
            items: filterNavItems(
                [
                    {
                        title: 'Pharmacy Queue',
                        url: '/pharmacy/queue',
                        permission: 'visits.view',
                    },
                    {
                        title: 'Dispense History',
                        url: '/pharmacy/dispenses',
                        permission: 'visits.view',
                    },
                    {
                        title: 'Pharmacy POS',
                        url: '/pharmacy/pos',
                        permission: 'pharmacy_pos.create',
                    },
                    {
                        title: 'POS History',
                        url: '/pharmacy/pos/history',
                        permission: 'pharmacy_pos.view_history',
                    },
                    {
                        title: 'Stock',
                        url: '/pharmacy/stock',
                        items: [
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
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Inventory',
            url: '/inventory/dashboard',
            icon: Boxes,
            items: filterNavItems(
                [
                    {
                        title: 'Dashboard',
                        url: '/inventory/dashboard',
                        permission: 'inventory_items.view',
                    },
                    {
                        title: 'Stock',
                        url: '/inventory-items',
                        items: [
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
                    },
                    {
                        title: 'Procurement',
                        url: '/purchase-orders',
                        items: [
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
                        ],
                    },
                    {
                        title: 'Requisitions',
                        url: '/inventory-requisitions',
                        items: canAccessIncomingRequisitions
                            ? [
                                  {
                                      title: 'Incoming Requisitions',
                                      url: '/inventory-requisitions',
                                  },
                              ]
                            : [],
                    },
                    {
                        title: 'Reconciliations',
                        url: '/reconciliations',
                        permission: 'stock_adjustments.view',
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'Finance & Accounting',
            url: '/finance/opd-payments',
            icon: CircleDollarSign,
            items: filterNavItems(
                [
                    {
                        title: 'Incoming OPD Payments',
                        url: '/finance/opd-payments',
                        permission: 'payments.view',
                    },
                    {
                        title: 'Insurance',
                        url: '/finance/insurance-invoices',
                        items: [
                            {
                                title: 'Insurance Invoices',
                                url: '/finance/insurance-invoices',
                                permission: 'insurance_claims.view',
                            },
                        ],
                    },
                    {
                        title: 'Accounts',
                        url: '/finance/deposits',
                        items: [
                            {
                                title: 'Deposits',
                                url: '/finance/deposits',
                                permission: 'billing_deposits.view',
                            },
                            {
                                title: 'Debtors',
                                url: '/finance/debtors',
                                permission: 'visit_billings.view',
                            },
                        ],
                    },
                    {
                        title: 'Reports',
                        url: '/finance/billing-summary',
                        items: [
                            {
                                title: 'Billing Summary',
                                url: '/finance/billing-summary',
                                permission: 'reports.view',
                            },
                            {
                                title: 'Daily Revenue',
                                url: '/reports/daily-revenue',
                                permission: 'reports.view',
                            },
                        ],
                    },
                ],
                hasPermission,
            ),
        },
        {
            title: 'User Management',
            url: indexUsers(),
            icon: UserCog,
            items: filterNavItems(
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
            items: filterNavItems(
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
            items: filterNavItems(
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
                        title: 'Charge Master',
                        url: '/charge-masters',
                        permission: 'charge_masters.view',
                    },
                    {
                        title: 'Reports',
                        url: '/reports',
                        permissions: [
                            'reports.view',
                            'patient_reports.view',
                            'visit_reports.view',
                            'pharmacy_reports.view',
                            'laboratory_reports.view',
                            'inventory_reports.view',
                        ],
                    },
                    {
                        title: 'Insurance Setup',
                        url: '/administration/insurance-setup',
                        items: [
                            {
                                title: 'Companies',
                                url: '/insurance-companies',
                                permission: 'insurance_companies.view',
                            },
                            {
                                title: 'Packages',
                                url: '/insurance-packages',
                                permission: 'insurance_packages.view',
                            },
                        ],
                    },
                    {
                        title: 'Master Data',
                        url: '/administration/master-data',
                        items: [
                            {
                                title: 'Branches',
                                url: '/facility-branches',
                                permission: 'facility_branches.view',
                            },
                            {
                                title: 'Clinics',
                                url: '/clinics',
                                permission: 'clinics.view',
                            },
                            {
                                title: 'Departments',
                                url: indexDepartments().url,
                                permission: 'departments.view',
                            },
                            {
                                title: 'Facility Services',
                                url: '/facility-services',
                                permission: 'facility_services.view',
                            },
                            {
                                title: 'Addresses',
                                url: '/addresses',
                                permission: 'addresses.view',
                            },
                            {
                                title: 'Allergens',
                                url: '/allergens',
                                permission: 'allergens.view',
                            },
                            {
                                title: 'Currencies',
                                url: '/currencies',
                                permission: 'currencies.view',
                            },
                            {
                                title: 'Units',
                                url: '/units',
                                permission: 'units.view',
                            },
                        ],
                    },
                    {
                        title: 'Data Upload',
                        url: '/data-upload',
                        permission: 'patients.create',
                    },
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
                      items: filterNavItems(
                          [
                              {
                                  title: 'Dashboard',
                                  url: '/facility-manager/dashboard',
                                  permission: 'tenants.view',
                              },
                              {
                                  title: 'Impersonation',
                                  url: '/facility-manager/impersonation',
                                  permission: 'tenants.impersonate',
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
