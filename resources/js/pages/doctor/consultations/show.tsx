import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type Consultation,
    type DoctorConsultationShowPageProps,
    type TriageRecord,
    type VitalSign,
} from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ClipboardPen,
    CreditCard,
    FlaskConical,
    Pill,
    ScanLine,
    Stethoscope,
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
    ];
}

function triageGradeClasses(grade: string | undefined): string {
    return (
        {
            red: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            yellow: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            green: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            black: 'bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900',
        }[grade ?? ''] ?? 'bg-zinc-100 text-zinc-800'
    );
}

function OrdersPlaceholder({
    icon: Icon,
    title,
    description,
}: {
    icon: typeof FlaskConical;
    title: string;
    description: string;
}) {
    return (
        <Card>
            <CardContent className="flex flex-col items-start gap-3 p-6">
                <div className="flex h-11 w-11 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    <Icon className="h-5 w-5" />
                </div>
                <div className="space-y-1">
                    <h3 className="font-semibold">{title}</h3>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

export default function DoctorConsultationShow({
    visit,
}: DoctorConsultationShowPageProps) {
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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Doctors', href: '/doctors/consultations' },
        { title: 'Consultation', href: '/doctors/consultations' },
        { title: visit.visit_number, href: `/doctors/consultations/${visit.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Consultation ${visit.visit_number}`} />

            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                <Stethoscope className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold">
                                    Consultation Workspace
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {visit.visit_number} for {patientName || 'Unknown patient'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                            <span>Clinic: {visit.clinic?.name || 'Not assigned'}</span>
                            <span>
                                Doctor:{' '}
                                {visit.doctor
                                    ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                    : 'Not assigned'}
                            </span>
                            <span>
                                Started:{' '}
                                {formatDateTime(
                                    consultation?.started_at ?? triage?.triage_datetime ?? visit.registered_at,
                                )}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/doctors/consultations">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Consultation Queue
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/visits/${visit.id}`}>Open Visit Summary</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <Tabs defaultValue="overview" className="space-y-4">
                            <TabsList variant="line" className="w-full justify-start">
                                <TabsTrigger value="overview">Overview</TabsTrigger>
                                <TabsTrigger value="lab">Lab Investigation</TabsTrigger>
                                <TabsTrigger value="prescriptions">Prescriptions</TabsTrigger>
                                <TabsTrigger value="imaging">Imaging</TabsTrigger>
                                <TabsTrigger value="services">Services</TabsTrigger>
                            </TabsList>

                            <TabsContent value="overview" className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <ClipboardPen className="h-5 w-5" />
                                            Consultation Note
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!triage ? (
                                            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                                This visit has no triage record yet, so consultation cannot begin.
                                            </div>
                                        ) : (
                                            <>
                                                <div className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 xl:grid-cols-4">
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">Triage Grade</p>
                                                        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}>
                                                            {triage.triage_grade}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">Chief Complaint</p>
                                                        <p className="font-medium">{triage.chief_complaint}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">Latest Vitals</p>
                                                        <p className="font-medium">{latestVital ? formatDateTime(latestVital.recorded_at) : 'Not yet captured'}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">Consultation Status</p>
                                                        <p className="font-medium">{consultation ? 'Draft in progress' : 'Ready to start'}</p>
                                                    </div>
                                                </div>

                                                <Form method={consultation ? 'put' : 'post'} action={`/doctors/consultations/${visit.id}`}>
                                                    {({ processing, errors }) => (
                                                        <div className="space-y-4 rounded-lg border p-4">
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="chief_complaint">Chief Complaint</Label>
                                                                <Textarea id="chief_complaint" name="chief_complaint" rows={3} defaultValue={consultation?.chief_complaint ?? triage.chief_complaint} />
                                                                <InputError message={errors.chief_complaint} />
                                                            </div>

                                                            <div className="grid gap-2">
                                                                <Label htmlFor="history_of_presenting_illness">History of Presenting Illness</Label>
                                                                <Textarea id="history_of_presenting_illness" name="history_of_presenting_illness" rows={4} defaultValue={consultation?.history_of_presenting_illness ?? triage.history_of_presenting_illness ?? ''} />
                                                                <InputError message={errors.history_of_presenting_illness} />
                                                            </div>

                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="review_of_systems">Review of Systems</Label>
                                                                    <Textarea id="review_of_systems" name="review_of_systems" rows={4} defaultValue={consultation?.review_of_systems ?? ''} />
                                                                    <InputError message={errors.review_of_systems} />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="past_medical_history_summary">Past Medical History Summary</Label>
                                                                    <Textarea id="past_medical_history_summary" name="past_medical_history_summary" rows={4} defaultValue={consultation?.past_medical_history_summary ?? ''} />
                                                                    <InputError message={errors.past_medical_history_summary} />
                                                                </div>
                                                            </div>

                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="family_history">Family History</Label>
                                                                    <Textarea id="family_history" name="family_history" rows={3} defaultValue={consultation?.family_history ?? ''} />
                                                                    <InputError message={errors.family_history} />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="social_history">Social History</Label>
                                                                    <Textarea id="social_history" name="social_history" rows={3} defaultValue={consultation?.social_history ?? ''} />
                                                                    <InputError message={errors.social_history} />
                                                                </div>
                                                            </div>

                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="subjective_notes">Subjective Notes</Label>
                                                                    <Textarea id="subjective_notes" name="subjective_notes" rows={4} defaultValue={consultation?.subjective_notes ?? ''} />
                                                                    <InputError message={errors.subjective_notes} />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="objective_findings">Objective Findings</Label>
                                                                    <Textarea id="objective_findings" name="objective_findings" rows={4} defaultValue={consultation?.objective_findings ?? ''} />
                                                                    <InputError message={errors.objective_findings} />
                                                                </div>
                                                            </div>

                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="assessment">Assessment</Label>
                                                                    <Textarea id="assessment" name="assessment" rows={4} defaultValue={consultation?.assessment ?? ''} />
                                                                    <InputError message={errors.assessment} />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="plan">Plan</Label>
                                                                    <Textarea id="plan" name="plan" rows={4} defaultValue={consultation?.plan ?? ''} />
                                                                    <InputError message={errors.plan} />
                                                                </div>
                                                            </div>

                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="primary_diagnosis">Primary Diagnosis</Label>
                                                                    <Input id="primary_diagnosis" name="primary_diagnosis" defaultValue={consultation?.primary_diagnosis ?? ''} />
                                                                    <InputError message={errors.primary_diagnosis} />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="primary_icd10_code">Primary ICD-10 Code</Label>
                                                                    <Input id="primary_icd10_code" name="primary_icd10_code" defaultValue={consultation?.primary_icd10_code ?? ''} />
                                                                    <InputError message={errors.primary_icd10_code} />
                                                                </div>
                                                            </div>

                                                            <div className="flex items-center justify-between gap-3">
                                                                <p className="text-sm text-muted-foreground">
                                                                    {consultation ? 'Continue refining the consultation draft from this workspace.' : 'Start the consultation draft here, then use the other tabs for downstream clinical work as those phases land.'}
                                                                </p>
                                                                <Button type="submit" disabled={processing}>
                                                                    {consultation ? 'Save Consultation' : 'Start Consultation'}
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    )}
                                                </Form>
                                            </>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="lab">
                                <OrdersPlaceholder icon={FlaskConical} title="Lab Investigation" description="This tab will host lab requests from the consultation workspace. The consultation context is now separated so we can add ordering here cleanly in the next phase." />
                            </TabsContent>

                            <TabsContent value="prescriptions">
                                <OrdersPlaceholder icon={Pill} title="Prescriptions" description="Prescription ordering will live here so the doctor can document the note and medication plan without leaving the consultation workspace." />
                            </TabsContent>

                            <TabsContent value="imaging">
                                <OrdersPlaceholder icon={ScanLine} title="Imaging" description="Imaging requests will be attached here once radiology ordering is wired into the consultation module." />
                            </TabsContent>

                            <TabsContent value="services">
                                <OrdersPlaceholder icon={CreditCard} title="Facility Services" description="General facility service ordering will be added here later, using the Option A approach with operational orders and billing resolved through charge masters." />
                            </TabsContent>
                        </Tabs>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Patient Snapshot</CardTitle></CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div><p className="text-muted-foreground">Patient</p><p className="font-medium">{patientName || 'Unknown patient'}</p></div>
                                <div><p className="text-muted-foreground">MRN</p><p className="font-medium">{visit.patient?.patient_number || 'N/A'}</p></div>
                                <div><p className="text-muted-foreground">Gender</p><p className="font-medium capitalize">{visit.patient?.gender || 'N/A'}</p></div>
                                <div><p className="text-muted-foreground">Date of Birth</p><p className="font-medium">{visit.patient?.date_of_birth ? formatDate(visit.patient.date_of_birth) : visit.patient?.age ? `${visit.patient.age} ${visit.patient.age_units}` : 'N/A'}</p></div>
                                <div><p className="text-muted-foreground">Phone</p><p className="font-medium">{visit.patient?.phone_number || 'N/A'}</p></div>
                                <div><p className="text-muted-foreground">Blood Group</p><p className="font-medium">{visit.patient?.blood_group || 'N/A'}</p></div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Triage Snapshot</CardTitle></CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {triage ? (
                                    <>
                                        <div><p className="text-muted-foreground">Triage Grade</p><span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}>{triage.triage_grade}</span></div>
                                        <div><p className="text-muted-foreground">Complaint</p><p className="font-medium">{triage.chief_complaint}</p></div>
                                        <div><p className="text-muted-foreground">Recorded</p><p className="font-medium">{formatDateTime(triage.triage_datetime)}</p></div>
                                        <div><p className="text-muted-foreground">Nurse</p><p className="font-medium">{triage.nurse ? `${triage.nurse.first_name} ${triage.nurse.last_name}` : 'Unknown nurse'}</p></div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">No triage record is available for this visit yet.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Latest Vitals</CardTitle></CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {latestVital ? (
                                    vitalSummaryItems(latestVital).map((item) => (
                                        <div key={item.label}>
                                            <p className="text-muted-foreground">{item.label}</p>
                                            <p className="font-medium">{item.value}</p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-muted-foreground">No vitals recorded yet for this visit.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
