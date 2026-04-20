import CurrencyController from '@/actions/App/Http/Controllers/CurrencyController';
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
import { type Currency, type CurrencyIndexPageProps } from '@/types/currency';
import { Head, Link, router } from '@inertiajs/react';
import { Lock, TrendingUp, Unlock } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Currencies', href: CurrencyController.index.url() },
];

export default function CurrencyIndex({
    currencies,
    filters,
}: CurrencyIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Currency[] = Array.isArray(currencies)
        ? currencies
        : (currencies.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                CurrencyController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['currencies', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Currencies" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Currencies
                    </h2>
                    <Input
                        placeholder="Search currencies (name, code)..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <div className="flex shrink-0 gap-2">
                    {hasPermission('currency_exchange_rates.view') ? (
                        <Button
                            asChild
                            variant="outline"
                            className="border border-zinc-200 shadow-sm dark:border-zinc-800"
                        >
                            <Link href="/currency-exchange-rates" className="gap-2">
                                <TrendingUp className="h-4 w-4" />
                                <span>Exchange Rates</span>
                            </Link>
                        </Button>
                    ) : null}
                    {hasPermission('currencies.create') ? (
                        <Button
                            asChild
                            className="border border-zinc-200 shadow-sm dark:border-zinc-800"
                        >
                            <Link
                                href={CurrencyController.create.url()}
                                className="gap-2"
                            >
                                <span>+ Add Currency</span>
                            </Link>
                        </Button>
                    ) : null}
                </div>
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[100px] text-center text-xs font-semibold tracking-wider uppercase">
                                Code
                            </TableHead>
                            <TableHead className="w-[100px] text-center text-xs font-semibold tracking-wider uppercase">
                                Symbol
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Name
                            </TableHead>
                            <TableHead className="w-[150px] text-center text-xs font-semibold tracking-wider uppercase">
                                Status
                            </TableHead>
                            <TableHead className="w-[150px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((currency) => (
                                <TableRow
                                    key={currency.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="text-center">
                                        <span className="rounded bg-zinc-100 px-2 py-1 text-sm font-bold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
                                            {currency.code}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-center font-mono text-lg text-indigo-600 dark:text-indigo-400">
                                        {currency.symbol}
                                    </TableCell>
                                    <TableCell className="font-semibold tracking-tight text-zinc-900 uppercase dark:text-zinc-100">
                                        {currency.name}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <span
                                            className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${currency.modifiable ? 'border-green-200 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300' : 'border-red-200 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300'}`}
                                        >
                                            {currency.modifiable ? (
                                                <Unlock className="h-3 w-3" />
                                            ) : (
                                                <Lock className="h-3 w-3" />
                                            )}
                                            {currency.modifiable
                                                ? 'Modifiable'
                                                : 'Locked'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'currencies.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={CurrencyController.edit.url(
                                                            { currency },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {currency.modifiable &&
                                            hasPermission(
                                                'currencies.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Currency"
                                                    description={`Are you sure you want to delete "${currency.name}" (${currency.code})? This action cannot be undone.`}
                                                    action={CurrencyController.destroy.form(
                                                        { currency },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Currency deleted successfully.`,
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
                                    No currencies found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(currencies) && currencies.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            currencies.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>

                                {currencies.links.map((link, idx) => {
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
                                            currencies.next_page_url ??
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
