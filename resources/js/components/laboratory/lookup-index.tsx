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
import {
    type LabLookupRecord,
    type PaginatedLabLookupList,
} from '@/types/lab-reference';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

type LookupIndexProps = {
    title: string;
    createLabel: string;
    createHref: string;
    baseHref: string;
    editBaseHref: string;
    deleteResourceName: string;
    breadcrumbs: BreadcrumbItem[];
    records: PaginatedLabLookupList<LabLookupRecord> | LabLookupRecord[];
    filters: {
        search: string | null;
    };
    createPermission: string;
    updatePermission: string;
    deletePermission: string;
    codeColumnLabel?: string;
};

export function LookupIndex({
    title,
    createLabel,
    createHref,
    baseHref,
    editBaseHref,
    deleteResourceName,
    breadcrumbs,
    records,
    filters,
    createPermission,
    updatePermission,
    deletePermission,
    codeColumnLabel,
}: LookupIndexProps) {
    const { hasPermission } = usePermissions();
    const rows: LabLookupRecord[] = Array.isArray(records)
        ? records
        : (records.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) return;

        const timeoutId = window.setTimeout(() => {
            router.get(
                baseHref,
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [baseHref, search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {title}
                    </h2>
                    <Input
                        placeholder={`Search ${title.toLowerCase()}...`}
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission(createPermission) ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link href={createHref}>{createLabel}</Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            {codeColumnLabel ? (
                                <TableHead>{codeColumnLabel}</TableHead>
                            ) : null}
                            <TableHead>Description</TableHead>
                            <TableHead>Scope</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((record) => {
                                const isDefault = record.tenant_id === null;

                                return (
                                    <TableRow key={record.id}>
                                        <TableCell className="font-semibold">
                                            {record.name}
                                        </TableCell>
                                        {codeColumnLabel ? (
                                            <TableCell className="font-mono">
                                                {record.code ?? 'N/A'}
                                            </TableCell>
                                        ) : null}
                                        <TableCell className="max-w-[360px] whitespace-normal">
                                            {record.description || (
                                                <span className="italic opacity-50">
                                                    No description provided
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {isDefault
                                                    ? 'Default'
                                                    : 'Custom'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {record.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                {hasPermission(
                                                    updatePermission,
                                                ) && !isDefault ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`${editBaseHref}/${record.id}/edit`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                                {hasPermission(
                                                    deletePermission,
                                                ) && !isDefault ? (
                                                    <DeleteConfirmationModal
                                                        title={`Delete ${deleteResourceName}`}
                                                        description={`Are you sure you want to delete "${record.name}"? This action cannot be undone.`}
                                                        action={{
                                                            method: 'delete',
                                                            action: `${editBaseHref}/${record.id}`,
                                                        }}
                                                        onSuccess={() =>
                                                            toast.success(
                                                                `${deleteResourceName} "${record.name}" deleted successfully.`,
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
                                    colSpan={codeColumnLabel ? 6 : 5}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No records found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(records) && records.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            records.prev_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>
                                {records.links.map((link, idx) => {
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
                                            records.next_page_url ?? undefined
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
