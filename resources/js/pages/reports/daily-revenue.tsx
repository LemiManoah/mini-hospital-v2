import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Download, TrendingUp } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Daily Revenue', href: '/reports/daily-revenue' },
];

interface Payment {
    id: string;
    receipt_number: string | null;
    payment_method: string | null;
    reference_number: string | null;
    amount: string;
    is_refund: boolean;
    payment_date: string | null;
    visit: {
        visit_number: string | null;
        patient: {
            patient_number: string;
            first_name: string;
            middle_name: string | null;
            last_name: string;
        } | null;
    } | null;
}

interface ReportData {
    date: string;
    currency: string;
    total_amount: number;
    total_count: number;
    refund_amount: number;
    net_amount: number;
    by_method: Record<string, number>;
    rows: Payment[];
}

interface Props {
    report: ReportData | null;
    filters: { date: string };
}

function fmt(currency: string, amount: number): string {
    return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function patientName(p: Payment['visit']['patient'] | null): string {
    if (!p) return '—';
    return [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(' ');
}

export default function DailyRevenueReport({ report, filters }: Props) {
    const [date, setDate] = useState(
        filters.date ?? new Date().toISOString().slice(0, 10),
    );

    function apply() {
        router.get(
            '/reports/daily-revenue',
            { date },
            { preserveScroll: true },
        );
    }

    function downloadPdf() {
        window.location.href = `/reports/daily-revenue/download?date=${date}`;
    }

    const currency = report?.currency ?? 'UGX';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Revenue Report" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <TrendingUp className="h-6 w-6 text-blue-600" />
                        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                            Daily Revenue Report
                        </h1>
                    </div>
                </div>

                {/* Filter bar */}
                <div className="flex flex-wrap items-end gap-4 rounded-lg border bg-white p-4 dark:bg-gray-900">
                    <div className="space-y-1">
                        <Label htmlFor="date">Date</Label>
                        <Input
                            id="date"
                            type="date"
                            value={date}
                            onChange={(e) => setDate(e.target.value)}
                            className="w-44"
                        />
                    </div>
                    <Button onClick={apply}>Apply</Button>
                    <Button
                        variant="outline"
                        onClick={downloadPdf}
                        className="ml-auto gap-2"
                    >
                        <Download className="h-4 w-4" />
                        Download PDF
                    </Button>
                </div>

                {report ? (
                    <>
                        {/* KPI cards */}
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Total Collected
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-blue-600">
                                        {fmt(currency, report.total_amount)}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {report.total_count} transaction(s)
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Refunds
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-red-600">
                                        {fmt(currency, report.refund_amount)}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Net Revenue
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-green-600">
                                        {fmt(currency, report.net_amount)}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        By Method
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-1">
                                    {Object.entries(report.by_method).map(
                                        ([method, amount]) => (
                                            <div
                                                key={method}
                                                className="flex justify-between text-xs"
                                            >
                                                <span className="capitalize">
                                                    {method.replace(/_/g, ' ')}
                                                </span>
                                                <span className="font-medium">
                                                    {fmt(currency, amount)}
                                                </span>
                                            </div>
                                        ),
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Transactions table */}
                        <div className="rounded-lg border bg-white dark:bg-gray-900">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-8">#</TableHead>
                                        <TableHead>Receipt No.</TableHead>
                                        <TableHead>Patient</TableHead>
                                        <TableHead>Visit No.</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead>Time</TableHead>
                                        <TableHead>Type</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report.rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={9}
                                                className="py-10 text-center text-gray-400"
                                            >
                                                No payments recorded for this
                                                date.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        report.rows.map((row, i) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="text-gray-400">
                                                    {i + 1}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs">
                                                    {row.receipt_number ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <span>
                                                        {patientName(
                                                            row.visit
                                                                ?.patient ??
                                                                null,
                                                        )}
                                                    </span>
                                                    {row.visit?.patient
                                                        ?.patient_number && (
                                                        <span className="block text-xs text-gray-400">
                                                            {
                                                                row.visit
                                                                    .patient
                                                                    .patient_number
                                                            }
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs">
                                                    {row.visit?.visit_number ??
                                                        '—'}
                                                </TableCell>
                                                <TableCell className="capitalize">
                                                    {(
                                                        row.payment_method ??
                                                        '—'
                                                    ).replace(/_/g, ' ')}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    {row.reference_number ??
                                                        '—'}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {fmt(
                                                        currency,
                                                        parseFloat(row.amount),
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    {row.payment_date
                                                        ? new Date(
                                                              row.payment_date,
                                                          ).toLocaleTimeString(
                                                              [],
                                                              {
                                                                  hour: '2-digit',
                                                                  minute: '2-digit',
                                                              },
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {row.is_refund ? (
                                                        <Badge
                                                            variant="destructive"
                                                            className="text-xs"
                                                        >
                                                            Refund
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-green-100 text-xs text-green-800 hover:bg-green-100">
                                                            Payment
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                                {report.rows.length > 0 && (
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="font-bold"
                                            >
                                                Total
                                            </TableCell>
                                            <TableCell className="text-right font-bold">
                                                {fmt(
                                                    currency,
                                                    report.total_amount,
                                                )}
                                            </TableCell>
                                            <TableCell colSpan={2} />
                                        </TableRow>
                                    </TableFooter>
                                )}
                            </Table>
                        </div>
                    </>
                ) : (
                    <div className="rounded-lg border bg-white p-12 text-center text-gray-400 dark:bg-gray-900">
                        Select a date and click Apply to load the report.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
