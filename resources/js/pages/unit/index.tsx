import UnitController from '@/actions/App/Http/Controllers/UnitController';
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
import { type Unit, type UnitIndexPageProps } from '@/types/unit';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Units', href: UnitController.index.url() },
];

const getTypeColor = (type: Unit['type']) => {
    switch (type) {
        case 'mass':
            return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800';
        case 'volume':
            return 'bg-cyan-100 text-cyan-800 border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-800';
        case 'length':
            return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800';
        case 'temperature':
            return 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-800';
        case 'time':
            return 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800';
        case 'count':
            return 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800';
        default:
            return 'bg-zinc-100 text-zinc-800 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700';
    }
};

export default function UnitIndex({ units, filters }: UnitIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Unit[] = Array.isArray(units) ? units : (units.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                UnitController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['units', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Units" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Units
                    </h2>
                    <Input
                        placeholder="Search units..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('units.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link
                            href={UnitController.create.url()}
                            className="gap-2"
                        >
                            <span>+ Add Unit</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[200px] text-xs font-semibold tracking-wider uppercase">
                                Name
                            </TableHead>
                            <TableHead className="w-[100px] text-xs font-semibold tracking-wider uppercase">
                                Symbol
                            </TableHead>
                            <TableHead className="w-[150px] text-xs font-semibold tracking-wider uppercase">
                                Type
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Description
                            </TableHead>
                            <TableHead className="w-[150px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((unit) => (
                                <TableRow
                                    key={unit.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {unit.name}
                                    </TableCell>
                                    <TableCell className="font-mono text-zinc-700 dark:text-zinc-300">
                                        {unit.symbol}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${getTypeColor(unit.type)}`}
                                        >
                                            {unit.type}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {unit.description || (
                                            <span className="italic opacity-50">
                                                No description
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission('units.update') ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={UnitController.edit.url(
                                                            { unit },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission('units.delete') ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Unit"
                                                    description={`Are you sure you want to delete "${unit.name}" (${unit.symbol})? This action cannot be undone.`}
                                                    action={UnitController.destroy.form(
                                                        { unit },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Unit "${unit.name}" deleted successfully.`,
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
                                    colSpan={5}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No units found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(units) && units.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={units.prev_page_url ?? undefined}
                                    />
                                </PaginationItem>

                                {units.links.map(
                                    (
                                        link: {
                                            url: string | null;
                                            label: string;
                                            active: boolean;
                                        },
                                        idx: number,
                                    ) => {
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
                                    },
                                )}

                                <PaginationItem>
                                    <PaginationNext
                                        href={units.next_page_url ?? undefined}
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
