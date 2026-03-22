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
import {
    type InsurancePackage,
    type InsurancePackageIndexPageProps,
} from '@/types/insurance-package';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Insurance Packages', href: '/insurance-packages' },
];

export default function InsurancePackageIndex({
    insurancePackages,
    filters,
}: InsurancePackageIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: InsurancePackage[] = Array.isArray(insurancePackages)
        ? insurancePackages
        : (insurancePackages.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/insurance-packages',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['insurancePackages', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Insurance Packages" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Insurance Packages
                    </h2>
                    <Input
                        placeholder="Search insurance packages..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('insurance_packages.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link
                            href="/insurance-packages/create"
                            className="gap-2"
                        >
                            <span>+ Add Insurance Package</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Package Name
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Insurance Company
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Status
                            </TableHead>
                            <TableHead className="w-[100px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((insurancePackage) => (
                                <TableRow
                                    key={insurancePackage.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-medium text-zinc-900 dark:text-zinc-100">
                                        {insurancePackage.name}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {insurancePackage.insurance_company
                                            ?.name ?? (
                                            <span className="italic opacity-50">
                                                N/A
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <span className="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {insurancePackage.status}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'insurance_packages.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={`/insurance-packages/${insurancePackage.id}/edit`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission(
                                                'insurance_packages.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Insurance Package"
                                                    description={`Are you sure you want to delete "${insurancePackage.name}"? This action cannot be undone.`}
                                                    action={{
                                                        action: `/insurance-packages/${insurancePackage.id}`,
                                                        method: 'delete',
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Insurance package "${insurancePackage.name}" deleted successfully.`,
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
                                    colSpan={4}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No insurance packages found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(insurancePackages) &&
                insurancePackages.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            insurancePackages.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>

                                {insurancePackages.links.map((link, idx) => {
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
                                            insurancePackages.next_page_url ??
                                            undefined
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
