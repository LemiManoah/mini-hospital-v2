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
import {
    type Consultation,
    type TriageRecord,
    type TriageShowPageProps,
    type VitalSign,
} from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    HeartPulse,
    NotebookPen,
    Stethoscope,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import TriageVitalForm from './triage-vital-form';

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
        {
            label: 'Respiratory',
            value: measurement(vital.respiratory_rate, '/min'),
        },
        {
            label: 'Blood Pressure',
            value:
                vital.systolic_bp === null || vital.diastolic_bp === null
                    ? 'N/A'
                    : `${vital.systolic_bp}/${vital.diastolic_bp} mmHg`,
        },
        { label: 'SpO2', value: measurement(vital.oxygen_saturation, '%') },
        {
            label: 'Pain',
            value: vital.pain_score === null ? 'N/A' : `${vital.pain_score}/10`,
        },
        { label: 'Weight', value: measurement(vital.weight_kg, 'kg') },
        { label: 'BMI', value: vital.bmi === null ? 'N/A' : `${vital.bmi}` },
    ];
}

function TriageSummary({
    triage,
    triageGrades,
    attendanceTypes,
    consciousLevels,
    mobilityStatuses,
}: {
    triage: TriageRecord;
    triageGrades: { value: string; label: string }[];
    attendanceTypes: { value: string; label: string }[];
    consciousLevels: { value: string; label: string }[];
    mobilityStatuses: { value: string; label: string }[];
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Triage Summary</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
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
                        <p className="text-sm text-muted-foreground">
                            Attendance
                        </p>
                        <p className="font-medium">
                            {findLabel(attendanceTypes, triage.attendance_type)}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Conscious
                        </p>
                        <p className="font-medium">
                            {findLabel(consciousLevels, triage.conscious_level)}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Mobility
                        </p>
                        <p className="font-medium">
                            {findLabel(
                                mobilityStatuses,
                                triage.mobility_status,
                            )}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Clinic</p>
                        <p className="font-medium">
                            {triage.assignedClinic?.name ||
                                triage.assigned_clinic?.name ||
                                'Not assigned'}
                        </p>
                    </div>
                </div>
                <div className="grid gap-3 rounded-lg border p-4">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Chief Complaint
                        </p>
                        <p className="font-medium">{triage.chief_complaint}</p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            History of Presenting Illness
                        </p>
                        <p className="font-medium">
                            {triage.history_of_presenting_illness ||
                                'Not documented'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Notes</p>
                        <p className="font-medium">
                            {triage.nurse_notes || 'Not documented'}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function TriageShow({
    visit,
    triageGrades,
    attendanceTypes,
    consciousLevels,
    mobilityStatuses,
    clinics,
    temperatureUnits,
    bloodGlucoseUnits,
}: TriageShowPageProps) {
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');
    const triage: TriageRecord | null | undefined = visit.triage;
    const consultation: Consultation | null | undefined = visit.consultation;
    const vitalSigns = triage?.vitalSigns ?? triage?.vital_signs ?? [];
    const latestVital = vitalSigns[0];

    const [triageGrade, setTriageGrade] = useState(
        triageGrades.find((option) => option.value === 'green')?.value ??
            triageGrades[0]?.value ??
            '',
    );
    const [attendanceType, setAttendanceType] = useState(
        attendanceTypes.find((option) => option.value === 'new')?.value ??
            attendanceTypes[0]?.value ??
            '',
    );
    const [consciousLevel, setConsciousLevel] = useState(
        consciousLevels.find((option) => option.value === 'alert')?.value ??
            consciousLevels[0]?.value ??
            '',
    );
    const [mobilityStatus, setMobilityStatus] = useState(
        mobilityStatuses.find((option) => option.value === 'independent')
            ?.value ??
            mobilityStatuses[0]?.value ??
            '',
    );
    const [assignedClinicId, setAssignedClinicId] = useState(
        visit.clinic?.id ?? 'none',
    );
    const [temperatureUnit, setTemperatureUnit] = useState('celsius');
    const [bloodGlucoseUnit, setBloodGlucoseUnit] = useState('mg_dl');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Triage', href: '/triage' },
        { title: visit.visit_number, href: `/triage/${visit.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Triage ${visit.visit_number}`} />

            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                            <HeartPulse className="h-6 w-6" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Triage Workspace
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {visit.visit_number} for{' '}
                                {patientName || 'Unknown patient'}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/triage">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Queue
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/visits/${visit.id}`}>
                                <Stethoscope className="mr-2 h-4 w-4" />
                                Visit Page
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/patients/${visit.patient?.id}`}>
                                <UserRound className="mr-2 h-4 w-4" />
                                Patient Profile
                            </Link>
                        </Button>
                        {triage ? (
                            <Button asChild>
                                <Link
                                    href={`/doctors/consultations/${visit.id}`}
                                >
                                    <NotebookPen className="mr-2 h-4 w-4" />
                                    Open Consultation
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Visit Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Patient
                                    </p>
                                    <p className="font-medium">
                                        {patientName || 'Unknown patient'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        MRN
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.patient_number || 'N/A'}
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
                                        {visit.doctor
                                            ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                            : 'Not assigned'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {!triage ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Start Triage</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        method="post"
                                        action={`/visits/${visit.id}/triage`}
                                    >
                                        {({ processing, errors }) => (
                                            <div className="space-y-4">
                                                <input
                                                    type="hidden"
                                                    name="redirect_to"
                                                    value="triage"
                                                />
                                                <input
                                                    type="hidden"
                                                    name="triage_grade"
                                                    value={triageGrade}
                                                />
                                                <input
                                                    type="hidden"
                                                    name="attendance_type"
                                                    value={attendanceType}
                                                />
                                                <input
                                                    type="hidden"
                                                    name="conscious_level"
                                                    value={consciousLevel}
                                                />
                                                <input
                                                    type="hidden"
                                                    name="mobility_status"
                                                    value={mobilityStatus}
                                                />
                                                <input
                                                    type="hidden"
                                                    name="assigned_clinic_id"
                                                    value={
                                                        assignedClinicId ===
                                                        'none'
                                                            ? ''
                                                            : assignedClinicId
                                                    }
                                                />

                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Triage Grade
                                                        </Label>
                                                        <Select
                                                            value={triageGrade}
                                                            onValueChange={
                                                                setTriageGrade
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select triage grade" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {triageGrades.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                errors.triage_grade
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Attendance Type
                                                        </Label>
                                                        <Select
                                                            value={
                                                                attendanceType
                                                            }
                                                            onValueChange={
                                                                setAttendanceType
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select attendance type" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {attendanceTypes.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                errors.attendance_type
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Conscious Level
                                                        </Label>
                                                        <Select
                                                            value={
                                                                consciousLevel
                                                            }
                                                            onValueChange={
                                                                setConsciousLevel
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select conscious level" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {consciousLevels.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                errors.conscious_level
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Mobility Status
                                                        </Label>
                                                        <Select
                                                            value={
                                                                mobilityStatus
                                                            }
                                                            onValueChange={
                                                                setMobilityStatus
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select mobility status" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {mobilityStatuses.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                errors.mobility_status
                                                            }
                                                        />
                                                    </div>
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="news_score">
                                                            NEWS Score
                                                        </Label>
                                                        <Input
                                                            id="news_score"
                                                            name="news_score"
                                                            type="number"
                                                            min={0}
                                                            max={20}
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="pews_score">
                                                            PEWS Score
                                                        </Label>
                                                        <Input
                                                            id="pews_score"
                                                            name="pews_score"
                                                            type="number"
                                                            min={0}
                                                            max={20}
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Assigned Clinic
                                                        </Label>
                                                        <Select
                                                            value={
                                                                assignedClinicId
                                                            }
                                                            onValueChange={
                                                                setAssignedClinicId
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select clinic" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="none">
                                                                    No clinic
                                                                </SelectItem>
                                                                {clinics.map(
                                                                    (
                                                                        clinic,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                clinic.id
                                                                            }
                                                                            value={
                                                                                clinic.id
                                                                            }
                                                                        >
                                                                            {
                                                                                clinic.name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="chief_complaint">
                                                            Chief Complaint
                                                        </Label>
                                                        <Textarea
                                                            id="chief_complaint"
                                                            name="chief_complaint"
                                                            rows={4}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.chief_complaint
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="history_of_presenting_illness">
                                                            History of
                                                            Presenting Illness
                                                        </Label>
                                                        <Textarea
                                                            id="history_of_presenting_illness"
                                                            name="history_of_presenting_illness"
                                                            rows={4}
                                                        />
                                                    </div>
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <Input
                                                        id="poisoning_agent"
                                                        name="poisoning_agent"
                                                        placeholder="Poisoning agent"
                                                    />
                                                    <Input
                                                        id="referred_by"
                                                        name="referred_by"
                                                        placeholder="Referred by"
                                                    />
                                                    <Textarea
                                                        id="nurse_notes"
                                                        name="nurse_notes"
                                                        rows={3}
                                                        placeholder="Nurse notes"
                                                    />
                                                </div>

                                                <div className="grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                                                    <label className="flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="requires_priority"
                                                            value="1"
                                                        />
                                                        Requires priority
                                                    </label>
                                                    <label className="flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="is_pediatric"
                                                            value="1"
                                                        />
                                                        Pediatric patient
                                                    </label>
                                                    <label className="flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="poisoning_case"
                                                            value="1"
                                                        />
                                                        Poisoning case
                                                    </label>
                                                    <label className="flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="snake_bite_case"
                                                            value="1"
                                                        />
                                                        Snake bite case
                                                    </label>
                                                </div>

                                                <div className="flex justify-end">
                                                    <Button
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        {processing
                                                            ? 'Saving...'
                                                            : 'Save Triage'}
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        ) : (
                            <TriageSummary
                                triage={triage}
                                triageGrades={triageGrades}
                                attendanceTypes={attendanceTypes}
                                consciousLevels={consciousLevels}
                                mobilityStatuses={mobilityStatuses}
                            />
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Vitals</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {!triage ? (
                                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                        Save triage first to unlock vital sign
                                        capture.
                                    </div>
                                ) : (
                                    <>
                                        {latestVital ? (
                                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                {vitalSummaryItems(
                                                    latestVital,
                                                ).map((item) => (
                                                    <div
                                                        key={item.label}
                                                        className="rounded-lg border p-3"
                                                    >
                                                        <p className="text-sm text-muted-foreground">
                                                            {item.label}
                                                        </p>
                                                        <p className="font-medium">
                                                            {item.value}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : null}

                                        <TriageVitalForm
                                            visitId={visit.id}
                                            temperatureUnit={temperatureUnit}
                                            setTemperatureUnit={
                                                setTemperatureUnit
                                            }
                                            bloodGlucoseUnit={bloodGlucoseUnit}
                                            setBloodGlucoseUnit={
                                                setBloodGlucoseUnit
                                            }
                                            temperatureUnits={temperatureUnits}
                                            bloodGlucoseUnits={
                                                bloodGlucoseUnits
                                            }
                                        />

                                        {vitalSigns.length > 0 ? (
                                            <div className="space-y-3">
                                                <h3 className="font-medium">
                                                    Vitals History
                                                </h3>
                                                {vitalSigns.map((vital) => (
                                                    <div
                                                        key={vital.id}
                                                        className="rounded-lg border p-4"
                                                    >
                                                        <p className="font-medium">
                                                            {formatDateTime(
                                                                vital.recorded_at,
                                                            )}
                                                        </p>
                                                        <div className="mt-3 grid gap-2 text-sm text-muted-foreground sm:grid-cols-2 lg:grid-cols-4">
                                                            {vitalSummaryItems(
                                                                vital,
                                                            ).map((item) => (
                                                                <div
                                                                    key={
                                                                        item.label
                                                                    }
                                                                >
                                                                    <p>
                                                                        {
                                                                            item.label
                                                                        }
                                                                    </p>
                                                                    <p className="font-medium text-foreground">
                                                                        {
                                                                            item.value
                                                                        }
                                                                    </p>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : null}
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Workflow Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div>
                                    <p className="text-muted-foreground">
                                        Triage
                                    </p>
                                    <p className="font-medium">
                                        {triage ? 'Recorded' : 'Pending'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Latest Vitals
                                    </p>
                                    <p className="font-medium">
                                        {latestVital
                                            ? formatDateTime(
                                                  latestVital.recorded_at,
                                              )
                                            : 'Not yet captured'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Consultation
                                    </p>
                                    <p className="font-medium">
                                        {consultation
                                            ? consultation.completed_at
                                                ? 'Finalized'
                                                : 'In progress'
                                            : triage
                                              ? 'Ready to start'
                                              : 'Locked until triage'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Consultation Handoff</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {!triage ? (
                                    <p className="text-muted-foreground">
                                        Complete triage first, then hand the
                                        visit to the doctors workspace.
                                    </p>
                                ) : (
                                    <>
                                        <div>
                                            <p className="text-muted-foreground">
                                                Chief Complaint
                                            </p>
                                            <p className="font-medium">
                                                {consultation?.chief_complaint ??
                                                    triage.chief_complaint}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">
                                                Primary Diagnosis
                                            </p>
                                            <p className="font-medium">
                                                {consultation?.primary_diagnosis ||
                                                    'Not documented yet'}
                                            </p>
                                        </div>
                                        <Button className="w-full" asChild>
                                            <Link
                                                href={`/doctors/consultations/${visit.id}`}
                                            >
                                                <NotebookPen className="mr-2 h-4 w-4" />
                                                {consultation
                                                    ? 'Continue Consultation'
                                                    : 'Start Consultation'}
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
