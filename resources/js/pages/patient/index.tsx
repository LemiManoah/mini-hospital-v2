import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
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
import { type Patient, type PatientIndexPageProps } from '@/types/patient';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
];

export default function PatientIndex({
    patients,
    filters,
}: PatientIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Patient[] = Array.isArray(patients)
        ? patients
        : (patients.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/patients',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['patients', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Patients" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Patients
                    </h2>
                    <Input
                        placeholder="Search by patient number, name, or phone..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('patients.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link href="/patients/create" className="gap-2">
                            <span>+ Register Patient & Start Visit</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[800px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>MRN</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Gender</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
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
                                        <TableCell className="font-medium">
                                            {patient.patient_number}
                                        </TableCell>
                                        <TableCell>{fullName}</TableCell>
                                        <TableCell>
                                            {patient.phone_number}
                                        </TableCell>
                                        <TableCell className="capitalize">
                                            {patient.gender}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/patients/${patient.id}`}
                                                    >
                                                        Select
                                                    </Link>
                                                </Button>
                                                {hasPermission('patients.update') ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/patients/${patient.id}/edit`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                                {hasPermission('patients.delete') ? (
                                                    <DeleteConfirmationModal
                                                        title="Delete Patient"
                                                        description={`Are you sure you want to delete "${fullName}"? This action cannot be undone.`}
                                                        action={{
                                                            action: `/patients/${patient.id}`,
                                                            method: 'delete',
                                                        }}
                                                        onSuccess={() =>
                                                            toast.success(
                                                                `Patient "${fullName}" deleted successfully.`,
                                                            )
                                                        }
                                                        trigger={
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                            >
                                                                Delete
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
                                    colSpan={5}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No patients found.
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
                                            patients.prev_page_url ?? undefined
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
                                                    href={link.url ?? undefined}
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
                                            patients.next_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
