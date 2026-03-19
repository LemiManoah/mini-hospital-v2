import ClinicController from '@/actions/App/Http/Controllers/ClinicController';
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
import { formatIdentifierLabel } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { type Clinic, type ClinicIndexPageProps } from '@/types/clinic';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clinics', href: ClinicController.index.url() },
];

export default function ClinicIndex({
    clinics,
    filters,
}: ClinicIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Clinic[] = Array.isArray(clinics)
        ? clinics
        : (clinics.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                ClinicController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['clinics', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clinics" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Clinics
                    </h2>
                    <Input
                        placeholder="Search clinics..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('clinics.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link
                            href={ClinicController.create.url()}
                            className="gap-2"
                        >
                            <span>+ Add Clinic</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[120px] text-xs font-semibold tracking-wider uppercase">
                                Code
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Clinic Name
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Department
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Branch
                            </TableHead>
                            <TableHead className="w-[100px] text-xs font-semibold tracking-wider uppercase">
                                Status
                            </TableHead>
                            <TableHead className="w-[100px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((clinic) => (
                                <TableRow
                                    key={clinic.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                        {clinic.clinic_code}
                                    </TableCell>
                                    <TableCell className="font-medium text-zinc-900 dark:text-zinc-100">
                                        {formatIdentifierLabel(
                                            clinic.clinic_name,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {clinic.department?.department_name || (
                                            <span className="italic opacity-50">
                                                N/A
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {clinic.branch?.name || (
                                            <span className="italic opacity-50">
                                                N/A
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${
                                                clinic.status === 'active'
                                                    ? 'border-green-200 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'border-zinc-200 bg-zinc-100 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                                            }`}
                                        >
                                            {clinic.status}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission('clinics.update') ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={ClinicController.edit.url(
                                                            {
                                                                clinic: clinic.id,
                                                            },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission('clinics.delete') ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Clinic"
                                                    description={`Are you sure you want to delete the clinic "${formatIdentifierLabel(clinic.clinic_name)}"? This action cannot be undone.`}
                                                    action={ClinicController.destroy.form(
                                                        {
                                                            clinic: clinic.id,
                                                        },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Clinic "${formatIdentifierLabel(clinic.clinic_name)}" deleted successfully.`,
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            className="h-8 cursor-pointer px-3 text-xs shadow-sm"
                                                        >
                                                            Delete
                                                        </Button>
                                                    }
                                                />
                                            ) : null}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={7}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No clinics found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(clinics) && clinics.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            clinics.prev_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>

                                {clinics.links.map((link, idx) => {
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
                                            clinics.next_page_url ?? undefined
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
