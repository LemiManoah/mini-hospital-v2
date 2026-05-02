import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import { type FinanceBillingSummaryPageProps } from '@/types/finance';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function FinanceBillingSummaryPage({
    filters,
    summary,
    paymentMethods,
    depositStatuses,
    insurerInvoices,
}: FinanceBillingSummaryPageProps) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Finance & Accounting', href: '/finance/billing-summary' },
        { title: 'Billing Summary', href: '/finance/billing-summary' },
    ];

    const cards: Array<[string, number]> = [
        ['Gross billings', summary.gross_billings],
        ['Patient collections', summary.patient_collections],
        ['Patient refunds', summary.patient_refunds],
        ['Approved discounts', summary.approved_discounts],
        ['Approved write-offs', summary.approved_write_offs],
        ['Deposits received', summary.deposits_received],
        ['Deposits held', summary.deposits_held],
        ['Insurer invoice balance', insurerInvoices.balance],
        ['Current debtor balance', summary.current_debtor_balance],
    ];

    function refresh(nextStartDate = startDate, nextEndDate = endDate): void {
        router.get(
            '/finance/billing-summary',
            {
                start_date: nextStartDate,
                end_date: nextEndDate,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: [
                    'filters',
                    'summary',
                    'paymentMethods',
                    'depositStatuses',
                    'insurerInvoices',
                ],
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing Summary" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-2">
                        <h1 className="text-2xl font-semibold">
                            Billing Summary
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Review operational billing, collections, deposits,
                            write-offs, and insurer invoice totals for the
                            active branch.
                        </p>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="start_date">Start Date</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={startDate}
                                onChange={(event) => {
                                    setStartDate(event.target.value);
                                    refresh(event.target.value, endDate);
                                }}
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="end_date">End Date</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={endDate}
                                onChange={(event) => {
                                    setEndDate(event.target.value);
                                    refresh(startDate, event.target.value);
                                }}
                            />
                        </div>
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {cards.map(([label, value]) => (
                        <Card key={label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold">
                                    {formatMoney(value)}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Collections By Method</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Count</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paymentMethods.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                No collections found for this
                                                period.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        paymentMethods.map((method) => (
                                            <TableRow
                                                key={method.payment_method}
                                            >
                                                <TableCell className="capitalize">
                                                    {method.payment_method.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {method.count}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatMoney(method.amount)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Deposits By Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Count</TableHead>
                                        <TableHead>Applied</TableHead>
                                        <TableHead className="text-right">
                                            Held
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {depositStatuses.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-8 text-center text-sm text-muted-foreground"
                                            >
                                                No deposits found for this
                                                period.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        depositStatuses.map((status) => (
                                            <TableRow key={status.status}>
                                                <TableCell className="capitalize">
                                                    {status.status.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {status.count}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(
                                                        status.applied_amount,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatMoney(
                                                        status.held_amount,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Insurer Invoice Position</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 sm:grid-cols-3">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Invoices
                            </p>
                            <p className="text-xl font-semibold">
                                {insurerInvoices.count}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Billed
                            </p>
                            <p className="text-xl font-semibold">
                                {formatMoney(summary.insurer_invoices_billed)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Paid
                            </p>
                            <p className="text-xl font-semibold">
                                {formatMoney(summary.insurer_invoices_paid)}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
