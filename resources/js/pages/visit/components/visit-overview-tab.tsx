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
    statusClasses,
} from './visit-show-utils';

type VisitOverviewTabProps = {
    visit: {
        id: string;
        visit_number: string;
        status: string;
        visit_type: string;
        is_emergency: boolean;
        registered_at: string | null;
        created_at: string;
        started_at?: string | null;
        completed_at: string | null;
        clinic?: { name?: string | null } | null;
        doctor?:
            | { first_name?: string | null; last_name?: string | null }
            | null;
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

    const insurer =
        visit.payer?.insuranceCompany?.name ??
        visit.payer?.insurance_company?.name;
    const packageName =
        visit.payer?.insurancePackage?.name ??
        visit.payer?.insurance_package?.name;
    const canCompleteVisit =
        canUpdateVisit &&
        ['in_progress', 'awaiting_payment'].includes(visit.status);

    return (
        <div className="space-y-6">
            <Card className="overflow-hidden">
                <CardHeader className="border-b bg-muted/20">
                    <CardTitle>Visit Overview</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 p-6 xl:grid-cols-[1.45fr_0.95fr]">
                    <div className="space-y-6">
                        <div className="flex flex-wrap gap-2">
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(visit.status)}`}
                            >
                                {visit.status.replaceAll('_', ' ')}
                            </span>
                            <span className="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">
                                {visit.is_emergency
                                    ? 'Emergency Visit'
                                    : 'Routine Visit'}
                            </span>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Visit Number
                                </p>
                                <p className="font-medium">
                                    {visit.visit_number}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Visit Type
                                </p>
                                <p className="font-medium">
                                    {visit.visit_type.replaceAll('_', ' ')}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Registered At
                                </p>
                                <p className="font-medium">
                                    {formatDateTime(
                                        visit.registered_at ?? visit.created_at,
                                    )}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    In Progress Since
                                </p>
                                <p className="font-medium">
                                    {formatDateTime(visit.started_at)}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Completed At
                                </p>
                                <p className="font-medium">
                                    {formatDateTime(visit.completed_at)}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Registered By
                                </p>
                                <p className="font-medium">
                                    {visit.registeredBy?.name ||
                                        visit.registered_by?.name ||
                                        'Unknown'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Clinic
                                </p>
                                <p className="font-medium">
                                    {visit.clinic?.name || 'Not assigned'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Doctor
                                </p>
                                <p className="font-medium">
                                    {visit.doctor?.first_name &&
                                    visit.doctor?.last_name
                                        ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                        : 'Not assigned'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Billing Status
                                </p>
                                <p className="font-medium capitalize">
                                    {visit.billing?.status?.replaceAll(
                                        '_',
                                        ' ',
                                    ) || 'Not billed'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-xl border bg-muted/20 p-4">
                            <div className="mb-3">
                                <h3 className="font-semibold">
                                    Payer Snapshot
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Billing arrangement and current financial
                                    position for this visit.
                                </p>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Billing Type
                                    </p>
                                    <p className="font-medium capitalize">
                                        {visit.payer?.billing_type ?? 'cash'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Insurer
                                    </p>
                                    <p className="font-medium">
                                        {insurer || 'Not applicable'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Package
                                    </p>
                                    <p className="font-medium">
                                        {packageName || 'Not applicable'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Gross Charges
                                    </p>
                                    <p className="font-medium">
                                        {formatMoney(
                                            visit.billing?.gross_amount,
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Paid Amount
                                    </p>
                                    <p className="font-medium">
                                        {formatMoney(
                                            visit.billing?.paid_amount,
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Balance
                                    </p>
                                    <p className="font-medium">
                                        {formatMoney(
                                            visit.billing?.balance_amount,
                                        )}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border bg-muted/20 p-4">
                            <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 className="font-semibold">
                                        Workflow Snapshot
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        Completion readiness and remaining work
                                        for this encounter.
                                    </p>
                                </div>
                                {canCompleteVisit ? (
                                    <VisitCompletionModal
                                        visitId={visit.id}
                                        visitNumber={visit.visit_number}
                                        completionCheck={completionCheck}
                                        trigger={
                                            <Button size="sm">
                                                Complete Visit
                                            </Button>
                                        }
                                    />
                                ) : null}
                            </div>
                            <div className="space-y-3 text-sm">
                                {completionCheck?.has_pending_services ? (
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                                        Pending services:{' '}
                                        {completionCheck.pending_services_count}
                                    </div>
                                ) : null}
                                {completionCheck?.has_unpaid_balance ? (
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
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
                                                    className="rounded-lg border border-red-200 bg-red-50 p-3 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100"
                                                >
                                                    {reason}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : null}
                                {completionCheck &&
                                !completionCheck.has_pending_services &&
                                !completionCheck.has_unpaid_balance &&
                                completionCheck.blocking_reasons.length ===
                                    0 ? (
                                    <p className="text-muted-foreground">
                                        This visit is in a good operational
                                        state. Complete it whenever the clinical
                                        work is fully done.
                                    </p>
                                ) : null}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Patient Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <p className="text-sm text-muted-foreground">Patient</p>
                        <p className="font-medium">
                            {patientName || 'Unknown'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">MRN</p>
                        <p className="font-medium">
                            {visit.patient?.patient_number || 'N/A'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Gender</p>
                        <p className="font-medium capitalize">
                            {visit.patient?.gender || 'N/A'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Age / Date of Birth
                        </p>
                        <p className="font-medium">
                            {visit.patient?.date_of_birth
                                ? `${formatAge(visit.patient.age, visit.patient.age_units)} (${formatDate(visit.patient.date_of_birth)})`
                                : formatAge(
                                      visit.patient?.age,
                                      visit.patient?.age_units,
                                  )}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Phone</p>
                        <p className="font-medium">
                            {visit.patient?.phone_number || 'N/A'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Blood Group
                        </p>
                        <p className="font-medium">
                            {visit.patient?.blood_group || 'N/A'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Visit Timeline</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {timeline.map((entry) => (
                        <div
                            key={entry.label}
                            className="flex items-start gap-3 rounded-lg border p-3"
                        >
                            <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                <CalendarClock className="h-4 w-4" />
                            </div>
                            <div>
                                <p className="font-medium">{entry.label}</p>
                                <p className="text-sm text-muted-foreground">
                                    {entry.value}
                                </p>
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </div>
    );
}
