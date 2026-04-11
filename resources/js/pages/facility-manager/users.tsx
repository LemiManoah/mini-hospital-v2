import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useState } from 'react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerPagination } from './components/facility-manager-pagination';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
    type PaginatedFacilityManagerList,
} from './types';

interface UserRow {
    id: string;
    name: string;
    email: string;
    position?: string | null;
    roles: string[];
    email_verified_at: string | null;
    created_at: string | null;
    is_active: boolean;
    employee_number: string | null;
    last_login_at: string | null;
    branches: Array<{
        id: string;
        name: string;
        is_primary_location: boolean;
    }>;
}

interface FacilityManagerUsersProps {
    tenant: FacilityManagerTenantSummary;
    filters: {
        search: string | null;
        status: string | null;
    };
    metrics: FacilityManagerMetric[];
    users: PaginatedFacilityManagerList<UserRow>;
}

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : 'Not set';

export default function FacilityManagerUsers({
    tenant,
    filters,
    metrics,
    users,
}: FacilityManagerUsersProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            status === (filters.status ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                `/facility-manager/facilities/${tenant.id}/users`,
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['users', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, filters.status, search, status, tenant.id]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
        {
            title: 'Users',
            href: `/facility-manager/facilities/${tenant.id}/users`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Users`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link
                            href={`/facility-manager/facilities/${tenant.id}`}
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Overview
                        </Link>
                    </Button>
                </div>

                <FacilityManagerTenantHeader
                    tenant={tenant}
                    title="Users"
                    description="Monitor account verification, staff activity, and branch assignment coverage."
                />

                <FacilityManagerNav tenantId={tenant.id} current="users" />

                <FacilityManagerMetrics metrics={metrics} />

                <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px]">
                    <Input
                        placeholder="Search email, staff name, or employee number..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger>
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All users</SelectItem>
                            <SelectItem value="active">Active staff</SelectItem>
                            <SelectItem value="inactive">
                                Inactive staff
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-xl border border-border bg-background p-4">
                    <Table className="min-w-[1100px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Position</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Branches</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Verified</TableHead>
                                <TableHead>Last Login</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length > 0 ? (
                                users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {user.name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {user.email}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {user.employee_number ??
                                                        'No employee number'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {user.position ?? 'Not set'}
                                        </TableCell>
                                        <TableCell>
                                            {user.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1 text-sm">
                                                {user.branches.length > 0 ? (
                                                    user.branches.map(
                                                        (branch) => (
                                                            <span
                                                                key={branch.id}
                                                            >
                                                                {branch.name}
                                                                {branch.is_primary_location
                                                                    ? ' (Primary)'
                                                                    : ''}
                                                            </span>
                                                        ),
                                                    )
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        No branch assignment
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.length > 0
                                                    ? user.roles.map((role) => (
                                                          <span
                                                              key={role}
                                                              className="rounded-full bg-muted px-2 py-1 text-xs"
                                                          >
                                                              {role}
                                                          </span>
                                                      ))
                                                    : 'No roles'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {user.email_verified_at
                                                ? formatDate(
                                                      user.email_verified_at,
                                                  )
                                                : 'Not verified'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(user.last_login_at)}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-12 text-center text-sm text-muted-foreground"
                                    >
                                        No users match the current filters.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <FacilityManagerPagination
                    links={users.links}
                    prevPageUrl={users.prev_page_url}
                    nextPageUrl={users.next_page_url}
                />
            </div>
        </AppLayout>
    );
}
