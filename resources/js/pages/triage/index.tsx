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
import { type PatientVisit, type TriageQueuePageProps } from '@/types/patient';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Triage', href: '/triage' },
    { title: 'Queue', href: '/triage' },
];

function triageBadgeClass(grade: string | undefined): string {
    return (
        {
            red: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            yellow: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            green: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            black: 'bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900',
        }[grade ?? ''] ?? 'bg-zinc-100 text-zinc-800'
    );
}

function triageState(visit: PatientVisit): {
    label: string;
    className: string;
    actionLabel: string;
} {
    if (visit.triage) {
        return {
            label: 'Recorded',
            className:
                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
            actionLabel: 'Open Triage',
        };
    }

    return {
        label: 'Pending',
        className:
            'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
        actionLabel: 'Start Triage',
    };
}

export default function TriageIndex({ visits, filters }: TriageQueuePageProps) {
    const { hasPermission } = usePermissions();
    const rows: PatientVisit[] = Array.isArray(visits)
        ? visits
        : (visits.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const canViewTriage = hasPermission('triage.view');
    const canCreateTriage = hasPermission('triage.create');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/triage',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['visits', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Triage Queue" />

            <div className="m-4 space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold">Triage Queue</h1>
                        <p className="text-sm text-muted-foreground">
                            Active visits ready for triage capture and vital
                            signs.
                        </p>
                    </div>

                    <Input
                        placeholder="Search by visit number, patient, or phone..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="w-full sm:max-w-sm"
                    />
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table className="min-w-[1080px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Patient</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Triage Grade</TableHead>
                                <TableHead>Chief Complaint</TableHead>
                                <TableHead>Clinic / Nurse</TableHead>
                                <TableHead>Last Activity</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((visit) => {
                                    const patientName = [
                                        visit.patient?.first_name,
                                        visit.patient?.middle_name,
                                        visit.patient?.last_name,
                                    ]
                                        .filter(Boolean)
                                        .join(' ');
                                    const state = triageState(visit);
                                    const activityDate =
                                        visit.triage?.triage_datetime ??
                                        visit.registered_at;

                                    return (
                                        <TableRow key={visit.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {patientName ||
                                                            'Unknown patient'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {visit.patient
                                                            ?.phone_number
                                                            ? `${visit.patient.phone_number}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${state.className}`}
                                                >
                                                    {state.label}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                {visit.triage ? (
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageBadgeClass(visit.triage.triage_grade)}`}
                                                    >
                                                        {
                                                            visit.triage
                                                                .triage_grade
                                                        }
                                                    </span>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        Not set
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="max-w-[260px] whitespace-normal">
                                                {visit.triage
                                                    ?.chief_complaint ||
                                                    'Capture complaint in triage'}
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {visit.clinic?.name ||
                                                            'Not assigned'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {visit.triage?.nurse
                                                            ? `${visit.triage.nurse.first_name} ${visit.triage.nurse.last_name}`
                                                            : 'Open queue'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {activityDate ? (
                                                    <div>
                                                        <p className="font-medium">
                                                            {new Date(
                                                                activityDate,
                                                            ).toLocaleDateString(
                                                                'en-US',
                                                                {
                                                                    year: 'numeric',
                                                                    month: 'short',
                                                                    day: 'numeric',
                                                                },
                                                            )}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {new Date(
                                                                activityDate,
                                                            ).toLocaleTimeString(
                                                                'en-US',
                                                                {
                                                                    hour: '2-digit',
                                                                    minute: '2-digit',
                                                                },
                                                            )}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        Not recorded
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {canViewTriage ? (
                                                    <Button size="sm" asChild>
                                                        <Link
                                                            href={`/triage/${visit.id}`}
                                                        >
                                                            {visit.triage ||
                                                            canCreateTriage
                                                                ? state.actionLabel
                                                                : 'Review Triage'}
                                                        </Link>
                                                    </Button>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        No action available
                                                    </span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-12 text-center text-zinc-500 italic"
                                    >
                                        No active visits are waiting in the
                                        triage queue.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {!Array.isArray(visits) && visits.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                visits.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {visits.links.map((link, idx) => {
                                        const label = link.label
                                            .replace(/<[^>]*>/g, '')
                                            .trim();
                                        if (label === '...') {
                                            return (
                                                <PaginationItem
                                                    key={`ellipsis-${idx}`}
                                                >
                                                    <PaginationEllipsis />
                                                </PaginationItem>
                                            );
                                        }
                                        if (/^\d+$/.test(label)) {
                                            return (
                                                <PaginationItem key={label}>
                                                    <PaginationLink
                                                        href={
                                                            link.url ??
                                                            undefined
                                                        }
                                                        isActive={link.active}
                                                    >
                                                        {label}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        }
                                        return null;
                                    })}
                                    <PaginationItem>
                                        <PaginationNext
                                            href={
                                                visits.next_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                </PaginationContent>
                            </Pagination>
                        </div>
                    ) : null}
                </div>

                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    <ClipboardList className="mr-2 inline h-4 w-4" />
                    Existing triage records stay visible in the queue so nurses
                    can reopen the workspace and add fresh vital captures from
                    one place.
                </div>
            </div>
        </AppLayout>
    );
}
