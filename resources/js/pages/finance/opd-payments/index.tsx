import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import {
    billingStatusClasses,
    formatDateTime,
    formatMoney,
} from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import { type FinanceOpdPaymentsIndexPageProps } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FinanceOpdPaymentsIndexPage({
    visits,
    filters,
    payerTypeOptions,
    statusOptions,
}: FinanceOpdPaymentsIndexPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [payerType, setPayerType] = useState(filters.payer_type ?? 'all');
    const [status, setStatus] = useState(filters.status ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            payerType === (filters.payer_type ?? 'all') &&
            status === (filters.status ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/finance/opd-payments',
                {
                    search: search || undefined,
                    payer_type: payerType === 'all' ? undefined : payerType,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['visits', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [
        filters.payer_type,
        filters.search,
        filters.status,
        payerType,
        search,
        status,
    ]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Finance & Accounting', href: '/finance/opd-payments' },
        { title: 'Incoming OPD Payments', href: '/finance/opd-payments' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incoming OPD Payments" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold">
                            Incoming OPD Payments
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Receive outpatient payments from a dedicated finance
                            queue instead of the clinical visit profile.
                        </p>
                        <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                            <span>Visits in queue: {visits.total}</span>
                            <span>
                                Outstanding total:{' '}
                                {formatMoney(
                                    visits.data.reduce(
                                        (sum, visit) =>
                                            sum +
                                            (visit.billing?.balance_amount ??
                                                0),
                                        0,
                                    ),
                                )}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient, MRN, or visit..."
                            className="min-w-72"
                        />
                        <Select value={payerType} onValueChange={setPayerType}>
                            <SelectTrigger className="w-full sm:w-44">
                                <SelectValue placeholder="Payer" />
                            </SelectTrigger>
                            <SelectContent>
                                {payerTypeOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger className="w-full sm:w-52">
                                <SelectValue placeholder="Billing status" />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {visits.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-14 text-center text-sm text-muted-foreground">
                            No OPD visits are currently waiting for payment in
                            this branch.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-border/60 bg-background">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Patient</TableHead>
                                    <TableHead>Visit</TableHead>
                                    <TableHead>Payer</TableHead>
                                    <TableHead>Charges</TableHead>
                                    <TableHead>Collected</TableHead>
                                    <TableHead>Balance</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {visits.data.map((visit) => (
                                    <TableRow key={visit.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {visit.patient?.full_name ??
                                                        'Unknown patient'}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {visit.patient
                                                        ?.patient_number ??
                                                        'No MRN'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {visit.visit_number}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDateTime(
                                                        visit.registered_at,
                                                    )}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="capitalize">
                                                    {visit.payer
                                                        ?.billing_type ??
                                                        'cash'}
                                                </span>
                                                {visit.payer
                                                    ?.insurance_company_name ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {
                                                            visit.payer
                                                                .insurance_company_name
                                                        }
                                                    </span>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                visit.billing?.gross_amount,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                visit.billing?.paid_amount,
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {formatMoney(
                                                visit.billing?.balance_amount,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={billingStatusClasses(
                                                    visit.billing?.status ??
                                                        'pending',
                                                )}
                                            >
                                                {(
                                                    visit.billing?.status ??
                                                    'pending'
                                                ).replaceAll('_', ' ')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                type="button"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/finance/opd-payments/${visit.id}`}
                                                >
                                                    Open queue item
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
