import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryNavigationContext } from '@/types/inventory-navigation';
import { Head, Link, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useState } from 'react';

interface SaleSummary {
    id: string;
    sale_number: string;
    status: string | null;
    status_label: string | null;
    customer_name: string | null;
    gross_amount: number;
    discount_amount: number;
    paid_amount: number;
    balance_amount: number;
    sold_at: string | null;
    location_name: string | null;
    sold_by: string | null;
}

interface StatusOption {
    value: string;
    label: string;
}

interface PaginatedSales {
    data: SaleSummary[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface PharmacyPosHistoryProps {
    navigation: InventoryNavigationContext;
    sales: PaginatedSales;
    filters: { search: string; status: string; from: string; to: string };
    statuses: StatusOption[];
}

const statusTone = (status: string | null): string => {
    switch (status) {
        case 'completed':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
        case 'cancelled':
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-300';
        case 'refunded':
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300';
    }
};

const breadcrumbs = (
    navigation: InventoryNavigationContext,
): BreadcrumbItem[] => [
    { title: navigation.section_title, href: navigation.section_href },
    { title: 'Pharmacy POS', href: '/pharmacy/pos' },
    { title: 'Sales History', href: '/pharmacy/pos/history' },
];

export default function PharmacyPosHistory({
    navigation,
    sales,
    filters,
    statuses,
}: PharmacyPosHistoryProps) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const applyFilters = () => {
        router.get(
            '/pharmacy/pos/history',
            { search, status, from, to },
            { preserveScroll: true, replace: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setFrom('');
        setTo('');
        router.get(
            '/pharmacy/pos/history',
            {},
            { preserveScroll: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(navigation)}>
            <Head title="Sales History" />

            <div className="flex h-full flex-col gap-6 p-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Sales History
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {sales.total} sale{sales.total !== 1 ? 's' : ''} found
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-3">
                        <Input
                            placeholder="Search sale # or customer..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters()
                            }
                            className="w-56"
                        />
                        <Select
                            value={status || undefined}
                            onValueChange={setStatus}
                        >
                            <SelectTrigger className="w-40">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                {statuses.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Input
                            type="date"
                            value={from}
                            onChange={(e) => setFrom(e.target.value)}
                            className="w-40"
                        />
                        <Input
                            type="date"
                            value={to}
                            onChange={(e) => setTo(e.target.value)}
                            className="w-40"
                        />
                        <Button onClick={applyFilters}>Apply</Button>
                        {(filters.search ||
                            filters.status ||
                            filters.from ||
                            filters.to) && (
                            <Button variant="outline" onClick={clearFilters}>
                                Clear
                            </Button>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {sales.data.length === 0 ? (
                            <p className="p-6 text-center text-sm text-muted-foreground">
                                No sales found. Try adjusting the filters.
                            </p>
                        ) : (
                            <div className="divide-y">
                                {sales.data.map((sale) => (
                                    <div
                                        key={sale.id}
                                        className="flex items-center justify-between gap-4 px-6 py-4"
                                    >
                                        <div className="flex flex-col gap-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-semibold">
                                                    {sale.sale_number}
                                                </span>
                                                <Badge
                                                    variant="outline"
                                                    className={statusTone(
                                                        sale.status,
                                                    )}
                                                >
                                                    {sale.status_label ??
                                                        'Unknown'}
                                                </Badge>
                                            </div>
                                            <div className="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                                                {sale.customer_name && (
                                                    <span>
                                                        {sale.customer_name}
                                                    </span>
                                                )}
                                                {sale.location_name && (
                                                    <span>
                                                        {sale.location_name}
                                                    </span>
                                                )}
                                                {sale.sold_by && (
                                                    <span>{sale.sold_by}</span>
                                                )}
                                                {sale.sold_at && (
                                                    <span>
                                                        {new Date(
                                                            sale.sold_at,
                                                        ).toLocaleString()}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <div className="text-right">
                                                <p className="text-sm font-semibold">
                                                    {(
                                                        sale.gross_amount -
                                                        sale.discount_amount
                                                    ).toFixed(2)}
                                                </p>
                                                {sale.balance_amount > 0 && (
                                                    <p className="text-xs text-amber-600">
                                                        Bal:{' '}
                                                        {sale.balance_amount.toFixed(
                                                            2,
                                                        )}
                                                    </p>
                                                )}
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={`/pharmacy/pos/sales/${sale.id}`}
                                                >
                                                    <Eye className="mr-1 h-3 w-3" />
                                                    View
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {sales.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {sales.links.map((link, i) => (
                            <Button
                                key={i}
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
                                disabled={link.url === null}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
