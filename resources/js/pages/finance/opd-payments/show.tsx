import { AuditTimelineCard } from '@/components/audit-timeline-card';
import { PatientPayerIndicator } from '@/components/patient-payer-indicator';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import { type FinanceOpdPaymentsShowPageProps } from '@/types/finance';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { OpdPaymentWorkspace } from './components/opd-payment-workspace';

export default function FinanceOpdPaymentsShowPage({
    visit,
    paymentMethods,
    currencyOptions,
    multiCurrencyEnabled,
    audit_activity,
}: FinanceOpdPaymentsShowPageProps) {
    const isInsurancePayer = visit.payer?.billing_type === 'insurance';
    const billingSplit = visit.billing?.split;
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
                <div>
                    <Link
                        href="/finance/opd-payments"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to finance queue
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold">
                        OPD Payment Desk
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        <span className="inline-flex items-center gap-2">
                            <span>{patientName || 'Unknown patient'}</span>
                            <PatientPayerIndicator
                                payerType={visit.payer?.billing_type}
                                insuranceCompanyName={
                                    visit.payer?.insuranceCompany?.name ??
                                    visit.payer?.insurance_company?.name
                                }
                                insurancePackageName={
                                    visit.payer?.insurancePackage?.name ??
                                    visit.payer?.insurance_package?.name
                                }
                                unpaidBalance={visit.billing?.balance_amount}
                            />
                        </span>{' '}
                        &middot; {visit.patient?.patient_number ?? 'No MRN'}{' '}
                        &middot; Visit {visit.visit_number}
                    </p>
                </div>

                <div className="flex flex-wrap gap-8 rounded-lg border px-5 py-4 text-sm">
                    <div>
                        <p className="text-muted-foreground">Payer</p>
                        <p className="mt-0.5 font-medium capitalize">
                            {visit.payer?.billing_type ?? 'Cash'}
                        </p>
                        {isInsurancePayer &&
                        visit.payer?.insuranceCompany?.name ? (
                            <p className="text-xs text-muted-foreground">
                                {visit.payer.insuranceCompany.name}
                            </p>
                        ) : null}
                    </div>
                    <div>
                        <p className="text-muted-foreground">Gross Charges</p>
                        <p className="mt-0.5 font-medium">
                            {formatMoney(visit.billing?.gross_amount)}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">Paid</p>
                        <p className="mt-0.5 font-medium">
                            {formatMoney(visit.billing?.paid_amount)}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">Discounts</p>
                        <p className="mt-0.5 font-medium">
                            {formatMoney(visit.billing?.discount_amount)}
                        </p>
                    </div>
                    {isInsurancePayer && billingSplit ? (
                        <>
                            <div>
                                <p className="text-muted-foreground">
                                    Patient Copay Due
                                </p>
                                <p className="mt-0.5 font-semibold">
                                    {formatMoney(
                                        billingSplit.patient_balance_amount,
                                    )}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">
                                    Insurer Balance
                                </p>
                                <p className="mt-0.5 font-medium">
                                    {formatMoney(
                                        billingSplit.insurer_balance_amount,
                                    )}
                                </p>
                            </div>
                        </>
                    ) : null}
                    <div>
                        <p className="text-muted-foreground">Outstanding</p>
                        <p className="mt-0.5 font-semibold">
                            {formatMoney(visit.billing?.balance_amount)}
                        </p>
                    </div>
                    {visit.billing?.status ? (
                        <div>
                            <p className="text-muted-foreground">
                                Billing Status
                            </p>
                            <p className="mt-0.5 font-medium capitalize">
                                {visit.billing.status.replaceAll('_', ' ')}
                            </p>
                        </div>
                    ) : null}
                </div>

                <OpdPaymentWorkspace
                    visitId={visit.id}
                    payerType={visit.payer?.billing_type ?? 'cash'}
                    billing={visit.billing}
                    charges={visit.charges ?? []}
                    payments={visit.billing?.payments ?? []}
                    paymentMethods={paymentMethods}
                    currencyOptions={currencyOptions}
                    multiCurrencyEnabled={multiCurrencyEnabled}
                    discounts={visit.billing?.discounts ?? []}
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
