import VisitCompletionModal from '@/components/visit-completion-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type VisitCompletionCheck } from '@/types/patient';
import { CalendarClock } from 'lucide-react';
import {
    formatAge,
    formatDate,
    formatDateTime,
    formatMoney,
} from './visit-show-utils';

type VisitOverviewTabProps = {
    visit: {
        id: string;
        visit_number: string;
        status: string;
        visit_type: string;
        registered_at: string | null;
        created_at: string;
        started_at?: string | null;
        completed_at: string | null;
        registeredBy?: { name?: string | null } | null;
        registered_by?: { name?: string | null } | null;
        payer?: {
            billing_type: 'cash' | 'insurance';
            insuranceCompany?: { name?: string | null } | null;
            insurancePackage?: { name?: string | null } | null;
            insurance_company?: { name?: string | null } | null;
            insurance_package?: { name?: string | null } | null;
        } | null;
        billing?: {
            gross_amount?: number | null;
            paid_amount?: number | null;
            balance_amount?: number | null;
            status?: string | null;
        } | null;
        patient?: {
            first_name: string;
            middle_name?: string | null;
            last_name: string;
            patient_number?: string | null;
            gender?: string | null;
            date_of_birth?: string | null;
            age?: number | null;
            age_units?: string | null;
            phone_number?: string | null;
            blood_group?: string | null;
        } | null;
    };
    timeline: { label: string; value: string }[];
    completionCheck?: VisitCompletionCheck;
    canUpdateVisit: boolean;
};

function SummaryItem({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className="mt-1 font-medium">{value}</p>
        </div>
    );
}

export function VisitOverviewTab({
    visit,
    timeline,
    completionCheck,
    canUpdateVisit,
}: VisitOverviewTabProps) {
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');

    const patientAge = visit.patient?.date_of_birth
        ? `${formatAge(visit.patient.age, visit.patient.age_units)} (${formatDate(visit.patient.date_of_birth)})`
        : formatAge(visit.patient?.age, visit.patient?.age_units);
    const registeredBy =
        visit.registeredBy?.name || visit.registered_by?.name || 'Unknown';
    const insurer =
        visit.payer?.insuranceCompany?.name ??
        visit.payer?.insurance_company?.name ??
        'Not applicable';
    const packageName =
        visit.payer?.insurancePackage?.name ??
        visit.payer?.insurance_package?.name ??
        'Not applicable';
    const billingStatus =
        visit.billing?.status?.replaceAll('_', ' ') ?? 'Not billed';
    const canCompleteVisit =
        canUpdateVisit &&
        ['in_progress', 'awaiting_payment'].includes(visit.status);

    return (
        <div className="space-y-6">
            <Card className="bg-muted/20">
                <CardHeader>
                    <CardTitle>Patient Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <SummaryItem
                        label="Patient"
                        value={patientName || 'Unknown patient'}
                    />
                    <SummaryItem
                        label="MRN"
                        value={visit.patient?.patient_number || 'N/A'}
                    />
                    <SummaryItem
                        label="Gender"
                        value={visit.patient?.gender || 'N/A'}
                    />
                    <SummaryItem label="Age / DOB" value={patientAge} />
                    <SummaryItem
                        label="Phone"
                        value={visit.patient?.phone_number || 'N/A'}
                    />
                    <SummaryItem
                        label="Blood Group"
                        value={visit.patient?.blood_group || 'N/A'}
                    />
                    <SummaryItem
                        label="Visit Type"
                        value={visit.visit_type.replaceAll('_', ' ')}
                    />
                    <SummaryItem
                        label="Registered"
                        value={formatDateTime(
                            visit.registered_at ?? visit.created_at,
                        )}
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <CardTitle>Visit Summary</CardTitle>
                    </div>
                    {canCompleteVisit ? (
                        <VisitCompletionModal
                            visitId={visit.id}
                            visitNumber={visit.visit_number}
                            completionCheck={completionCheck}
                            trigger={<Button size="sm">Complete Visit</Button>}
                        />
                    ) : null}
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <SummaryItem
                            label="Visit Number"
                            value={visit.visit_number}
                        />
                        <SummaryItem
                            label="Registered By"
                            value={registeredBy}
                        />
                        <SummaryItem
                            label="Started"
                            value={formatDateTime(visit.started_at)}
                        />
                        <SummaryItem
                            label="Completed"
                            value={formatDateTime(visit.completed_at)}
                        />
                        <SummaryItem
                            label="Billing Type"
                            value={visit.payer?.billing_type ?? 'cash'}
                        />
                        <SummaryItem
                            label="Billing Status"
                            value={billingStatus}
                        />
                        <SummaryItem label="Insurer" value={insurer} />
                        <SummaryItem label="Package" value={packageName} />
                        <SummaryItem
                            label="Gross Charges"
                            value={formatMoney(visit.billing?.gross_amount)}
                        />
                        <SummaryItem
                            label="Paid Amount"
                            value={formatMoney(visit.billing?.paid_amount)}
                        />
                        <SummaryItem
                            label="Balance"
                            value={formatMoney(
                                visit.billing?.balance_amount,
                            )}
                        />
                    </div>

                    {(completionCheck?.has_pending_services ||
                        completionCheck?.has_unpaid_balance ||
                        completionCheck?.blocking_reasons?.length) && (
                        <div className="space-y-3 border-t pt-6">
                            {completionCheck?.has_pending_services ? (
                                <div className="rounded-lg border bg-muted/20 p-3 text-sm">
                                    Pending services:{' '}
                                    {completionCheck.pending_services_count}
                                </div>
                            ) : null}
                            {completionCheck?.has_unpaid_balance ? (
                                <div className="rounded-lg border bg-muted/20 p-3 text-sm">
                                    Unpaid balance:{' '}
                                    {formatMoney(
                                        completionCheck.unpaid_balance,
                                    )}
                                </div>
                            ) : null}
                            {completionCheck?.blocking_reasons?.length ? (
                                <div className="space-y-2">
                                    {completionCheck.blocking_reasons.map(
                                        (reason) => (
                                            <div
                                                key={reason}
                                                className="rounded-lg border bg-muted/20 p-3 text-sm"
                                            >
                                                {reason}
                                            </div>
                                        ),
                                    )}
                                </div>
                            ) : null}
                        </div>
                    )}

                    <div className="space-y-4 border-t pt-6">
                        <div className="flex items-center gap-2">
                            <CalendarClock className="h-4 w-4 text-muted-foreground" />
                            <h3 className="font-medium">Visit Timeline</h3>
                        </div>
                        <div className="space-y-4 border-l pl-4">
                            {timeline.map((entry) => (
                                <div key={entry.label} className="relative pl-3">
                                    <span className="absolute -left-[1.1875rem] top-1.5 h-2.5 w-2.5 rounded-full bg-muted-foreground/40" />
                                    <p className="font-medium">{entry.label}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {entry.value}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
