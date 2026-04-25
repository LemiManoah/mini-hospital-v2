import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
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
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface FacilityRow {
    id: string;
    name: string;
    domain: string;
    status: string | null;
    onboarding_completed_at: string | null;
    current_subscription?: {
        status: string;
        status_label: string;
        package?: {
            name: string;
        } | null;
    } | null;
    counts: {
        branches: number;
        departments: number;
        users: number;
        patients: number;
        visits: number;
        lab_requests: number;
        prescriptions: number;
    };
}

interface FacilityManagerIndexProps {
    filters: {
        search: string | null;
        onboarding: string | null;
        subscription: string | null;
    };
    tenants: {
        data: FacilityRow[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Manager', href: '/facility-manager/dashboard' },
    { title: 'Facilities', href: '/facility-manager/facilities' },
];

const onboardingLabel = (value: string | null): string =>
    value ? 'Completed' : 'Open';

export default function FacilityManagerIndex({
    filters,
    tenants,
}: FacilityManagerIndexProps) {
    const { hasPermission } = usePermissions();
    const [search, setSearch] = useState(filters.search ?? '');
    const [onboarding, setOnboarding] = useState(filters.onboarding ?? 'all');
    const [subscription, setSubscription] = useState(
        filters.subscription ?? 'all',
    );

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            onboarding === (filters.onboarding ?? 'all') &&
            subscription === (filters.subscription ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/facility-manager/facilities',
                {
                    search: search || undefined,
                    onboarding: onboarding === 'all' ? undefined : onboarding,
                    subscription:
                        subscription === 'all' ? undefined : subscription,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['tenants', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [
        search,
        onboarding,
        subscription,
        filters.search,
        filters.onboarding,
        filters.subscription,
    ]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facility Manager Facilities" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Facilities
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Search, filter, and inspect tenant workspaces from
                            one detailed support panel.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href="/facility-manager/dashboard">
                                Back to Dashboard
                            </Link>
                        </Button>
                        {hasPermission('tenants.update') ? (
                            <Button asChild>
                                <Link href="/facility-manager/facilities/create">
                                    Create Facility
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_220px]">
                    <Input
                        placeholder="Search facility name or domain..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <Select value={onboarding} onValueChange={setOnboarding}>
                        <SelectTrigger>
                            <SelectValue placeholder="Onboarding" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All onboarding</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="open">Open</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={subscription}
                        onValueChange={setSubscription}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Subscription" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                All subscriptions
                            </SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="trial">Trial</SelectItem>
                            <SelectItem value="past_due">Past Due</SelectItem>
                            <SelectItem value="no_subscription">
                                No Subscription
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-xl border border-border bg-background p-4">
                    <Table className="min-w-[1080px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Facility</TableHead>
                                <TableHead>Onboarding</TableHead>
                                <TableHead>Subscription</TableHead>
                                <TableHead>Branches</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead>Patients</TableHead>
                                <TableHead>Visits</TableHead>
                                <TableHead>Lab</TableHead>
                                <TableHead>Rx</TableHead>
                                <TableHead className="text-right">
                                    Action
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tenants.data.length > 0 ? (
                                tenants.data.map((tenant) => (
                                    <TableRow key={tenant.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {tenant.name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {tenant.domain}
                                                    .mini-hospital.com
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {onboardingLabel(
                                                    tenant.onboarding_completed_at,
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <Badge variant="outline">
                                                    {tenant.current_subscription
                                                        ?.status_label ??
                                                        'No subscription'}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {tenant.current_subscription
                                                        ?.package?.name ??
                                                        'No package'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {tenant.counts.branches}
                                        </TableCell>
                                        <TableCell>
                                            {tenant.counts.users}
                                        </TableCell>
                                        <TableCell>
                                            {tenant.counts.patients}
                                        </TableCell>
                                        <TableCell>
                                            {tenant.counts.visits}
                                        </TableCell>
                                        <TableCell>
                                            {tenant.counts.lab_requests}
                                        </TableCell>
                                        <TableCell>
                                            {tenant.counts.prescriptions}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={`/facility-manager/facilities/${tenant.id}`}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={10}
                                        className="py-12 text-center text-sm text-muted-foreground"
                                    >
                                        No facilities match the current filters.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination>
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href={tenants.prev_page_url ?? undefined}
                            />
                        </PaginationItem>
                        {tenants.links.map((link, idx) => {
                            const label = link.label
                                .replace('&laquo;', '')
                                .replace('&raquo;', '')
                                .trim();

                            if (label === '') {
                                return (
                                    <PaginationItem
                                        key={`${idx}-${link.label}`}
                                    >
                                        <PaginationEllipsis />
                                    </PaginationItem>
                                );
                            }

                            return (
                                <PaginationItem key={`${idx}-${label}`}>
                                    <PaginationLink
                                        href={link.url ?? '#'}
                                        isActive={link.active}
                                    >
                                        {label}
                                    </PaginationLink>
                                </PaginationItem>
                            );
                        })}
                        <PaginationItem>
                            <PaginationNext
                                href={tenants.next_page_url ?? undefined}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            </div>
        </AppLayout>
    );
}
