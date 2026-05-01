import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { type FinanceInsuranceInvoicesIndexPageProps } from '@/types/finance';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FilePlus2 } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function FinanceInsuranceInvoicesIndexPage({
    invoices,
    readyClaimBatches,
    filters,
    statusOptions,
}: FinanceInsuranceInvoicesIndexPageProps) {
    const [status, setStatus] = useState(filters.status ?? 'all');
    const batchForm = useForm({
        insurance_company_id: readyClaimBatches[0]?.insurance_company_id ?? '',
        start_date: '',
        end_date: '',
    });

    useEffect(() => {
        if (status === (filters.status ?? 'all')) {
            return;
        }

        router.get(
            '/finance/insurance-invoices',
            { status: status === 'all' ? undefined : status },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                only: ['invoices', 'filters'],
            },
        );
    }, [filters.status, status]);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Finance & Accounting',
            href: '/finance/insurance-invoices',
        },
        {
            title: 'Insurance Invoices',
            href: '/finance/insurance-invoices',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Insurance Invoices" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-2">
                        <h1 className="text-2xl font-semibold">
                            Insurance Invoices
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Batch ready insurance claims into insurer invoices
                            and track remittances against the invoice balance.
                        </p>
                    </div>

                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-full sm:w-52">
                            <SelectValue placeholder="Invoice status" />
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

                <Card>
                    <CardHeader>
                        <CardTitle>Ready Claim Batches</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {readyClaimBatches.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No ready insurance claims are waiting for
                                invoice batching in this branch.
                            </p>
                        ) : (
                            <>
                                <div className="grid gap-3 md:grid-cols-3">
                                    {readyClaimBatches.map((batch) => (
                                        <div
                                            key={batch.insurance_company_id}
                                            className="rounded-lg border p-4"
                                        >
                                            <p className="font-medium">
                                                {batch.insurance_company_name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {batch.claims_count} claims
                                            </p>
                                            <p className="mt-2 text-lg font-semibold">
                                                {formatMoney(batch.claim_total)}
                                            </p>
                                        </div>
                                    ))}
                                </div>

                                <form
                                    className="grid gap-3 md:grid-cols-[1.3fr_1fr_1fr_auto]"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        batchForm.post(
                                            '/finance/insurance-invoices',
                                        );
                                    }}
                                >
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="insurance_company_id">
                                            Insurer
                                        </Label>
                                        <Select
                                            value={
                                                batchForm.data
                                                    .insurance_company_id
                                            }
                                            onValueChange={(value) =>
                                                batchForm.setData(
                                                    'insurance_company_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger id="insurance_company_id">
                                                <SelectValue placeholder="Select insurer" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {readyClaimBatches.map(
                                                    (batch) => (
                                                        <SelectItem
                                                            key={
                                                                batch.insurance_company_id
                                                            }
                                                            value={
                                                                batch.insurance_company_id
                                                            }
                                                        >
                                                            {
                                                                batch.insurance_company_name
                                                            }
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="start_date">
                                            Start Date
                                        </Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={batchForm.data.start_date}
                                            onChange={(event) =>
                                                batchForm.setData(
                                                    'start_date',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="end_date">
                                            End Date
                                        </Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            value={batchForm.data.end_date}
                                            onChange={(event) =>
                                                batchForm.setData(
                                                    'end_date',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="self-end"
                                        disabled={batchForm.processing}
                                    >
                                        <FilePlus2 data-icon="inline-start" />
                                        Generate
                                    </Button>
                                </form>
                            </>
                        )}
                    </CardContent>
                </Card>

                {invoices.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-14 text-center text-sm text-muted-foreground">
                            No insurer invoices have been generated for this
                            branch yet.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-border/60 bg-background">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Insurer</TableHead>
                                    <TableHead>Claims</TableHead>
                                    <TableHead>Billed</TableHead>
                                    <TableHead>Paid</TableHead>
                                    <TableHead>Balance</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {invoice.code}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDateTime(
                                                        invoice.created_at,
                                                    )}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {invoice.insurance_company_name ??
                                                'Unknown insurer'}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.claims_count}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(invoice.bill_amount)}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(invoice.paid_amount)}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {formatMoney(
                                                invoice.balance_amount,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={billingStatusClasses(
                                                    invoice.status,
                                                )}
                                            >
                                                {invoice.status.replaceAll(
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
                                                    href={`/finance/insurance-invoices/${invoice.id}`}
                                                >
                                                    Open invoice
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
