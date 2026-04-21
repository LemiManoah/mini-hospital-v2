import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Building2, LogIn, Search, ShieldUser, UserRound } from 'lucide-react';
import { useEffect, useState } from 'react';

import { FacilityManagerPagination } from '../components/facility-manager-pagination';
import { type PaginatedFacilityManagerList } from '../types';

interface FacilityOption {
    id: string;
    name: string;
}

interface RoleOption {
    name: string;
}

interface ImpersonationUser {
    id: string;
    name: string;
    email: string;
    position?: string | null;
    employee_number?: string | null;
    is_active: boolean;
    last_login_at: string | null;
    tenant?: {
        id: string;
        name: string;
    } | null;
    roles: string[];
    branches: Array<{
        id: string;
        name: string;
    }>;
}

interface FacilityImpersonationIndexProps {
    filters: {
        search: string | null;
        facility_id: string | null;
        role: string | null;
    };
    facility_options: FacilityOption[];
    role_options: RoleOption[];
    users: PaginatedFacilityManagerList<ImpersonationUser>;
}

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : 'No login recorded';

export default function FacilityImpersonationIndex({
    filters,
    facility_options,
    role_options,
    users,
}: FacilityImpersonationIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [facilityId, setFacilityId] = useState(filters.facility_id ?? 'all');
    const [role, setRole] = useState(filters.role ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            facilityId === (filters.facility_id ?? 'all') &&
            role === (filters.role ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/facility-manager/impersonation',
                {
                    search: search || undefined,
                    facility_id: facilityId === 'all' ? undefined : facilityId,
                    role: role === 'all' ? undefined : role,
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
    }, [
        facilityId,
        filters.facility_id,
        filters.role,
        filters.search,
        role,
        search,
    ]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        {
            title: 'Impersonation',
            href: '/facility-manager/impersonation',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Support Impersonation" />

            <div className="flex flex-col gap-6 p-6">
                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader className="space-y-2">
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <ShieldUser className="h-5 w-5" />
                            Support Impersonation
                        </CardTitle>
                        <CardDescription>
                            Find a facility user, filter by facility or role,
                            then open their workspace as that user.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_220px]">
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search user, email, employee number, or facility..."
                                    className="pl-9"
                                />
                            </div>

                            <Select
                                value={facilityId}
                                onValueChange={setFacilityId}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Facility" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All facilities
                                    </SelectItem>
                                    {facility_options.map((facility) => (
                                        <SelectItem
                                            key={facility.id}
                                            value={facility.id}
                                        >
                                            {facility.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={role} onValueChange={setRole}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All roles
                                    </SelectItem>
                                    {role_options.map((roleOption) => (
                                        <SelectItem
                                            key={roleOption.name}
                                            value={roleOption.name}
                                        >
                                            {roleOption.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {users.data.length > 0 ? (
                    <div className="grid gap-4 xl:grid-cols-2">
                        {users.data.map((user) => (
                            <Card
                                key={user.id}
                                className="border-none shadow-sm ring-1 ring-border/50"
                            >
                                <CardContent className="space-y-4 p-5">
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="space-y-2">
                                            <div className="space-y-1">
                                                <p className="text-base font-semibold">
                                                    {user.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {user.email}
                                                </p>
                                            </div>

                                            <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                                                <span className="inline-flex items-center gap-1">
                                                    <Building2 className="h-4 w-4" />
                                                    {user.tenant?.name ??
                                                        'No facility'}
                                                </span>
                                                <span className="inline-flex items-center gap-1">
                                                    <UserRound className="h-4 w-4" />
                                                    {user.position ??
                                                        'No position'}
                                                </span>
                                            </div>
                                        </div>

                                        <Button
                                            type="button"
                                            onClick={() =>
                                                router.post(
                                                    `/facility-manager/impersonation/users/${user.id}`,
                                                )
                                            }
                                        >
                                            <LogIn className="h-4 w-4" />
                                            Impersonate
                                        </Button>
                                    </div>

                                    <div className="grid gap-3 text-sm md:grid-cols-2">
                                        <div className="rounded-lg bg-muted/35 p-3">
                                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                Roles
                                            </p>
                                            <p className="mt-2 text-foreground">
                                                {user.roles.length > 0
                                                    ? user.roles.join(', ')
                                                    : 'No roles assigned'}
                                            </p>
                                        </div>
                                        <div className="rounded-lg bg-muted/35 p-3">
                                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                Branches
                                            </p>
                                            <p className="mt-2 text-foreground">
                                                {user.branches.length > 0
                                                    ? user.branches
                                                          .map(
                                                              (branch) =>
                                                                  branch.name,
                                                          )
                                                          .join(', ')
                                                    : 'No branches assigned'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid gap-2 text-sm text-muted-foreground sm:grid-cols-2">
                                        <p>
                                            Status:{' '}
                                            {user.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </p>
                                        <p>
                                            Employee No:{' '}
                                            {user.employee_number ?? 'Not set'}
                                        </p>
                                        <p className="sm:col-span-2">
                                            Last login:{' '}
                                            {formatDate(user.last_login_at)}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardContent className="py-12 text-center text-sm text-muted-foreground">
                            No facility users matched the current filters.
                        </CardContent>
                    </Card>
                )}

                <FacilityManagerPagination
                    links={users.links}
                    prevPageUrl={users.prev_page_url}
                    nextPageUrl={users.next_page_url}
                />
            </div>
        </AppLayout>
    );
}
