import { AuditTimelineCard } from '@/components/audit-timeline-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import { type FinanceOpdPaymentsShowPageProps } from '@/types/finance';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { OpdPaymentWorkspace } from './components/opd-payment-workspace';

export default function FinanceOpdPaymentsShowPage({
    visit,
    paymentMethods,
    audit_activity,
}: FinanceOpdPaymentsShowPageProps) {
    const paymentForm = useForm({
        amount: visit.billing?.balance_amount
            ? String(visit.billing.balance_amount)
            : '',
        payment_method_id: paymentMethods[0]?.value ?? '',
        payment_date: '',
        reference_number: '',
        notes: '',
    });
    const discountForm = useForm({
        amount: '',
        reason: '',
        notes: '',
    });
    const [reversalReasons, setReversalReasons] = useState<
        Record<string, string>
    >({});

    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Finance & Accounting', href: '/finance/opd-payments' },
        { title: 'Incoming OPD Payments', href: '/finance/opd-payments' },
        {
            title: visit.visit_number,
            href: `/finance/opd-payments/${visit.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`OPD Payment ${visit.visit_number}`} />

            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <Link
                            href="/finance/opd-payments"
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to finance queue
                        </Link>
                        <h1 className="text-2xl font-semibold">
                            OPD Payment Desk
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {patientName || 'Unknown patient'} {' | '}
                            {visit.patient?.patient_number ?? 'No MRN'} {' | '}
                            Visit {visit.visit_number}
                        </p>
                    </div>

                    <Card className="min-w-[280px]">
                        <CardHeader>
                            <CardTitle>Queue Snapshot</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Payer
                                </span>
                                <span className="capitalize">
                                    {visit.payer?.billing_type ?? 'cash'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Charges
                                </span>
                                <span>
                                    {formatMoney(visit.billing?.gross_amount)}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Collected
                                </span>
                                <span>
                                    {formatMoney(visit.billing?.paid_amount)}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted-foreground">
                                    Discounts
                                </span>
                                <span>
                                    {formatMoney(
                                        visit.billing?.discount_amount,
                                    )}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3 font-medium">
                                <span className="text-muted-foreground">
                                    Outstanding
                                </span>
                                <span>
                                    {formatMoney(visit.billing?.balance_amount)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <OpdPaymentWorkspace
                    visitId={visit.id}
                    billing={visit.billing}
                    charges={visit.charges ?? []}
                    payments={visit.billing?.payments ?? []}
                    paymentMethods={paymentMethods}
                    paymentForm={paymentForm.data}
                    paymentErrors={paymentForm.errors}
                    paymentProcessing={paymentForm.processing}
                    discounts={visit.billing?.discounts ?? []}
                    discountForm={discountForm.data}
                    discountErrors={discountForm.errors}
                    discountProcessing={discountForm.processing}
                    reversalReasons={reversalReasons}
                    onPaymentChange={(field, value) =>
                        paymentForm.setData(field, value)
                    }
                    onPaymentSubmit={() =>
                        paymentForm.post(
                            `/finance/opd-payments/${visit.id}/payments`,
                        )
                    }
                    onDiscountChange={(field, value) =>
                        discountForm.setData(field, value)
                    }
                    onDiscountSubmit={() =>
                        discountForm.post(
                            `/finance/opd-payments/${visit.id}/discounts`,
                            {
                                onSuccess: () =>
                                    discountForm.reset(
                                        'amount',
                                        'reason',
                                        'notes',
                                    ),
                            },
                        )
                    }
                    onApproveDiscount={(discountId) =>
                        router.post(
                            `/finance/opd-payments/${visit.id}/discounts/${discountId}/approve`,
                        )
                    }
                    onReverseReasonChange={(discountId, value) =>
                        setReversalReasons((current) => ({
                            ...current,
                            [discountId]: value,
                        }))
                    }
                    onReverseDiscount={(discountId) =>
                        router.post(
                            `/finance/opd-payments/${visit.id}/discounts/${discountId}/reverse`,
                            {
                                reversal_reason:
                                    reversalReasons[discountId] ?? '',
                            },
                            {
                                onSuccess: () =>
                                    setReversalReasons((current) => ({
                                        ...current,
                                        [discountId]: '',
                                    })),
                            },
                        )
                    }
                />

                <AuditTimelineCard
                    title="Billing Audit Log"
                    entries={audit_activity}
                    emptyMessage="No billing audit activity recorded for this visit yet."
                />
            </div>
        </AppLayout>
    );
}
