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
import VisitCompletionModal from '@/components/visit-completion-modal';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type ActiveVisitsPageProps, type PatientVisit } from '@/types/patient';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Outpatient', href: '/visits' },
    { title: 'Active Visits', href: '/visits' },
];

function formatDate(date: string | null): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTime(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClasses(status: string): string {
    return (
        {
            registered:
                'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
            in_progress:
                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
            awaiting_payment:
                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        }[status] ?? 'bg-zinc-100 text-zinc-800'
    );
}

export default function ActiveVisits({
    visits,
    filters,
}: ActiveVisitsPageProps) {
    const { hasPermission } = usePermissions();
    const rows: PatientVisit[] = Array.isArray(visits)
        ? visits
        : (visits.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const canOpenVisit = hasPermission('visits.view');
    const canUpdateVisit = hasPermission('visits.update');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/visits',
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
            <Head title="Active Visits" />

            <div className="m-4 space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="w-full sm:max-w-md">
                        <h1 className="text-2xl font-semibold">
                            Active Visits
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Current registered and in-progress visits.
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
                    <Table className="min-w-[1120px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Patient</TableHead>
                                <TableHead>Clinic</TableHead>
                                <TableHead>Payer</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Registered</TableHead>
                                <TableHead>Actions</TableHead>
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
                                    const company =
                                        visit.payer?.insuranceCompany?.name ??
                                        visit.payer?.insurance_company?.name;
                                    const packageName =
                                        visit.payer?.insurancePackage?.name ??
                                        visit.payer?.insurance_package?.name;
                                    const completionCheck =
                                        visit.completion_check;

                                    return (
                                        <TableRow key={visit.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {patientName ||
                                                            'Unknown patient'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {
                                                            visit.patient
                                                                ?.patient_number
                                                        }{' '}
                                                        {visit.patient
                                                            ?.phone_number
                                                            ? ` ${visit.patient.phone_number}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {visit.clinic?.name ||
                                                            'Not assigned'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {visit.doctor
                                                            ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                                            : 'Not assigned'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="capitalize">
                                                        {visit.payer
                                                            ?.billing_type ??
                                                            'cash'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {company
                                                            ? `${company}${packageName ? ` / ${packageName}` : ''}`
                                                            : 'No insurer'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-2">
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ${statusClasses(visit.status)}`}
                                                    >
                                                        {visit.status.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                    {completionCheck?.has_pending_services ? (
                                                        <p className="text-xs font-medium text-red-600">
                                                            Pending services:{' '}
                                                            {
                                                                completionCheck.pending_services_count
                                                            }
                                                        </p>
                                                    ) : completionCheck?.has_unpaid_balance ? (
                                                        <p className="text-xs font-medium text-amber-600">
                                                            Unpaid balance
                                                            warning
                                                        </p>
                                                    ) : (
                                                        <p className="text-xs font-medium text-emerald-600">
                                                            Ready to complete
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {formatDate(
                                                            visit.registered_at ??
                                                                visit.created_at,
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatTime(
                                                            visit.registered_at ??
                                                                visit.created_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-2">
                                                    {canOpenVisit ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/visits/${visit.id}`}
                                                            >
                                                                Open Visit
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {canUpdateVisit ? (
                                                        visit.status ===
                                                            'in_progress' ||
                                                        visit.status ===
                                                            'awaiting_payment' ? (
                                                            <VisitCompletionModal
                                                                visitId={
                                                                    visit.id
                                                                }
                                                                visitNumber={
                                                                    visit.visit_number
                                                                }
                                                                completionCheck={
                                                                    completionCheck
                                                                }
                                                                redirectTo="index"
                                                                trigger={
                                                                    <Button size="sm">
                                                                        Complete
                                                                        Visit
                                                                    </Button>
                                                                }
                                                            />
                                                        ) : null
                                                    ) : null}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-12 text-center text-zinc-500 italic"
                                    >
                                        No active visits found.
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
            </div>
        </AppLayout>
    );
}
