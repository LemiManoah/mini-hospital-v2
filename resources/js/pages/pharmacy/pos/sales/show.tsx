import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryNavigationContext } from '@/types/inventory-navigation';
import { Head, Link, useForm } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useState } from 'react';

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
    can: { void: boolean; refund: boolean };
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

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'mobile_money', label: 'Mobile Money' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'insurance', label: 'Insurance' },
    { value: 'other', label: 'Other' },
];

export default function PharmacyPosSaleShow({ navigation, sale, can }: PharmacyPosSaleShowProps) {
    const [showVoidConfirm, setShowVoidConfirm] = useState(false);
    const [showRefundModal, setShowRefundModal] = useState(false);

    const voidForm = useForm({});
    const refundForm = useForm({
        payment_method: 'cash',
        refund_amount: sale.paid_amount.toFixed(2),
        reference_number: '',
        notes: '',
    });

    const isCompleted = sale.status === 'completed';

    const submitVoid = () => {
        voidForm.post(`/pharmacy/pos/sales/${sale.id}/void`, {
            onSuccess: () => setShowVoidConfirm(false),
        });
    };

    const submitRefund = () => {
        refundForm.post(`/pharmacy/pos/sales/${sale.id}/refund`, {
            onSuccess: () => setShowRefundModal(false),
        });
    };

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

                    <div className="flex flex-wrap gap-2">
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
                        {isCompleted && can.void && (
                            <Button
                                variant="outline"
                                className="border-rose-300 text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400"
                                onClick={() => setShowVoidConfirm(true)}
                            >
                                Void Sale
                            </Button>
                        )}
                        {isCompleted && can.refund && (
                            <Button
                                variant="outline"
                                className="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-400"
                                onClick={() => setShowRefundModal(true)}
                            >
                                Refund
                            </Button>
                        )}
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

                            <div className="flex justify-between text-lg font-semibold">
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
                                                {payment.is_refund ? '−' : ''}
                                                {payment.amount.toFixed(2)}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Void confirmation dialog */}
            <Dialog open={showVoidConfirm} onOpenChange={setShowVoidConfirm}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Void Sale</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        This will cancel <strong>{sale.sale_number}</strong> and reverse all stock
                        movements. This action cannot be undone. Continue?
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowVoidConfirm(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={voidForm.processing}
                            onClick={submitVoid}
                        >
                            {voidForm.processing ? 'Voiding…' : 'Void Sale'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Refund dialog */}
            <Dialog open={showRefundModal} onOpenChange={setShowRefundModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Refund Sale</DialogTitle>
                    </DialogHeader>
                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label>Refund Amount</Label>
                            <Input
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={refundForm.data.refund_amount}
                                onChange={(e) => refundForm.setData('refund_amount', e.target.value)}
                            />
                            {refundForm.errors.refund_amount && (
                                <p className="text-xs text-destructive">{refundForm.errors.refund_amount}</p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label>Payment Method</Label>
                            <Select
                                value={refundForm.data.payment_method}
                                onValueChange={(v) => refundForm.setData('payment_method', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {paymentMethods.map((m) => (
                                        <SelectItem key={m.value} value={m.value}>
                                            {m.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label>Reference Number (optional)</Label>
                            <Input
                                value={refundForm.data.reference_number}
                                onChange={(e) => refundForm.setData('reference_number', e.target.value)}
                                placeholder="e.g. transaction ID"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label>Notes (optional)</Label>
                            <Input
                                value={refundForm.data.notes}
                                onChange={(e) => refundForm.setData('notes', e.target.value)}
                            />
                        </div>
                        {refundForm.errors.sale && (
                            <p className="text-sm text-destructive">{refundForm.errors.sale}</p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowRefundModal(false)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={refundForm.processing}
                            onClick={submitRefund}
                        >
                            {refundForm.processing ? 'Processing…' : 'Confirm Refund'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
