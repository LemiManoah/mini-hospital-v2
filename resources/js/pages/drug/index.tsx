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
import { type BreadcrumbItem } from '@/types';
import { type Drug, type DrugIndexPageProps } from '@/types/drug';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Drugs', href: '/drugs' }];

export default function DrugIndex({ drugs, filters }: DrugIndexPageProps) {
    const rows: Drug[] = Array.isArray(drugs) ? drugs : (drugs.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) return;

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/drugs',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['drugs', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Drugs" />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Drugs
                    </h2>
                    <Input
                        placeholder="Search drugs..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button
                    asChild
                    className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                >
                    <Link href="/drugs/create">+ Add Drug</Link>
                </Button>
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[1000px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Generic Name</TableHead>
                            <TableHead>Brand</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Form</TableHead>
                            <TableHead>Strength</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((drug) => (
                                <TableRow key={drug.id}>
                                    <TableCell className="font-semibold">
                                        {drug.generic_name}
                                    </TableCell>
                                    <TableCell>
                                        {drug.brand_name || (
                                            <span className="italic opacity-50">
                                                No brand
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-mono">
                                        {drug.drug_code}
                                    </TableCell>
                                    <TableCell>
                                        {drug.category.replaceAll('_', ' ')}
                                    </TableCell>
                                    <TableCell>
                                        {drug.dosage_form.replaceAll('_', ' ')}
                                    </TableCell>
                                    <TableCell>{drug.strength}</TableCell>
                                    <TableCell>
                                        {drug.is_active ? 'Active' : 'Inactive'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/drugs/${drug.id}/edit`}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <DeleteConfirmationModal
                                                title="Delete Drug"
                                                description={`Are you sure you want to delete "${drug.generic_name}"? This action cannot be undone.`}
                                                action={{
                                                    method: 'delete',
                                                    action: `/drugs/${drug.id}`,
                                                }}
                                                onSuccess={() =>
                                                    toast.success(
                                                        `Drug "${drug.generic_name}" deleted successfully.`,
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
                                    No drugs found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(drugs) && drugs.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={drugs.prev_page_url ?? undefined}
                                    />
                                </PaginationItem>
                                {drugs.links.map((link, idx) => {
                                    const label = link.label
                                        .replace(/<[^>]*>/g, '')
                                        .trim();
                                    if (label === '...')
                                        return (
                                            <PaginationItem
                                                key={`ellipsis-${idx}`}
                                            >
                                                <PaginationEllipsis />
                                            </PaginationItem>
                                        );
                                    if (/^\d+$/.test(label))
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
                                    return null;
                                })}
                                <PaginationItem>
                                    <PaginationNext
                                        href={drugs.next_page_url ?? undefined}
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
