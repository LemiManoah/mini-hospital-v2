import AddressController from '@/actions/App/Http/Controllers/AddressController';
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
import { type Address, type AddressIndexPageProps } from '@/types/address';
import { Head, Link, router } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Addresses', href: AddressController.index.url() },
];

export default function AddressIndex({
    addresses,
    filters,
}: AddressIndexPageProps) {
    const rows: Address[] = Array.isArray(addresses)
        ? addresses
        : (addresses.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                AddressController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['addresses', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Addresses" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Addresses
                    </h2>
                    <Input
                        placeholder="Search addresses (city, state, district)..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button
                    asChild
                    className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                >
                    <Link
                        href={AddressController.create.url()}
                        className="gap-2"
                    >
                        <span>+ Add Address</span>
                    </Link>
                </Button>
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                City
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                District
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                State
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Country
                            </TableHead>
                            <TableHead className="w-[150px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((address) => (
                                <TableRow
                                    key={address.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {address.city}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-600 dark:text-zinc-400">
                                        {address.district || '-'}
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-600 dark:text-zinc-400">
                                        {address.state || '-'}
                                    </TableCell>
                                    <TableCell>
                                        {address.country ? (
                                            <span className="inline-flex items-center rounded border border-zinc-200 bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                {address.country.country_name} (
                                                {address.country.country_code})
                                            </span>
                                        ) : (
                                            '-'
                                        )}
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
                                                    href={AddressController.edit.url(
                                                        { address },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>

                                            <DeleteConfirmationModal
                                                title="Delete Address"
                                                description={`Are you sure you want to delete this address? This action cannot be undone.`}
                                                action={AddressController.destroy.form(
                                                    { address },
                                                )}
                                                onSuccess={() =>
                                                    toast.success(
                                                        `Address deleted successfully.`,
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
                                    No addresses found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(addresses) && addresses.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            addresses.prev_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>

                                {addresses.links.map((link, idx) => {
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
                                            addresses.next_page_url ?? undefined
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
