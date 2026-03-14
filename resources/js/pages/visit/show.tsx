import InputError from '@/components/input-error';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Consultation, type TriageRecord, type VitalSign, type VisitShowPageProps } from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    CalendarClock,
    CreditCard,
    HeartPulse,
    NotebookPen,
    Stethoscope,
} from 'lucide-react';
import { useState } from 'react';

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

function vitalSummaryItems(vital: VitalSign | undefined): { label: string; value: string }[] {
    if (!vital) {
        return [];
    }

    return [
        {
            label: 'Temperature',
            value: vital.temperature === null ? 'N/A' : `${vital.temperature} ${vital.temperature_unit === 'celsius' ? 'C' : 'F'}`,
        },
        { label: 'Pulse', value: measurement(vital.pulse_rate, 'bpm') },
        { label: 'Respiratory Rate', value: measurement(vital.respiratory_rate, '/min') },
        {
            label: 'Blood Pressure',
            value:
                vital.systolic_bp === null || vital.diastolic_bp === null
                    ? 'N/A'
                    : `${vital.systolic_bp}/${vital.diastolic_bp} mmHg`,
        },
        { label: 'SpO2', value: measurement(vital.oxygen_saturation, '%') },
        { label: 'Pain Score', value: vital.pain_score === null ? 'N/A' : `${vital.pain_score}/10` },
        { label: 'Weight', value: measurement(vital.weight_kg, 'kg') },
        { label: 'BMI', value: vital.bmi === null ? 'N/A' : `${vital.bmi}` },
    ];
}

