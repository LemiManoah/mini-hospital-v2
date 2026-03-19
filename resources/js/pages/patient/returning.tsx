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
import VisitStartDialog from '@/components/visit-start-dialog';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type Patient, type ReturningPatientsPageProps } from '@/types/patient';
import { Head, Link, router } from '@inertiajs/react';
import { PlusCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Returning Patients', href: '/patients/returning' },
];

function formatDate(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function ReturningPatients({
    patients,
    filters,
    visitTypes,
    clinics,
    doctors,
    companies,
    packages,
}: ReturningPatientsPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Patient[] = Array.isArray(patients)
        ? patients
        : (patients.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const canOpenPatient = hasPermission('patients.view');
    const canStartVisit = hasPermission('visits.create');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/patients/returning',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: [
                        'patients',
                        'filters',
                        'visitTypes',
                        'clinics',
                        'doctors',
                        'companies',
                        'packages',
                    ],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Returning Patients" />

            <div className="m-4 space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="w-full sm:max-w-md">
                        <h1 className="text-2xl font-semibold">
                            Returning Patients
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Patients with at least one completed visit.
                        </p>
                    </div>
                    <Input
                        placeholder="Search by patient number, name, or phone..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="w-full sm:max-w-sm"
                    />
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table className="min-w-[980px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Patient</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Country</TableHead>
                                <TableHead>Completed Visits</TableHead>
                                <TableHead>Last Completed Visit</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((patient) => {
                                    const fullName = [
                                        patient.first_name,
                                        patient.middle_name,
                                        patient.last_name,
                                    ]
                                        .filter(Boolean)
                                        .join(' ');

                                    return (
                                        <TableRow key={patient.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {fullName}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {patient.patient_number}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {patient.phone_number}
                                            </TableCell>
                                            <TableCell>
                                                {patient.country
                                                    ?.country_name || 'N/A'}
                                            </TableCell>
                                            <TableCell>
                                                {patient.completed_visits_count ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    patient.last_completed_visit_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-2">
                                                    {canOpenPatient ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/patients/${patient.id}`}
                                                            >
                                                                Open Patient
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {canStartVisit ? (
                                                        <VisitStartDialog
                                                            patientId={patient.id}
                                                            patientName={fullName}
                                                            visitTypes={visitTypes}
                                                            clinics={clinics}
                                                            doctors={doctors}
                                                            companies={companies}
                                                            packages={packages}
                                                            redirectTo="visit"
                                                            title="Start Return Visit"
                                                            description="Create a fresh visit for this returning patient without leaving the list."
                                                            trigger={
                                                                <Button size="sm">
                                                                    <PlusCircle className="mr-2 h-4 w-4" />
                                                                    New Visit
                                                                </Button>
                                                            }
                                                        />
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
                                        No returning patients found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {!Array.isArray(patients) && patients.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                patients.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {patients.links.map((link, idx) => {
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
                                                patients.next_page_url ??
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
