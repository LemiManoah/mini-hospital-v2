import RoleController from '@/actions/App/Http/Controllers/RoleController';
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
import { formatIdentifierLabel } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { type Role, type RoleIndexPageProps } from '@/types/role';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: RoleController.index.url() },
];

export default function RoleIndex({ roles, filters }: RoleIndexPageProps) {
    const rows: Role[] = Array.isArray(roles) ? roles : (roles.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                RoleController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['roles', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles & Permissions" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Roles & Permissions
                    </h2>
                    <Input
                        placeholder="Search roles..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button
                    asChild
                    className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                >
                    <Link href={RoleController.create.url()} className="gap-2">
                        <span>+ Add Role</span>
                    </Link>
                </Button>
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[250px] text-xs font-semibold tracking-wider uppercase">
                                Role Name
                            </TableHead>
                            <TableHead className="text-center text-xs font-semibold tracking-wider uppercase">
                                Permissions
                            </TableHead>
                            <TableHead className="text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((role) => (
                                <TableRow
                                    key={role.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {formatIdentifierLabel(role.name)}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <span className="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 px-2.5 py-0.5 text-[10px] font-bold tracking-tight text-zinc-800 uppercase dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                            {role.permissions.length}{' '}
                                            permissions
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                                className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                            >
                                                <Link
                                                    href={RoleController.edit.url(
                                                        { role },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>

                                            {role.name !== 'super_admin' && (
                                                <DeleteConfirmationModal
                                                    title="Delete Role"
                                                    description={`Are you sure you want to delete the role "${formatIdentifierLabel(role.name)}"? This action cannot be undone.`}
                                                    action={RoleController.destroy.form(
                                                        { role },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Role "${formatIdentifierLabel(role.name)}" deleted successfully.`,
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
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={3}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No roles found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(roles) && roles.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={roles.prev_page_url ?? undefined}
                                    />
                                </PaginationItem>

                                {roles.links.map((link, idx) => {
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
                                        href={roles.next_page_url ?? undefined}
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
