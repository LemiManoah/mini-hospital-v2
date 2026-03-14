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
import { type BreadcrumbItem } from '@/types';
import {
    type DoctorConsultationIndexPageProps,
    type PatientVisit,
} from '@/types/patient';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardPen, FileClock, PlayCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Doctors', href: '/doctors/consultations' },
    { title: 'Consultation', href: '/doctors/consultations' },
];

function formatDateTime(date: string | null | undefined): string {
    if (!date) return 'N/A';

    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

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

function consultationState(visit: PatientVisit): {
    label: string;
    className: string;
    actionLabel: string;
} {
    if (visit.consultation) {
        return {
            label: 'In progress',
            className: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
            actionLabel: 'Continue Consultation',
        };
    }

    return {
        label: 'New',
        className: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
        actionLabel: 'Start Consultation',
    };
}

export default function DoctorConsultationsIndex({
    visits,
    filters,
}: DoctorConsultationIndexPageProps) {
    const rows: PatientVisit[] = Array.isArray(visits)
        ? visits
        : (visits.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/doctors/consultations',
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
            <Head title="Consultation Queue" />

            <div className="m-4 space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold">
                            Consultation Queue
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Triaged active visits ready for doctor review.
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
                    <Table className="min-w-[1100px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Visit</TableHead>
                                <TableHead>Patient</TableHead>
                                <TableHead>Triage</TableHead>
                                <TableHead>Chief Complaint</TableHead>
                                <TableHead>Clinic</TableHead>
                                <TableHead>Assigned Doctor</TableHead>
                                <TableHead>Consultation</TableHead>
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
                                    const consultation = consultationState(visit);

                                    return (
                                        <TableRow key={visit.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {visit.visit_number}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {visit.visit_type.replaceAll(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </p>
                                                </div>
                                            </TableCell>
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
                                                            ? `• ${visit.patient.phone_number}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageBadgeClass(visit.triage?.triage_grade)}`}
                                                >
                                                    {visit.triage?.triage_grade ??
                                                        'N/A'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="max-w-[260px] whitespace-normal">
                                                {visit.triage?.chief_complaint ||
                                                    'No complaint captured'}
                                            </TableCell>
                                            <TableCell>
                                                {visit.clinic?.name ||
                                                    'Not assigned'}
                                            </TableCell>
                                            <TableCell>
                                                {visit.doctor
                                                    ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                                    : 'Open queue'}
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${consultation.className}`}
                                                    >
                                                        {consultation.label}
                                                    </span>
                                                    <p className="text-xs text-muted-foreground">
                                                        {visit.consultation
                                                            ?.primary_diagnosis ||
                                                            'No diagnosis yet'}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(
                                                    visit.consultation
                                                        ?.started_at ??
                                                        visit.triage
                                                            ?.triage_datetime ??
                                                        visit.registered_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Button size="sm" asChild>
                                                    <Link
                                                        href={`/doctors/consultations/${visit.id}`}
                                                    >
                                                        {visit.consultation ? (
                                                            <FileClock className="mr-2 h-4 w-4" />
                                                        ) : (
                                                            <PlayCircle className="mr-2 h-4 w-4" />
                                                        )}
                                                        {consultation.actionLabel}
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={9}
                                        className="py-12 text-center text-zinc-500 italic"
                                    >
                                        No triaged visits are ready for consultation.
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
                    <ClipboardPen className="mr-2 inline h-4 w-4" />
                    Existing consultations stay in the queue so doctors can continue and refine draft notes from one place.
                </div>
            </div>
        </AppLayout>
    );
}
