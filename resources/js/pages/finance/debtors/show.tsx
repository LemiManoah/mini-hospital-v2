import { AuditTimelineCard } from '@/components/audit-timeline-card';
import InputError from '@/components/input-error';
import { PatientPayerIndicator } from '@/components/patient-payer-indicator';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import {
    billingStatusClasses,
    formatDateTime,
    formatMoney,
} from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import { type FinanceDebtorsShowPageProps } from '@/types/finance';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function FinanceDebtorsShowPage({
    billing,
    audit_activity,
}: FinanceDebtorsShowPageProps) {
    const { hasPermission } = usePermissions();
    const canRequestWriteOff = hasPermission('billing_write_offs.create');
    const canApproveWriteOff = hasPermission('billing_write_offs.approve');
    const canReverseWriteOff = hasPermission('billing_write_offs.reverse');
    const [reversalReasons, setReversalReasons] = useState<
        Record<string, string>
    >({});
    const writeOffForm = useForm({
        amount:
            billing.balance_amount > 0 ? String(billing.balance_amount) : '',
        reason: '',
        notes: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Finance & Accounting', href: '/finance/debtors' },
        { title: 'Debtors', href: '/finance/debtors' },
        {
            title: billing.visit_number ?? billing.id,
            href: `/finance/debtors/${billing.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Debtor ${billing.visit_number ?? billing.id}`} />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="flex flex-col gap-2">
                        <Link
                            href="/finance/debtors"
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft data-icon="inline-start" />
                            Back to debtors
                        </Link>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-semibold">
                                {billing.patient_name}
                            </h1>
                            <PatientPayerIndicator
                                payerType={billing.payer_type}
                                insuranceCompanyName={
                                    billing.insurance_company_name
                                }
                                insurancePackageName={
                                    billing.insurance_package_name
                                }
                            />
                            <Badge
                                variant="outline"
                                className={billingStatusClasses(billing.status)}
                            >
                                {billing.status.replaceAll('_', ' ')}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {billing.patient_number ?? 'No MRN'} {' | '}
                            {billing.visit_number ?? 'No visit'}
                        </p>
                    </div>

                    <Card className="min-w-[280px]">
                        <CardHeader>
                            <CardTitle>Balance Snapshot</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Gross
                                </span>
                                <span>{formatMoney(billing.gross_amount)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Paid
                                </span>
                                <span>{formatMoney(billing.paid_amount)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Discounts
                                </span>
                                <span>
                                    {formatMoney(billing.discount_amount)}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Written Off
                                </span>
                                <span>
                                    {formatMoney(billing.write_off_amount)}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3 font-medium">
                                <span className="text-muted-foreground">
                                    Balance
                                </span>
                                <span>
                                    {formatMoney(billing.balance_amount)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Charges</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                {billing.charges.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No charges were found for this debtor
                                        balance.
                                    </p>
                                ) : (
                                    billing.charges.map((charge) => (
                                        <div
                                            key={charge.id}
                                            className="rounded-lg border p-3"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">
                                                        {charge.description}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {formatDateTime(
                                                            charge.charged_at,
                                                        )}
                                                    </p>
                                                </div>
                                                <p className="font-medium">
                                                    {formatMoney(
                                                        charge.line_total,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Payment History</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                {billing.payments.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No payments have been recorded against
                                        this balance.
                                    </p>
                                ) : (
                                    billing.payments.map((payment) => (
                                        <div
                                            key={payment.id}
                                            className="rounded-lg border p-3"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">
                                                        {formatMoney(
                                                            payment.amount,
                                                        )}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {payment.payment_method ??
                                                            'Method not set'}
                                                    </p>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {formatDateTime(
                                                        payment.payment_date,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Write-Off Governance</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            {billing.balance_amount <= 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    This debtor balance is already settled.
                                </p>
                            ) : canRequestWriteOff ? (
                                <form
                                    className="flex flex-col gap-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        writeOffForm.post(
                                            `/finance/debtors/${billing.id}/write-offs`,
                                            {
                                                onSuccess: () =>
                                                    writeOffForm.reset(
                                                        'reason',
                                                        'notes',
                                                    ),
                                            },
                                        );
                                    }}
                                >
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="write_off_amount">
                                            Amount
                                        </Label>
                                        <Input
                                            id="write_off_amount"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            max={String(billing.balance_amount)}
                                            value={writeOffForm.data.amount}
                                            onChange={(event) =>
                                                writeOffForm.setData(
                                                    'amount',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={writeOffForm.errors.amount}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="write_off_reason">
                                            Reason
                                        </Label>
                                        <Input
                                            id="write_off_reason"
                                            value={writeOffForm.data.reason}
                                            onChange={(event) =>
                                                writeOffForm.setData(
                                                    'reason',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={writeOffForm.errors.reason}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="write_off_notes">
                                            Notes
                                        </Label>
                                        <Textarea
                                            id="write_off_notes"
                                            value={writeOffForm.data.notes}
                                            onChange={(event) =>
                                                writeOffForm.setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={writeOffForm.processing}
                                    >
                                        Request Write-Off
                                    </Button>
                                </form>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    You do not have permission to request
                                    write-offs.
                                </p>
                            )}

                            {billing.write_offs.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No write-offs have been requested for this
                                    debtor balance.
                                </p>
                            ) : (
                                billing.write_offs.map((writeOff) => (
                                    <div
                                        key={writeOff.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    {formatMoney(
                                                        writeOff.amount,
                                                    )}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {writeOff.reason}
                                                </p>
                                            </div>
                                            <Badge variant="secondary">
                                                {writeOff.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>
                                        </div>
                                        {writeOff.notes ? (
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {writeOff.notes}
                                            </p>
                                        ) : null}
                                        {writeOff.reversal_reason ? (
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                Reversal:{' '}
                                                {writeOff.reversal_reason}
                                            </p>
                                        ) : null}
                                        {writeOff.status === 'pending' &&
                                        canApproveWriteOff ? (
                                            <div className="mt-3 flex justify-end">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            `/finance/debtors/${billing.id}/write-offs/${writeOff.id}/approve`,
                                                        )
                                                    }
                                                >
                                                    Approve
                                                </Button>
                                            </div>
                                        ) : null}
                                        {writeOff.status === 'approved' &&
                                        canReverseWriteOff ? (
                                            <form
                                                className="mt-3 flex flex-col gap-3"
                                                onSubmit={(event) => {
                                                    event.preventDefault();
                                                    router.post(
                                                        `/finance/debtors/${billing.id}/write-offs/${writeOff.id}/reverse`,
                                                        {
                                                            reversal_reason:
                                                                reversalReasons[
                                                                    writeOff.id
                                                                ] ?? '',
                                                        },
                                                    );
                                                }}
                                            >
                                                <div className="flex flex-col gap-2">
                                                    <Label
                                                        htmlFor={`write_off_reversal_${writeOff.id}`}
                                                    >
                                                        Reversal Reason
                                                    </Label>
                                                    <Input
                                                        id={`write_off_reversal_${writeOff.id}`}
                                                        value={
                                                            reversalReasons[
                                                                writeOff.id
                                                            ] ?? ''
                                                        }
                                                        onChange={(event) =>
                                                            setReversalReasons(
                                                                (current) => ({
                                                                    ...current,
                                                                    [writeOff.id]:
                                                                        event
                                                                            .target
                                                                            .value,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    size="sm"
                                                    className="self-end"
                                                >
                                                    Reverse
                                                </Button>
                                            </form>
                                        ) : null}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <AuditTimelineCard
                    title="Debtor Audit Log"
                    entries={audit_activity}
                    emptyMessage="No debtor audit activity recorded yet."
                />
            </div>
        </AppLayout>
    );
}
