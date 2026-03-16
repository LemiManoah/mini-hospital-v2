import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type Consultation,
    type TriageRecord,
    type VitalSign,
    type VisitShowPageProps,
} from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    CalendarClock,
    CreditCard,
    HeartPulse,
    NotebookPen,
    Stethoscope,
    UserRound,
} from 'lucide-react';

function formatDate(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClasses(status: string): string {
    return (
        {
            registered:
                'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
            in_progress:
                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
            awaiting_payment:
                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            completed:
                'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
            cancelled:
                'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
        }[status] ?? 'bg-zinc-100 text-zinc-800'
    );
}

function triageGradeClasses(grade: string): string {
    return (
        {
            red: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            yellow: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            green: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            black: 'bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900',
        }[grade] ?? 'bg-zinc-100 text-zinc-800'
    );
}

function findLabel(
    options: { value: string; label: string }[],
    value: string | null | undefined,
): string {
    return options.find((option) => option.value === value)?.label ?? 'N/A';
}

function measurement(value: number | null | undefined, suffix: string): string {
    return value === null || value === undefined ? 'N/A' : `${value} ${suffix}`;
}

function vitalSummaryItems(vital: VitalSign | undefined) {
    if (!vital) return [];

    return [
        {
            label: 'Temperature',
            value:
                vital.temperature === null
                    ? 'N/A'
                    : `${vital.temperature} ${vital.temperature_unit === 'celsius' ? 'C' : 'F'}`,
        },
        { label: 'Pulse', value: measurement(vital.pulse_rate, 'bpm') },
        { label: 'Respiratory', value: measurement(vital.respiratory_rate, '/min') },
        {
            label: 'Blood Pressure',
            value:
                vital.systolic_bp === null || vital.diastolic_bp === null
                    ? 'N/A'
                    : `${vital.systolic_bp}/${vital.diastolic_bp} mmHg`,
        },
        { label: 'SpO2', value: measurement(vital.oxygen_saturation, '%') },
        { label: 'Pain', value: vital.pain_score === null ? 'N/A' : `${vital.pain_score}/10` },
    ];
}

export default function VisitShow({
    visit,
    availableTransitions,
    triageGrades,
}: VisitShowPageProps) {
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
    const triage: TriageRecord | null | undefined = visit.triage;
    const consultation: Consultation | null | undefined = visit.consultation;
    const vitalSigns = triage?.vitalSigns ?? triage?.vital_signs ?? [];
    const latestVital = vitalSigns[0];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Active Visits', href: '/visits' },
        { title: visit.visit_number, href: `/visits/${visit.id}` },
    ];

    const timeline = [
        {
            label: 'Registered',
            value: formatDateTime(visit.registered_at ?? visit.created_at),
        },
        { label: 'In Progress', value: formatDateTime(visit.started_at) },
        { label: 'Completed', value: formatDateTime(visit.completed_at) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Visit ${visit.visit_number}`} />

            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                <Stethoscope className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold">
                                    Visit {visit.visit_number}
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {visit.visit_type.replaceAll('_', ' ')} for{' '}
                                    {patientName || 'Unknown patient'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(visit.status)}`}
                            >
                                {visit.status.replaceAll('_', ' ')}
                            </span>
                            <span>Clinic: {visit.clinic?.name || 'Not assigned'}</span>
                            <span>
                                Doctor:{' '}
                                {visit.doctor
                                    ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                    : 'Not assigned'}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/visits">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Active Visits
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/patients/${visit.patient?.id}`}>
                                <UserRound className="mr-2 h-4 w-4" />
                                Patient Profile
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/triage/${visit.id}`}>
                                <HeartPulse className="mr-2 h-4 w-4" />
                                {triage ? 'Open Triage Page' : 'Start Triage'}
                            </Link>
                        </Button>
                        {triage ? (
                            <Button variant="outline" asChild>
                                <Link href={`/doctors/consultations/${visit.id}`}>
                                    <NotebookPen className="mr-2 h-4 w-4" />
                                    Open Consultation
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Visit Overview</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">Visit Number</p>
                                    <p className="font-medium">{visit.visit_number}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Visit Type</p>
                                    <p className="font-medium">{visit.visit_type.replaceAll('_', ' ')}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Emergency</p>
                                    <p className="font-medium">{visit.is_emergency ? 'Yes' : 'No'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Registered At</p>
                                    <p className="font-medium">{formatDateTime(visit.registered_at ?? visit.created_at)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Registered By</p>
                                    <p className="font-medium">{visit.registeredBy?.name || visit.registered_by?.name || 'Unknown'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Completed At</p>
                                    <p className="font-medium">{formatDateTime(visit.completed_at)}</p>
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
                                    <p className="font-medium">{patientName || 'Unknown'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">MRN</p>
                                    <p className="font-medium">{visit.patient?.patient_number || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Gender</p>
                                    <p className="font-medium capitalize">{visit.patient?.gender || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Date of Birth</p>
                                    <p className="font-medium">
                                        {visit.patient?.date_of_birth
                                            ? formatDate(visit.patient.date_of_birth)
                                            : visit.patient?.age
                                              ? `${visit.patient.age} ${visit.patient.age_units}`
                                              : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Phone</p>
                                    <p className="font-medium">{visit.patient?.phone_number || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Blood Group</p>
                                    <p className="font-medium">{visit.patient?.blood_group || 'N/A'}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Visit Timeline</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {timeline.map((entry) => (
                                    <div key={entry.label} className="flex items-start gap-3 rounded-lg border p-3">
                                        <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                            <CalendarClock className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="font-medium">{entry.label}</p>
                                            <p className="text-sm text-muted-foreground">{entry.value}</p>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Triage Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {!triage ? (
                                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                        Triage is now managed in the dedicated triage workspace for this visit.
                                    </div>
                                ) : (
                                    <>
                                        <div className="flex flex-wrap items-center gap-3">
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}
                                            >
                                                {findLabel(triageGrades, triage.triage_grade)}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                Recorded {formatDateTime(triage.triage_datetime)}
                                            </span>
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <p className="text-sm text-muted-foreground">Chief Complaint</p>
                                                <p className="font-medium">{triage.chief_complaint}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Nurse</p>
                                                <p className="font-medium">
                                                    {triage.nurse
                                                        ? `${triage.nurse.first_name} ${triage.nurse.last_name}`
                                                        : 'Unknown'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Assigned Clinic</p>
                                                <p className="font-medium">
                                                    {triage.assignedClinic?.name || triage.assigned_clinic?.name || 'Not assigned'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Latest Vitals</p>
                                                <p className="font-medium">
                                                    {latestVital ? formatDateTime(latestVital.recorded_at) : 'Not yet captured'}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="grid gap-3 rounded-lg border p-4">
                                            <div>
                                                <p className="text-sm text-muted-foreground">History of Presenting Illness</p>
                                                <p className="font-medium">
                                                    {triage.history_of_presenting_illness || 'Not documented'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Notes</p>
                                                <p className="font-medium">{triage.nurse_notes || 'Not documented'}</p>
                                            </div>
                                        </div>
                                        {latestVital ? (
                                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                {vitalSummaryItems(latestVital).map((item) => (
                                                    <div key={item.label} className="rounded-lg border p-3">
                                                        <p className="text-sm text-muted-foreground">{item.label}</p>
                                                        <p className="font-medium">{item.value}</p>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : null}
                                    </>
                                )}
                                <div className="flex justify-end">
                                    <Button asChild>
                                        <Link href={`/triage/${visit.id}`}>
                                            <HeartPulse className="mr-2 h-4 w-4" />
                                            {triage ? 'Continue in Triage Workspace' : 'Open Triage Workspace'}
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Consultation Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {consultation ? (
                                    <>
                                        <div>
                                            <p className="text-muted-foreground">Started</p>
                                            <p className="font-medium">{formatDateTime(consultation.started_at)}</p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">Clinician</p>
                                            <p className="font-medium">
                                                {consultation.doctor
                                                    ? `${consultation.doctor.first_name} ${consultation.doctor.last_name}`
                                                    : 'Assigned clinician'}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">Primary Diagnosis</p>
                                            <p className="font-medium">{consultation.primary_diagnosis || 'Not documented yet'}</p>
                                        </div>
                                        <div className="flex justify-end">
                                            <Button variant="outline" asChild>
                                                <Link href={`/doctors/consultations/${visit.id}`}>
                                                    <NotebookPen className="mr-2 h-4 w-4" />
                                                    Continue Consultation
                                                </Link>
                                            </Button>
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        Consultation has not been started yet. Use the dedicated doctors workspace after triage.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Payer Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                        <CreditCard className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Billing Type</p>
                                        <p className="font-medium capitalize">{visit.payer?.billing_type ?? 'cash'}</p>
                                    </div>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Insurer</p>
                                    <p className="font-medium">{insurer || 'Not applicable'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Package</p>
                                    <p className="font-medium">{packageName || 'Not applicable'}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Quick Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {availableTransitions.length > 0 ? (
                                    availableTransitions.map((transition) => (
                                        <Form key={transition.value} method="patch" action={`/visits/${visit.id}/status`}>
                                            <input type="hidden" name="status" value={transition.value} />
                                            <Button
                                                type="submit"
                                                className="w-full justify-start"
                                                variant={transition.value === 'cancelled' ? 'destructive' : 'default'}
                                            >
                                                <Activity className="mr-2 h-4 w-4" />
                                                {transition.label}
                                            </Button>
                                        </Form>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No further status actions are available for this visit.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
