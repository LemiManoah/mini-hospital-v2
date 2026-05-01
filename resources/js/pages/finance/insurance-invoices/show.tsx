import { AuditTimelineCard } from '@/components/audit-timeline-card';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    billingStatusClasses,
    formatDateTime,
    formatMoney,
} from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import { type FinanceInsuranceInvoicesShowPageProps } from '@/types/finance';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ReceiptText } from 'lucide-react';

type AllocationFormRow = {
    insured_visit_claim_id: string;
    allocated_amount: string;
    notes: string;
};

export default function FinanceInsuranceInvoicesShowPage({
    invoice,
    audit_activity,
}: FinanceInsuranceInvoicesShowPageProps) {
    const payableClaims = invoice.claims.filter(
        (claim) => claim.outstanding_amount > 0,
    );
    const paymentForm = useForm({
        paid_amount:
            invoice.balance_amount > 0 ? String(invoice.balance_amount) : '',
        payment_date: '',
        receipt: '',
        allocations: payableClaims.map(
            (claim): AllocationFormRow => ({
                insured_visit_claim_id: claim.id,
                allocated_amount:
                    claim.outstanding_amount > 0
                        ? String(claim.outstanding_amount)
                        : '',
                notes: '',
            }),
        ),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Finance & Accounting',
            href: '/finance/insurance-invoices',
        },
        {
            title: 'Insurance Invoices',
            href: '/finance/insurance-invoices',
        },
        {
            title: invoice.code,
            href: `/finance/insurance-invoices/${invoice.id}`,
        },
    ];

    const updateAllocation = (
        claimId: string,
        field: keyof Omit<AllocationFormRow, 'insured_visit_claim_id'>,
        value: string,
    ) => {
        paymentForm.setData(
            'allocations',
            paymentForm.data.allocations.map((allocation) =>
                allocation.insured_visit_claim_id === claimId
                    ? { ...allocation, [field]: value }
                    : allocation,
            ),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Insurance Invoice ${invoice.code}`} />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="flex flex-col gap-2">
                        <Link
                            href="/finance/insurance-invoices"
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft data-icon="inline-start" />
                            Back to insurance invoices
                        </Link>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-semibold">
                                {invoice.code}
                            </h1>
                            <Badge
                                variant="outline"
                                className={billingStatusClasses(invoice.status)}
                            >
                                {invoice.status.replaceAll('_', ' ')}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {invoice.insurance_company_name ??
                                'Unknown insurer'}{' '}
                            {' | '}
                            {invoice.claims_count} claims
                        </p>
                    </div>

                    <Card className="min-w-[280px]">
                        <CardHeader>
                            <CardTitle>Invoice Snapshot</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Billed
                                </span>
                                <span>{formatMoney(invoice.bill_amount)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Paid
                                </span>
                                <span>{formatMoney(invoice.paid_amount)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3 font-medium">
                                <span className="text-muted-foreground">
                                    Balance
                                </span>
                                <span>
                                    {formatMoney(invoice.balance_amount)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Claim Lines</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-hidden rounded-lg border border-border/60">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Claim</TableHead>
                                            <TableHead>Patient</TableHead>
                                            <TableHead>Claimed</TableHead>
                                            <TableHead>Paid</TableHead>
                                            <TableHead>Outstanding</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.claims.map((claim) => (
                                            <TableRow key={claim.id}>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <span className="font-medium">
                                                            {
                                                                claim.claim_reference
                                                            }
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {claim.visit_number ??
                                                                'No visit'}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <span>
                                                            {claim.patient_name}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {claim.patient_number ??
                                                                'No MRN'}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(
                                                        claim.claimed_amount,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(
                                                        claim.paid_amount,
                                                    )}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {formatMoney(
                                                        claim.outstanding_amount,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {claim.status.replaceAll(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Record Remittance</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {invoice.balance_amount <= 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        This insurer invoice is fully settled.
                                    </p>
                                ) : payableClaims.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No claim lines have an outstanding
                                        balance for allocation.
                                    </p>
                                ) : (
                                    <form
                                        className="flex flex-col gap-4"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            paymentForm.post(
                                                `/finance/insurance-invoices/${invoice.id}/payments`,
                                            );
                                        }}
                                    >
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="flex flex-col gap-2">
                                                <Label htmlFor="paid_amount">
                                                    Paid Amount
                                                </Label>
                                                <Input
                                                    id="paid_amount"
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    max={String(
                                                        invoice.balance_amount,
                                                    )}
                                                    value={
                                                        paymentForm.data
                                                            .paid_amount
                                                    }
                                                    onChange={(event) =>
                                                        paymentForm.setData(
                                                            'paid_amount',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        paymentForm.errors
                                                            .paid_amount
                                                    }
                                                />
                                            </div>
                                            <div className="flex flex-col gap-2">
                                                <Label htmlFor="payment_date">
                                                    Payment Date
                                                </Label>
                                                <Input
                                                    id="payment_date"
                                                    type="date"
                                                    value={
                                                        paymentForm.data
                                                            .payment_date
                                                    }
                                                    onChange={(event) =>
                                                        paymentForm.setData(
                                                            'payment_date',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                        </div>
                                        <div className="flex flex-col gap-2">
                                            <Label htmlFor="receipt">
                                                Remittance Reference
                                            </Label>
                                            <Input
                                                id="receipt"
                                                value={paymentForm.data.receipt}
                                                onChange={(event) =>
                                                    paymentForm.setData(
                                                        'receipt',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="flex flex-col gap-3">
                                            {payableClaims.map((claim) => {
                                                const allocation =
                                                    paymentForm.data.allocations.find(
                                                        (row) =>
                                                            row.insured_visit_claim_id ===
                                                            claim.id,
                                                    );

                                                return (
                                                    <div
                                                        key={claim.id}
                                                        className="rounded-lg border p-3"
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p className="font-medium">
                                                                    {
                                                                        claim.claim_reference
                                                                    }
                                                                </p>
                                                                <p className="text-sm text-muted-foreground">
                                                                    {
                                                                        claim.patient_name
                                                                    }
                                                                </p>
                                                            </div>
                                                            <p className="text-sm font-medium">
                                                                {formatMoney(
                                                                    claim.outstanding_amount,
                                                                )}
                                                            </p>
                                                        </div>
                                                        <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_1.4fr]">
                                                            <div className="flex flex-col gap-2">
                                                                <Label
                                                                    htmlFor={`allocation_${claim.id}`}
                                                                >
                                                                    Allocation
                                                                </Label>
                                                                <Input
                                                                    id={`allocation_${claim.id}`}
                                                                    type="number"
                                                                    min="0.01"
                                                                    step="0.01"
                                                                    max={String(
                                                                        claim.outstanding_amount,
                                                                    )}
                                                                    value={
                                                                        allocation?.allocated_amount ??
                                                                        ''
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateAllocation(
                                                                            claim.id,
                                                                            'allocated_amount',
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="flex flex-col gap-2">
                                                                <Label
                                                                    htmlFor={`notes_${claim.id}`}
                                                                >
                                                                    Notes
                                                                </Label>
                                                                <Input
                                                                    id={`notes_${claim.id}`}
                                                                    value={
                                                                        allocation?.notes ??
                                                                        ''
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateAllocation(
                                                                            claim.id,
                                                                            'notes',
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={paymentForm.processing}
                                        >
                                            <ReceiptText data-icon="inline-start" />
                                            Record Remittance
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Remittance History</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                {invoice.payments.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No insurer payments have been recorded
                                        for this invoice yet.
                                    </p>
                                ) : (
                                    invoice.payments.map((payment) => (
                                        <div
                                            key={payment.id}
                                            className="rounded-lg border p-3"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">
                                                        {formatMoney(
                                                            payment.paid_amount,
                                                        )}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {payment.receipt ??
                                                            'No reference'}
                                                    </p>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {formatDateTime(
                                                        payment.payment_date,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="mt-3 flex flex-col gap-1 text-sm text-muted-foreground">
                                                {payment.allocations.map(
                                                    (allocation) => (
                                                        <div
                                                            key={allocation.id}
                                                            className="flex items-center justify-between gap-3"
                                                        >
                                                            <span>
                                                                {allocation.claim_reference ??
                                                                    'Claim'}
                                                            </span>
                                                            <span>
                                                                {formatMoney(
                                                                    allocation.allocated_amount,
                                                                )}
                                                            </span>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <AuditTimelineCard
                    title="Insurance Billing Audit Log"
                    entries={audit_activity}
                    emptyMessage="No insurer invoice audit activity recorded yet."
                />
            </div>
        </AppLayout>
    );
}
