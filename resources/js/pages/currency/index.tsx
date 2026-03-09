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
import { type Currency, type CurrencyIndexPageProps } from '@/types/currency';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Coins, Lock, Unlock } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Currencies', href: CurrencyController.index.url() },
];

export default function CurrencyIndex({ currencies, filters }: CurrencyIndexPageProps) {
    const rows: Currency[] = Array.isArray(currencies) ? currencies : (currencies.data ?? []);
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
            
            <div className="mt-4 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-4">
                <div className="flex flex-col gap-1 sm:max-w-md w-full">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <Coins className="h-6 w-6 text-indigo-500" />
                        Currencies
                    </h2>
                    <Input
                        placeholder="Search currencies (name, code)..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button asChild className="shadow-sm border border-zinc-200 dark:border-zinc-800 shrink-0">
                    <Link href={CurrencyController.create.url()} className="gap-2">
                        <span>+ Add Currency</span>
                    </Link>
                </Button>
            </div>

            <div className="m-2 overflow-x-auto rounded border p-4 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[100px] uppercase tracking-wider text-xs font-semibold text-center">Code</TableHead>
                            <TableHead className="uppercase tracking-wider text-xs font-semibold text-center w-[100px]">Symbol</TableHead>
                            <TableHead className="uppercase tracking-wider text-xs font-semibold">Name</TableHead>
                            <TableHead className="uppercase tracking-wider text-xs font-semibold text-center w-[150px]">Status</TableHead>
                            <TableHead className="text-right uppercase tracking-wider text-xs font-semibold w-[150px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((currency) => (
                                <TableRow key={currency.id} className="group transition-colors">
                                    <TableCell className="text-center">
                                        <span className="font-bold text-zinc-900 dark:text-zinc-100 bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded text-sm">
                                            {currency.code}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-center font-mono text-lg text-indigo-600 dark:text-indigo-400">
                                        {currency.symbol}
                                    </TableCell>
                                    <TableCell className="font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-tight">
                                        {currency.name}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <span className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-tight ${currency.modifiable ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'}`}>
                                            {currency.modifiable ? <Unlock className="h-3 w-3" /> : <Lock className="h-3 w-3" />}
                                            {currency.modifiable ? 'Modifiable' : 'Locked'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                asChild 
                                                className="h-8 px-3 text-xs cursor-pointer border-zinc-200 dark:border-zinc-800 hover:border-indigo-500 hover:text-indigo-600 dark:hover:border-indigo-400 dark:hover:text-indigo-400 shadow-sm"
                                            >
                                                <Link href={CurrencyController.edit.url({ currency })}>
                                                    Edit
                                                </Link>
                                            </Button>
                                            
                                            {currency.modifiable && (
                                                <DeleteConfirmationModal
                                                    title="Delete Currency"
                                                    description={`Are you sure you want to delete "${currency.name}" (${currency.code})? This action cannot be undone.`}
                                                    action={CurrencyController.destroy.form({ currency })}
                                                    onSuccess={() => toast.success(`Currency deleted successfully.`)}
                                                    trigger={
                                                        <Button 
                                                            variant="destructive" 
                                                            size="sm" 
                                                            className="h-8 px-3 text-xs cursor-pointer shadow-sm"
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
                                <TableCell colSpan={5} className="py-12 text-center text-zinc-500 italic">
                                    No currencies found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {(!Array.isArray(currencies) && currencies.links?.length > 3) ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious href={currencies.prev_page_url ?? undefined} />
                                </PaginationItem>

                                {currencies.links.map((link, idx) => {
                                    const label = link.label.replace(/<[^>]*>/g, '').trim();
                                    if (label === '...') {
                                        return (
                                            <PaginationItem key={`ellipsis-${idx}`}>
                                                <PaginationEllipsis />
                                            </PaginationItem>
                                        );
                                    }
                                    if (/^\d+$/.test(label)) {
                                        return (
                                            <PaginationItem key={label}>
                                                <PaginationLink href={link.url ?? undefined} isActive={link.active}>
                                                    {label}
                                                </PaginationLink>
                                            </PaginationItem>
                                        );
                                    }
                                    return null;
                                })}

                                <PaginationItem>
                                    <PaginationNext href={currencies.next_page_url ?? undefined} />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
