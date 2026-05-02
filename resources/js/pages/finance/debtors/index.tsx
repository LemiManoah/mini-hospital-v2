import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
import { type FinanceDebtorsIndexPageProps } from '@/types/finance';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FinanceDebtorsIndexPage({
    billings,
    filters,
}: FinanceDebtorsIndexPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/finance/debtors',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['billings', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, search]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Finance & Accounting', href: '/finance/debtors' },
        { title: 'Debtors', href: '/finance/debtors' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Debtors" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-2">
                        <h1 className="text-2xl font-semibold">Debtors</h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Review outstanding visit balances and manage
                            governed write-off requests.
                        </p>
                        <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                            <span>Open balances: {billings.total}</span>
                            <span>
                                Listed balance:{' '}
                                {formatMoney(
                                    billings.data.reduce(
                                        (sum, billing) =>
                                            sum + billing.balance_amount,
                                        0,
                                    ),
                                )}
                            </span>
                        </div>
                    </div>

                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search patient, MRN, or visit..."
                        className="min-w-72"
                    />
                </div>

                {billings.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-14 text-center text-sm text-muted-foreground">
                            No outstanding debtor balances were found in this
                            branch.
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
                                    <TableHead>Gross</TableHead>
                                    <TableHead>Paid</TableHead>
                                    <TableHead>Written Off</TableHead>
                                    <TableHead>Balance</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {billings.data.map((billing) => (
                                    <TableRow key={billing.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {billing.patient_name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {billing.patient_number ??
                                                        'No MRN'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {billing.visit_number ??
                                                        'No visit'}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDateTime(
                                                        billing.registered_at,
                                                    )}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="capitalize">
                                                    {billing.payer_type}
                                                </span>
                                                {billing.insurance_company_name ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {
                                                            billing.insurance_company_name
                                                        }
                                                    </span>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(billing.gross_amount)}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(billing.paid_amount)}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                billing.write_off_amount,
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {formatMoney(
                                                billing.balance_amount,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={billingStatusClasses(
                                                    billing.status,
                                                )}
                                            >
                                                {billing.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                type="button"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/finance/debtors/${billing.id}`}
                                                >
                                                    Open debtor
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
