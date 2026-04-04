import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
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
import { type SupplierIndexPageProps } from '@/types/supplier';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Suppliers', href: '/suppliers' },
];

export default function SuppliersIndex({
    suppliers,
    filters,
}: SupplierIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows = suppliers.data ?? [];
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/suppliers',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['suppliers', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Suppliers" />

            <div className="m-4 flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="w-full md:max-w-sm">
                        <Input
                            placeholder="Search by name, contact person, or email..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    {hasPermission('suppliers.create') ? (
                        <Button asChild>
                            <Link href="/suppliers/create">+ Add Supplier</Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Contact Person</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((supplier) => (
                                    <TableRow key={supplier.id}>
                                        <TableCell className="font-medium">
                                            {supplier.name}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {supplier.contact_person ?? '-'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {supplier.email ?? '-'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {supplier.phone ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    supplier.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {supplier.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                {hasPermission(
                                                    'suppliers.update',
                                                ) ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/suppliers/${supplier.id}/edit`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                                {hasPermission(
                                                    'suppliers.delete',
                                                ) ? (
                                                    <DeleteConfirmationModal
                                                        title="Delete Supplier"
                                                        description={`Are you sure you want to delete "${supplier.name}"?`}
                                                        action={{
                                                            action: `/suppliers/${supplier.id}`,
                                                            method: 'delete',
                                                        }}
                                                        onSuccess={() =>
                                                            toast.success(
                                                                `Supplier "${supplier.name}" deleted successfully.`,
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
                                        colSpan={6}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        No suppliers found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {suppliers.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                suppliers.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {suppliers.links.map((link, idx) => {
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
                                                suppliers.next_page_url ??
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
