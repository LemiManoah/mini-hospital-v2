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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
    type PaginatedFacilityManagerList,
} from './types';

interface AuditLogEntry {
    id: string;
    log_name: string | null;
    event: string | null;
    title: string;
    actor: string | null;
    subject: string | null;
    reason: string | null;
    created_at: string | null;
}

interface FacilityManagerAuditLogProps {
    tenant: FacilityManagerTenantSummary;
    filters: {
        search: string | null;
        log_name: string | null;
        event: string | null;
    };
    metrics: FacilityManagerMetric[];
    audit_logs: PaginatedFacilityManagerList<AuditLogEntry>;
    log_name_options: string[];
    event_options: string[];
}

const formatDateTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'Not set';

export default function FacilityManagerAuditLog({
    tenant,
    filters,
    metrics,
    audit_logs,
    log_name_options,
    event_options,
}: FacilityManagerAuditLogProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [logName, setLogName] = useState(filters.log_name ?? 'all');
    const [eventName, setEventName] = useState(filters.event ?? 'all');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
        {
            title: 'Audit Log',
            href: `/facility-manager/facilities/${tenant.id}/audit-log`,
        },
    ];

    const applyFilters = () => {
        router.get(
            `/facility-manager/facilities/${tenant.id}/audit-log`,
            {
                search: search || undefined,
                log_name: logName === 'all' ? undefined : logName,
                event: eventName === 'all' ? undefined : eventName,
            },
            { preserveState: true, replace: true },
        );
    };

    const resetFilters = () => {
        setSearch('');
        setLogName('all');
        setEventName('all');
        router.get(
            `/facility-manager/facilities/${tenant.id}/audit-log`,
            {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Audit Log`} />

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
                    title="Facility audit log"
                    description="Browse recorded audit activity across support, billing, pharmacy, inventory, and other logged workflows for this facility."
                />

                <FacilityManagerNav tenantId={tenant.id} current="audit-log" />

                <FacilityManagerMetrics metrics={metrics} />

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                        <CardDescription>
                            Narrow the audit log by domain, event name, or free
                            text.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-[minmax(0,1.2fr)_220px_260px_auto]">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search title, event, subject, or reason"
                        />
                        <Select value={logName} onValueChange={setLogName}>
                            <SelectTrigger>
                                <SelectValue placeholder="All logs" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All logs</SelectItem>
                                {log_name_options.map((option) => (
                                    <SelectItem key={option} value={option}>
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={eventName} onValueChange={setEventName}>
                            <SelectTrigger>
                                <SelectValue placeholder="All events" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All events</SelectItem>
                                {event_options.map((option) => (
                                    <SelectItem key={option} value={option}>
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <div className="flex gap-2">
                            <Button onClick={applyFilters}>Apply</Button>
                            <Button variant="outline" onClick={resetFilters}>
                                Reset
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Audit Entries</CardTitle>
                        <CardDescription>
                            Latest recorded events for this facility.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Log</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Actor</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Reason</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {audit_logs.data.length > 0 ? (
                                    audit_logs.data.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell>
                                                {formatDateTime(
                                                    entry.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell className="capitalize">
                                                {entry.log_name ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <p className="text-sm font-medium">
                                                        {entry.title}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {entry.event ?? '-'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {entry.actor ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {entry.subject ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {entry.reason ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-12 text-center text-sm text-muted-foreground"
                                        >
                                            No audit entries match the current
                                            filters.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {audit_logs.links.length > 3 && (
                    <div className="flex justify-center">
                        <Pagination>
                            <PaginationContent>
                                {audit_logs.prev_page_url && (
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                audit_logs.prev_page_url ?? '#'
                                            }
                                        />
                                    </PaginationItem>
                                )}
                                {audit_logs.links
                                    .filter(
                                        (link) =>
                                            ![
                                                '&laquo; Previous',
                                                'Next &raquo;',
                                            ].includes(link.label),
                                    )
                                    .map((link, index) =>
                                        link.label === '...' ? (
                                            <PaginationItem key={index}>
                                                <PaginationEllipsis />
                                            </PaginationItem>
                                        ) : (
                                            <PaginationItem key={index}>
                                                <PaginationLink
                                                    href={link.url ?? '#'}
                                                    isActive={link.active}
                                                >
                                                    {link.label}
                                                </PaginationLink>
                                            </PaginationItem>
                                        ),
                                    )}
                                {audit_logs.next_page_url && (
                                    <PaginationItem>
                                        <PaginationNext
                                            href={
                                                audit_logs.next_page_url ?? '#'
                                            }
                                        />
                                    </PaginationItem>
                                )}
                            </PaginationContent>
                        </Pagination>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
