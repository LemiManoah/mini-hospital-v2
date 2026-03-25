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
    type LabTestCatalog,
    type LabTestCatalogIndexPageProps,
} from '@/types/lab-test-catalog';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Lab Tests', href: '/lab-test-catalogs' },
];

const formatCaptureType = (value: string) => value.replaceAll('_', ' ');

export default function LabTestCatalogIndex({
    labTests,
    filters,
}: LabTestCatalogIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: LabTestCatalog[] = Array.isArray(labTests)
        ? labTests
        : (labTests.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) return;

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/lab-test-catalogs',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['labTests', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lab Tests" />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Lab Tests
                    </h2>
                    <Input
                        placeholder="Search lab tests..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('lab_test_catalogs.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link href="/lab-test-catalogs/create">
                            + Add Lab Test
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[1100px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Test</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Specimen</TableHead>
                            <TableHead>Capture Type</TableHead>
                            <TableHead>Base Price</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((labTest) => (
                                <TableRow key={labTest.id}>
                                    <TableCell className="font-semibold">
                                        <div>{labTest.test_name}</div>
                                    </TableCell>
                                    <TableCell className="font-mono">
                                        {labTest.test_code}
                                    </TableCell>
                                    <TableCell>{labTest.category}</TableCell>
                                    <TableCell>
                                        {labTest.specimen_type ?? 'Not set'}
                                    </TableCell>
                                    <TableCell className="capitalize">
                                        {formatCaptureType(
                                            labTest.result_capture_type ??
                                                'not_set',
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {Number(labTest.base_price).toFixed(2)}
                                    </TableCell>
                                    <TableCell>
                                        {labTest.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'lab_test_catalogs.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/lab-test-catalogs/${labTest.id}/edit`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            {hasPermission(
                                                'lab_test_catalogs.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Lab Test"
                                                    description={`Are you sure you want to delete "${labTest.test_name}"? This action cannot be undone.`}
                                                    action={{
                                                        method: 'delete',
                                                        action: `/lab-test-catalogs/${labTest.id}`,
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Lab test "${labTest.test_name}" deleted successfully.`,
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
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={8}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No lab tests found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(labTests) && labTests.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            labTests.prev_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>
                                {labTests.links.map((link, idx) => {
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
                                            labTests.next_page_url ?? undefined
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