export default function VisitShow({
    visit,
    availableTransitions,
    triageGrades,
    attendanceTypes,
    consciousLevels,
    mobilityStatuses,
    clinics,
    temperatureUnits,
    bloodGlucoseUnits,
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

    const [triageGrade, setTriageGrade] = useState(
        triageGrades.find((option) => option.value === 'green')?.value ?? triageGrades[0]?.value ?? '',
    );
    const [attendanceType, setAttendanceType] = useState(
        attendanceTypes.find((option) => option.value === 'new')?.value ?? attendanceTypes[0]?.value ?? '',
    );
    const [consciousLevel, setConsciousLevel] = useState(
        consciousLevels.find((option) => option.value === 'alert')?.value ?? consciousLevels[0]?.value ?? '',
    );
    const [mobilityStatus, setMobilityStatus] = useState(
        mobilityStatuses.find((option) => option.value === 'independent')?.value ?? mobilityStatuses[0]?.value ?? '',
    );
    const [assignedClinicId, setAssignedClinicId] = useState(visit.clinic?.id ?? 'none');
    const [temperatureUnit, setTemperatureUnit] = useState('celsius');
    const [bloodGlucoseUnit, setBloodGlucoseUnit] = useState('mg_dl');

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
                                <h1 className="text-2xl font-semibold">Visit {visit.visit_number}</h1>
                                <p className="text-sm text-muted-foreground">
                                    {visit.visit_type.replaceAll('_', ' ')} for {patientName || 'Unknown patient'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(visit.status)}`}>
                                {visit.status.replaceAll('_', ' ')}
                            </span>
                            <span>Branch: {visit.branch?.name || 'Not assigned'}</span>
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
                        {triage ? (
                            <Button asChild>
                                <Link href={`/doctors/consultations/${visit.id}`}>
                                    <NotebookPen className="mr-2 h-4 w-4" />
                                    Open Consultation
                                </Link>
                            </Button>
                        ) : null}
                        <Button variant="outline" asChild>
                            <Link href={`/patients/${visit.patient?.id}`}>Open Patient</Link>
                        </Button>
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
                                <div>
                                    <p className="text-sm text-muted-foreground">Address</p>
                                    <p className="font-medium">
                                        {visit.patient?.address
                                            ? `${visit.patient.address.city}${visit.patient.address.district ? `, ${visit.patient.address.district}` : ''}`
                                            : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Country</p>
                                    <p className="font-medium">{visit.patient?.country?.country_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Next of Kin</p>
                                    <p className="font-medium">
                                        {visit.patient?.next_of_kin_name || 'N/A'}
                                        {visit.patient?.next_of_kin_phone ? ` � ${visit.patient.next_of_kin_phone}` : ''}
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

                        {!triage ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <NotebookPen className="h-5 w-5" />
                                        Record Triage
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form method="post" action={`/visits/${visit.id}/triage`}>
                                        {({ processing, errors }) => (
                                            <div className="space-y-4">
                                                <input type="hidden" name="triage_grade" value={triageGrade} />
                                                <input type="hidden" name="attendance_type" value={attendanceType} />
                                                <input type="hidden" name="conscious_level" value={consciousLevel} />
                                                <input type="hidden" name="mobility_status" value={mobilityStatus} />
                                                <input type="hidden" name="assigned_clinic_id" value={assignedClinicId === 'none' ? '' : assignedClinicId} />

                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label>Triage Grade</Label>
                                                        <Select value={triageGrade} onValueChange={setTriageGrade}>
                                                            <SelectTrigger><SelectValue placeholder="Select triage grade" /></SelectTrigger>
                                                            <SelectContent>
                                                                {triageGrades.map((option) => (
                                                                    <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError message={errors.triage_grade} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label>Attendance Type</Label>
                                                        <Select value={attendanceType} onValueChange={setAttendanceType}>
                                                            <SelectTrigger><SelectValue placeholder="Select attendance type" /></SelectTrigger>
                                                            <SelectContent>
                                                                {attendanceTypes.map((option) => (
                                                                    <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError message={errors.attendance_type} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label>Conscious Level</Label>
                                                        <Select value={consciousLevel} onValueChange={setConsciousLevel}>
                                                            <SelectTrigger><SelectValue placeholder="Select conscious level" /></SelectTrigger>
                                                            <SelectContent>
                                                                {consciousLevels.map((option) => (
                                                                    <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError message={errors.conscious_level} />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label>Mobility Status</Label>
                                                        <Select value={mobilityStatus} onValueChange={setMobilityStatus}>
                                                            <SelectTrigger><SelectValue placeholder="Select mobility status" /></SelectTrigger>
                                                            <SelectContent>
                                                                {mobilityStatuses.map((option) => (
                                                                    <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError message={errors.mobility_status} />
                                                    </div>
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="news_score">NEWS Score</Label>
                                                        <Input id="news_score" name="news_score" type="number" min={0} max={20} />
                                                        <InputError message={errors.news_score} />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="pews_score">PEWS Score</Label>
                                                        <Input id="pews_score" name="pews_score" type="number" min={0} max={20} />
                                                        <InputError message={errors.pews_score} />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>Assigned Clinic</Label>
                                                        <Select value={assignedClinicId} onValueChange={setAssignedClinicId}>
                                                            <SelectTrigger><SelectValue placeholder="Select clinic" /></SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="none">Keep current clinic</SelectItem>
                                                                {clinics.map((clinic) => (
                                                                    <SelectItem key={clinic.id} value={clinic.id}>{clinic.name}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError message={errors.assigned_clinic_id} />
                                                    </div>
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="chief_complaint">Chief Complaint</Label>
                                                    <Textarea id="chief_complaint" name="chief_complaint" rows={3} />
                                                    <InputError message={errors.chief_complaint} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="history_of_presenting_illness">History of Presenting Illness</Label>
                                                    <Textarea id="history_of_presenting_illness" name="history_of_presenting_illness" rows={4} />
                                                    <InputError message={errors.history_of_presenting_illness} />
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="referred_by">Referred By</Label>
                                                        <Input id="referred_by" name="referred_by" />
                                                        <InputError message={errors.referred_by} />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="poisoning_agent">Poisoning Agent</Label>
                                                        <Input id="poisoning_agent" name="poisoning_agent" />
                                                        <InputError message={errors.poisoning_agent} />
                                                    </div>
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="nurse_notes">Nurse Notes</Label>
                                                    <Textarea id="nurse_notes" name="nurse_notes" rows={4} />
                                                    <InputError message={errors.nurse_notes} />
                                                </div>

                                                <div className="grid gap-3 md:grid-cols-2">
                                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="requires_priority" value="1" className="h-4 w-4" />Requires priority handling</label>
                                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="is_pediatric" value="1" className="h-4 w-4" />Pediatric case</label>
                                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="poisoning_case" value="1" className="h-4 w-4" />Poisoning case</label>
                                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="snake_bite_case" value="1" className="h-4 w-4" />Snake bite case</label>
                                                </div>

                                                <div className="flex justify-end">
                                                    <Button type="submit" disabled={processing}>Save Triage</Button>
                                                </div>
                                            </div>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <NotebookPen className="h-5 w-5" />
                                        Triage Summary
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}>
                                            {findLabel(triageGrades, triage.triage_grade)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">Recorded {formatDateTime(triage.triage_datetime)}</span>
                                        <span className="text-sm text-muted-foreground">By {triage.nurse ? `${triage.nurse.first_name} ${triage.nurse.last_name}` : 'Unknown nurse'}</span>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                        <div><p className="text-sm text-muted-foreground">Attendance</p><p className="font-medium">{findLabel(attendanceTypes, triage.attendance_type)}</p></div>
                                        <div><p className="text-sm text-muted-foreground">Conscious Level</p><p className="font-medium">{findLabel(consciousLevels, triage.conscious_level)}</p></div>
                                        <div><p className="text-sm text-muted-foreground">Mobility</p><p className="font-medium">{findLabel(mobilityStatuses, triage.mobility_status)}</p></div>
                                        <div><p className="text-sm text-muted-foreground">Assigned Clinic</p><p className="font-medium">{triage.assignedClinic?.name || triage.assigned_clinic?.name || visit.clinic?.name || 'Not assigned'}</p></div>
                                        <div><p className="text-sm text-muted-foreground">NEWS / PEWS</p><p className="font-medium">{triage.news_score ?? 'N/A'} / {triage.pews_score ?? 'N/A'}</p></div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Special Flags</p>
                                            <p className="font-medium">{[
                                                triage.requires_priority ? 'Priority' : null,
                                                triage.is_pediatric ? 'Pediatric' : null,
                                                triage.poisoning_case ? 'Poisoning' : null,
                                                triage.snake_bite_case ? 'Snake bite' : null,
                                            ].filter(Boolean).join(', ') || 'None'}</p>
                                        </div>
                                    </div>
                                    <div><p className="text-sm text-muted-foreground">Chief Complaint</p><p className="font-medium">{triage.chief_complaint}</p></div>
                                    {triage.history_of_presenting_illness ? (<div><p className="text-sm text-muted-foreground">History of Presenting Illness</p><p className="text-sm">{triage.history_of_presenting_illness}</p></div>) : null}
                                    {triage.nurse_notes ? (<div><p className="text-sm text-muted-foreground">Nurse Notes</p><p className="text-sm">{triage.nurse_notes}</p></div>) : null}
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <HeartPulse className="h-5 w-5" />
                                    Vitals
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {!triage ? (
                                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                        Record triage first to unlock vital signs for this visit.
                                    </div>
                                ) : (
                                    <>
                                        {latestVital ? (
                                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                {vitalSummaryItems(latestVital).map((item) => (
                                                    <div key={item.label} className="rounded-lg border p-3">
                                                        <p className="text-sm text-muted-foreground">{item.label}</p>
                                                        <p className="font-medium">{item.value}</p>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="text-sm text-muted-foreground">No vitals recorded yet for this visit.</p>
                                        )}

                                        <Form method="post" action={`/visits/${visit.id}/vitals`}>
                                            {({ processing, errors }) => (
                                                <div className="space-y-4 rounded-lg border p-4">
                                                    <input type="hidden" name="temperature_unit" value={temperatureUnit} />
                                                    <input type="hidden" name="blood_glucose_unit" value={bloodGlucoseUnit} />

                                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                        <div className="grid gap-2"><Label htmlFor="temperature">Temperature</Label><Input id="temperature" name="temperature" type="number" step="0.1" /><InputError message={errors.temperature} /></div>
                                                        <div className="grid gap-2"><Label>Temperature Unit</Label><Select value={temperatureUnit} onValueChange={setTemperatureUnit}><SelectTrigger><SelectValue placeholder="Select unit" /></SelectTrigger><SelectContent>{temperatureUnits.map((option) => (<SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>))}</SelectContent></Select><InputError message={errors.temperature_unit} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="pulse_rate">Pulse Rate</Label><Input id="pulse_rate" name="pulse_rate" type="number" /><InputError message={errors.pulse_rate} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="respiratory_rate">Respiratory Rate</Label><Input id="respiratory_rate" name="respiratory_rate" type="number" /><InputError message={errors.respiratory_rate} /></div>
                                                    </div>

                                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                        <div className="grid gap-2"><Label htmlFor="systolic_bp">Systolic BP</Label><Input id="systolic_bp" name="systolic_bp" type="number" /><InputError message={errors.systolic_bp} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="diastolic_bp">Diastolic BP</Label><Input id="diastolic_bp" name="diastolic_bp" type="number" /><InputError message={errors.diastolic_bp} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="oxygen_saturation">SpO2</Label><Input id="oxygen_saturation" name="oxygen_saturation" type="number" step="0.01" /><InputError message={errors.oxygen_saturation} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="pain_score">Pain Score</Label><Input id="pain_score" name="pain_score" type="number" min={0} max={10} /><InputError message={errors.pain_score} /></div>
                                                    </div>

                                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                        <div className="grid gap-2"><Label htmlFor="oxygen_delivery_method">Oxygen Method</Label><Input id="oxygen_delivery_method" name="oxygen_delivery_method" /><InputError message={errors.oxygen_delivery_method} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="oxygen_flow_rate">Oxygen Flow Rate</Label><Input id="oxygen_flow_rate" name="oxygen_flow_rate" type="number" step="0.1" /><InputError message={errors.oxygen_flow_rate} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="blood_glucose">Blood Glucose</Label><Input id="blood_glucose" name="blood_glucose" type="number" step="0.01" /><InputError message={errors.blood_glucose} /></div>
                                                        <div className="grid gap-2"><Label>Glucose Unit</Label><Select value={bloodGlucoseUnit} onValueChange={setBloodGlucoseUnit}><SelectTrigger><SelectValue placeholder="Select unit" /></SelectTrigger><SelectContent>{bloodGlucoseUnits.map((option) => (<SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>))}</SelectContent></Select><InputError message={errors.blood_glucose_unit} /></div>
                                                    </div>

                                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                        <div className="grid gap-2"><Label htmlFor="height_cm">Height (cm)</Label><Input id="height_cm" name="height_cm" type="number" step="0.01" /><InputError message={errors.height_cm} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="weight_kg">Weight (kg)</Label><Input id="weight_kg" name="weight_kg" type="number" step="0.01" /><InputError message={errors.weight_kg} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="head_circumference_cm">Head Circumference</Label><Input id="head_circumference_cm" name="head_circumference_cm" type="number" step="0.01" /><InputError message={errors.head_circumference_cm} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="muac_cm">MUAC</Label><Input id="muac_cm" name="muac_cm" type="number" step="0.01" /><InputError message={errors.muac_cm} /></div>
                                                    </div>

                                                    <div className="grid gap-4 md:grid-cols-2">
                                                        <div className="grid gap-2"><Label htmlFor="chest_circumference_cm">Chest Circumference</Label><Input id="chest_circumference_cm" name="chest_circumference_cm" type="number" step="0.01" /><InputError message={errors.chest_circumference_cm} /></div>
                                                        <div className="grid gap-2"><Label htmlFor="capillary_refill">Capillary Refill</Label><Input id="capillary_refill" name="capillary_refill" /><InputError message={errors.capillary_refill} /></div>
                                                    </div>

                                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="on_supplemental_oxygen" value="1" className="h-4 w-4" />Patient is on supplemental oxygen</label>

                                                    <div className="flex justify-end"><Button type="submit" disabled={processing}>Save Vitals</Button></div>
                                                </div>
                                            )}
                                        </Form>

                                        <div className="space-y-3">
                                            <div>
                                                <h3 className="font-medium">Vitals History</h3>
                                                <p className="text-sm text-muted-foreground">Most recent measurements for this visit.</p>
                                            </div>
                                            {vitalSigns.length > 0 ? (
                                                vitalSigns.map((vital) => (
                                                    <div key={vital.id} className="rounded-lg border p-4">
                                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                            <div>
                                                                <p className="font-medium">{formatDateTime(vital.recorded_at)}</p>
                                                                <p className="text-sm text-muted-foreground">Recorded by {vital.recordedBy ? `${vital.recordedBy.first_name} ${vital.recordedBy.last_name}` : 'Unknown staff'}</p>
                                                            </div>
                                                            <div className="grid gap-2 text-sm text-muted-foreground sm:grid-cols-2 lg:grid-cols-4">
                                                                {vitalSummaryItems(vital).map((item) => (
                                                                    <div key={item.label}><p>{item.label}</p><p className="font-medium text-foreground">{item.value}</p></div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))
                                            ) : null}
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <NotebookPen className="h-5 w-5" />
                                    Doctor Workspace
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {!triage ? (
                                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                        Record triage first to unlock the doctor consultation workspace for this visit.
                                    </div>
                                ) : (
                                    <div className="space-y-4 rounded-lg border p-4">
                                        <p className="text-sm text-muted-foreground">
                                            Consultation has been moved into the dedicated Doctors workspace so the visit page can stay focused on registration, triage, vitals, and visit status.
                                        </p>
                                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <p className="text-sm text-muted-foreground">Consultation Status</p>
                                                <p className="font-medium">{consultation ? 'Draft in progress' : 'Ready to start'}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Chief Complaint</p>
                                                <p className="font-medium">{consultation?.chief_complaint ?? triage.chief_complaint}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Primary Diagnosis</p>
                                                <p className="font-medium">{consultation?.primary_diagnosis || 'Not documented yet'}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Workspace</p>
                                                <p className="font-medium">Overview, labs, prescriptions, imaging, services</p>
                                            </div>
                                        </div>
                                        <div className="flex justify-end">
                                            <Button asChild>
                                                <Link href={`/doctors/consultations/${visit.id}`}>
                                                    <NotebookPen className="mr-2 h-4 w-4" />
                                                    {consultation ? 'Continue in Consultation Workspace' : 'Start in Consultation Workspace'}
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Payer Snapshot</CardTitle></CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"><CreditCard className="h-5 w-5" /></div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Billing Type</p>
                                        <p className="font-medium capitalize">{visit.payer?.billing_type ?? 'cash'}</p>
                                    </div>
                                </div>
                                <div><p className="text-sm text-muted-foreground">Insurer</p><p className="font-medium">{insurer || 'Not applicable'}</p></div>
                                <div><p className="text-sm text-muted-foreground">Package</p><p className="font-medium">{packageName || 'Not applicable'}</p></div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Quick Actions</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                {availableTransitions.length > 0 ? (
                                    availableTransitions.map((transition) => (
                                        <Form key={transition.value} method="patch" action={`/visits/${visit.id}/status`}>
                                            <input type="hidden" name="status" value={transition.value} />
                                            <Button type="submit" className="w-full justify-start" variant={transition.value === 'cancelled' ? 'destructive' : 'default'}>
                                                <Activity className="mr-2 h-4 w-4" />
                                                {transition.label}
                                            </Button>
                                        </Form>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">No further status actions are available for this visit.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Triage Snapshot</CardTitle></CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {triage ? (
                                    <>
                                        <div><p className="text-muted-foreground">Grade</p><p className="font-medium">{findLabel(triageGrades, triage.triage_grade)}</p></div>
                                        <div><p className="text-muted-foreground">Complaint</p><p className="font-medium">{triage.chief_complaint}</p></div>
                                        <div><p className="text-muted-foreground">Last Vital Capture</p><p className="font-medium">{latestVital ? formatDateTime(latestVital.recorded_at) : 'Not yet captured'}</p></div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">Triage has not been recorded yet. Saving triage will automatically move the visit into progress.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Consultation Snapshot</CardTitle></CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {consultation ? (
                                    <>
                                        <div><p className="text-muted-foreground">Started</p><p className="font-medium">{formatDateTime(consultation.started_at)}</p></div>
                                        <div><p className="text-muted-foreground">Clinician</p><p className="font-medium">{consultation.doctor ? `${consultation.doctor.first_name} ${consultation.doctor.last_name}` : 'Assigned clinician'}</p></div>
                                        <div><p className="text-muted-foreground">Primary Diagnosis</p><p className="font-medium">{consultation.primary_diagnosis || 'Not documented yet'}</p></div>
                                        <div><p className="text-muted-foreground">Plan</p><p className="font-medium">{consultation.plan || 'No plan documented yet'}</p></div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">Consultation has not been started yet. Use the dedicated Doctors workspace to start the note and manage downstream clinical work.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Next Build Areas</CardTitle></CardHeader>
                            <CardContent className="space-y-2 text-sm text-muted-foreground">
                                <div className="rounded-md border px-3 py-2"><Stethoscope className="mr-2 inline h-4 w-4" />Orders, procedures, and service requests</div>
                                <div className="rounded-md border px-3 py-2"><CreditCard className="mr-2 inline h-4 w-4" />Charges, billing, and payments</div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
