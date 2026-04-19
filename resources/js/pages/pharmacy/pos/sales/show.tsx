import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryNavigationContext } from '@/types/inventory-navigation';
import { Head, Link } from '@inertiajs/react';
import { Printer } from 'lucide-react';

interface SaleItem {
    id: string;
    item_name: string | null;
    generic_name: string | null;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    line_total: number;
    notes: string | null;
}

interface SalePayment {
    id: string;
    amount: number;
    payment_method: string;
    reference_number: string | null;
    payment_date: string | null;
    is_refund: boolean;
    notes: string | null;
}

interface Sale {
    id: string;
    sale_number: string;
    sale_type: string;
    status: string | null;
    status_label: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    gross_amount: number;
    discount_amount: number;
    paid_amount: number;
    balance_amount: number;
    change_amount: number;
    sold_at: string | null;
    notes: string | null;
    inventory_location: { id: string; name: string; location_code: string } | null;
    sold_by: string | null;
    items: SaleItem[];
    payments: SalePayment[];
}

interface PharmacyPosSaleShowProps {
    navigation: InventoryNavigationContext;
    sale: Sale;
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

const methodLabel = (method: string): string => {
    const labels: Record<string, string> = {
        cash: 'Cash',
        mobile_money: 'Mobile Money',
        card: 'Card',
        bank_transfer: 'Bank Transfer',
        insurance: 'Insurance',
        other: 'Other',
    };
    return labels[method] ?? method;
};

const breadcrumbs = (navigation: InventoryNavigationContext, sale: Sale): BreadcrumbItem[] => [
    { title: navigation.section_title, href: navigation.section_href },
    { title: 'Pharmacy POS', href: '/pharmacy/pos' },
    { title: sale.sale_number, href: `/pharmacy/pos/sales/${sale.id}` },
];

export default function PharmacyPosSaleShow({ navigation, sale }: PharmacyPosSaleShowProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs(navigation, sale)}>
            <Head title={sale.sale_number} />

            <div className="flex h-full flex-col gap-6 p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-tight">{sale.sale_number}</h1>
                            <Badge variant="outline" className={statusTone(sale.status)}>
                                {sale.status_label ?? 'Unknown'}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            {sale.customer_name && <span>Customer: {sale.customer_name}</span>}
                            {sale.customer_phone && <span>Phone: {sale.customer_phone}</span>}
                            {sale.sold_at && (
                                <span>Date: {new Date(sale.sold_at).toLocaleString()}</span>
                            )}
                            {sale.sold_by && <span>Sold by: {sale.sold_by}</span>}
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/pharmacy/pos">New Sale</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <a
                                href={`/pharmacy/pos/sales/${sale.id}/print`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Printer className="mr-2 h-4 w-4" />
                                Print Receipt
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Items</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            <div className="divide-y text-sm">
                                {sale.items.map((item) => (
                                    <div key={item.id} className="flex justify-between py-2">
                                        <div>
                                            <p className="font-medium">{item.item_name ?? item.generic_name ?? '—'}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.quantity.toFixed(3)} × {item.unit_price.toFixed(2)}
                                                {item.discount_amount > 0 && ` − ${item.discount_amount.toFixed(2)}`}
                                            </p>
                                        </div>
                                        <span className="font-medium">{item.line_total.toFixed(2)}</span>
                                    </div>
                                ))}
                            </div>

                            <Separator />

                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Gross</span>
                                <span>{sale.gross_amount.toFixed(2)}</span>
                            </div>
                            {sale.discount_amount > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Discount</span>
                                    <span className="text-rose-600">− {sale.discount_amount.toFixed(2)}</span>
                                </div>
                            )}

                            <Separator />

                            <div className="flex justify-between font-semibold text-lg">
                                <span>Total</span>
                                <span>{(sale.gross_amount - sale.discount_amount).toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Paid</span>
                                <span>{sale.paid_amount.toFixed(2)}</span>
                            </div>
                            {sale.change_amount > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Change</span>
                                    <span className="text-emerald-600">{sale.change_amount.toFixed(2)}</span>
                                </div>
                            )}
                            {sale.balance_amount > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Balance Due</span>
                                    <span className="text-amber-600">{sale.balance_amount.toFixed(2)}</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Details</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Location</span>
                                    <span>{sale.inventory_location?.name ?? '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Sale Type</span>
                                    <span className="capitalize">{sale.sale_type}</span>
                                </div>
                                {sale.notes && (
                                    <div className="pt-1 text-muted-foreground">{sale.notes}</div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Payments</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2 text-sm">
                                {sale.payments.length === 0 ? (
                                    <p className="text-muted-foreground">No payments recorded.</p>
                                ) : (
                                    sale.payments.map((payment) => (
                                        <div key={payment.id} className="flex justify-between py-1">
                                            <div>
                                                <p className="font-medium">
                                                    {methodLabel(payment.payment_method)}
                                                    {payment.is_refund && (
                                                        <span className="ml-1 text-xs text-rose-600">(Refund)</span>
                                                    )}
                                                </p>
                                                {payment.reference_number && (
                                                    <p className="text-xs text-muted-foreground">
                                                        Ref: {payment.reference_number}
                                                    </p>
                                                )}
                                                {payment.payment_date && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {new Date(payment.payment_date).toLocaleString()}
                                                    </p>
                                                )}
                                            </div>
                                            <span className={payment.is_refund ? 'text-rose-600' : ''}>
                                                {payment.is_refund ? '−' : ''}{payment.amount.toFixed(2)}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
