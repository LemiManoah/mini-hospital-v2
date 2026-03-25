import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import {
    type Consultation,
    type DoctorConsultationShowPageProps,
    type TriageRecord,
    type VitalSign,
} from '@/types/patient';
import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ClipboardPen,
    CreditCard,
    Plus,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';

type PrescriptionDraftItem = {
    drug_id: string;
    dosage: string;
    frequency: string;
    route: string;
    duration_days: string;
    quantity: string;
    instructions: string;
    is_prn: boolean;
    prn_reason: string;
    is_external_pharmacy: boolean;
};

const createPrescriptionItem = (): PrescriptionDraftItem => ({
    drug_id: '',
    dosage: '',
    frequency: '',
    route: '',
    duration_days: '5',
    quantity: '1',
    instructions: '',
    is_prn: false,
    prn_reason: '',
    is_external_pharmacy: false,
});

const formatDate = (date: string | null | undefined): string =>
    date
        ? new Date(date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : 'N/A';
const formatDateTime = (date: string | null | undefined): string =>
    date
        ? new Date(date).toLocaleString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'N/A';
const formatMoney = (amount: number | null | undefined): string =>
    amount === null || amount === undefined
        ? 'Not priced'
        : new Intl.NumberFormat('en-US', {
              style: 'currency',
              currency: 'UGX',
              maximumFractionDigits: 0,
          }).format(amount);
const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';
const staffName = (
    staff?: { first_name: string; last_name: string } | null,
): string => (staff ? `${staff.first_name} ${staff.last_name}` : 'Unknown');

const labItemResultValues = (item: {
    resultEntry?: {
        values?: Array<{
            id: string;
            label: string;
            display_value?: string | null;
            value_text: string | null;
            value_numeric: number | null;
            unit: string | null;
            reference_range: string | null;
        }> | null;
    } | null;
    result_entry?: {
        values?: Array<{
            id: string;
            label: string;
            display_value?: string | null;
            value_text: string | null;
            value_numeric: number | null;
            unit: string | null;
            reference_range: string | null;
        }> | null;
    } | null;
}) => item.resultEntry?.values ?? item.result_entry?.values ?? [];

function vitalSummaryItems(
    vital: VitalSign | undefined,
): { label: string; value: string }[] {
    if (!vital) return [];

    return [
        {
            label: 'Temperature',
            value:
                vital.temperature === null
                    ? 'N/A'
                    : `${vital.temperature} ${vital.temperature_unit === 'celsius' ? 'C' : 'F'}`,
        },
        {
            label: 'Pulse',
            value:
                vital.pulse_rate === null ? 'N/A' : `${vital.pulse_rate} bpm`,
        },
        {
            label: 'Respiratory Rate',
            value:
                vital.respiratory_rate === null
                    ? 'N/A'
                    : `${vital.respiratory_rate} /min`,
        },
        {
            label: 'SpO2',
            value:
                vital.oxygen_saturation === null
                    ? 'N/A'
                    : `${vital.oxygen_saturation} %`,
        },
    ];
}

function triageGradeClasses(grade: string | undefined): string {
    return (
        {
            red: 'bg-red-100 text-red-800',
            yellow: 'bg-amber-100 text-amber-800',
            green: 'bg-emerald-100 text-emerald-800',
            black: 'bg-zinc-900 text-zinc-50',
        }[grade ?? ''] ?? 'bg-zinc-100 text-zinc-800'
    );
}

function statusBadgeClasses(status: string): string {
    return (
        {
            requested: 'bg-amber-100 text-amber-900',
            pending: 'bg-amber-100 text-amber-900',
            in_progress: 'bg-blue-100 text-blue-900',
            completed: 'bg-emerald-100 text-emerald-900',
            fully_dispensed: 'bg-emerald-100 text-emerald-900',
            scheduled: 'bg-sky-100 text-sky-900',
            cancelled: 'bg-zinc-200 text-zinc-900',
            rejected: 'bg-rose-100 text-rose-900',
        }[status] ?? 'bg-zinc-100 text-zinc-800'
    );
}

function OrderGuard({
    consultation,
    canManageOrders,
}: {
    consultation: Consultation | null | undefined;
    canManageOrders: boolean;
}) {
    if (!consultation) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                Start the consultation note from the Overview tab before placing
                orders here.
            </div>
        );
    }

    if (consultation.completed_at) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                This consultation has been finalized, so orders in this
                workspace are now read-only.
            </div>
        );
    }

    if (!canManageOrders) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                Orders are visible here, but you do not have permission to add
                or change them in this workspace.
            </div>
        );
    }

    return null;
}

export default function DoctorConsultationShow({
    visit,
    activeTab,
    consultationOutcomes,
    labTestOptions,
    drugOptions,
    labPriorities,
    imagingModalities,
    imagingPriorities,
    imagingLateralities,
    pregnancyStatuses,
    facilityServiceOptions,
}: DoctorConsultationShowPageProps) {
    const { hasPermission } = usePermissions();
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');
    const triage: TriageRecord | null | undefined = visit.triage;
    const consultation: Consultation | null | undefined = visit.consultation;
    const latestVital = (triage?.vitalSigns ?? triage?.vital_signs ?? [])[0];
    const labRequests = visit.labRequests ?? visit.lab_requests ?? [];
    const imagingRequests =
        visit.imagingRequests ?? visit.imaging_requests ?? [];
    const prescriptions = visit.prescriptions ?? [];
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];
    const isConsultationFinalized = consultation?.completed_at != null;
    const [selectedTab, setSelectedTab] = useState(activeTab || 'overview');
    const [outcome, setOutcome] = useState(consultation?.outcome ?? '');
    const canViewVisit = hasPermission('visits.view');
    const canCreateConsultation = hasPermission('consultations.create');
    const canUpdateConsultation = hasPermission('consultations.update');
    const canManageConsultation = consultation
        ? canUpdateConsultation
        : canCreateConsultation;

    const labCatalogByCategory = useMemo(
        () =>
            labTestOptions.reduce<Record<string, typeof labTestOptions>>(
                (groups, option) => {
                    const key = option.category || 'Other';
                    groups[key] ??= [];
                    groups[key].push(option);
                    return groups;
                },
                {},
            ),
        [labTestOptions],
    );

    const labForm = useForm({
        test_ids: [] as string[],
    });
    const imagingForm = useForm({
        modality: imagingModalities[0]?.value ?? 'xray',
        body_part: '',
        laterality: 'na',
        clinical_history:
            consultation?.history_of_present_illness ??
            triage?.history_of_presenting_illness ??
            '',
        indication: consultation?.primary_diagnosis ?? '',
        priority: imagingPriorities[0]?.value ?? 'routine',
        requires_contrast: false,
        contrast_allergy_status: '',
        pregnancy_status: 'unknown',
    });
    const prescriptionForm = useForm({
        items: [createPrescriptionItem()],
    });
    const serviceForm = useForm({
        facility_service_id: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Doctors', href: '/doctors/consultations' },
        { title: 'Consultation', href: '/doctors/consultations' },
        {
            title: visit.visit_number,
            href: `/doctors/consultations/${visit.id}`,
        },
    ];

    const canPlaceOrders =
        consultation != null &&
        !isConsultationFinalized &&
        canUpdateConsultation;
    const toggleLabTest = (testId: string, checked: boolean) =>
        labForm.setData(
            'test_ids',
            checked
                ? [...labForm.data.test_ids, testId]
                : labForm.data.test_ids.filter((value) => value !== testId),
        );
    const updatePrescriptionItem = <K extends keyof PrescriptionDraftItem>(
        index: number,
        field: K,
        value: PrescriptionDraftItem[K],
    ) =>
        prescriptionForm.setData(
            'items',
            prescriptionForm.data.items.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item,
            ),
        );
    const selectedFacilityService = facilityServiceOptions.find(
        (option) => option.id === serviceForm.data.facility_service_id,
    );
    const pendingFacilityServiceIds = new Set(
        facilityServiceOrders
            .filter((order) => order.status === 'pending')
            .map((order) => order.facility_service_id),
    );
    const hasPendingSelectedFacilityService =
        serviceForm.data.facility_service_id !== '' &&
        pendingFacilityServiceIds.has(serviceForm.data.facility_service_id);
    const selectedDrugOptions = prescriptionForm.data.items.map((item) =>
        drugOptions.find((drug) => drug.id === item.drug_id),
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Consultation ${visit.visit_number}`} />
            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700">
                                <Stethoscope className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold">
                                    Consultation Workspace
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {visit.visit_number} for{' '}
                                    {patientName || 'Unknown patient'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                            <span>
                                Clinic: {visit.clinic?.name || 'Not assigned'}
                            </span>
                            <span>
                                Doctor:{' '}
                                {visit.doctor
                                    ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                    : 'Not assigned'}
                            </span>
                            <span>
                                Started:{' '}
                                {formatDateTime(
                                    consultation?.started_at ??
                                        triage?.triage_datetime ??
                                        visit.registered_at,
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
                        {canViewVisit ? (
                            <Button variant="outline" asChild>
                                <Link href={`/visits/${visit.id}`}>
                                    Open Visit Summary
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <Tabs
                            value={selectedTab}
                            onValueChange={setSelectedTab}
                            className="space-y-4"
                        >
                            <TabsList
                                variant="line"
                                className="w-full justify-start"
                            >
                                <TabsTrigger value="overview">
                                    Overview
                                </TabsTrigger>
                                <TabsTrigger value="lab">
                                    Lab Investigation
                                </TabsTrigger>
                                <TabsTrigger value="prescriptions">
                                    Prescriptions
                                </TabsTrigger>
                                <TabsTrigger value="imaging">
                                    Imaging
                                </TabsTrigger>
                                <TabsTrigger value="services">
                                    Services
                                </TabsTrigger>
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
                                                This visit has no triage record
                                                yet, so consultation cannot
                                                begin.
                                            </div>
                                        ) : (
                                            <>
                                                <div className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 xl:grid-cols-4">
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">
                                                            Triage Grade
                                                        </p>
                                                        <span
                                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}
                                                        >
                                                            {
                                                                triage.triage_grade
                                                            }
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">
                                                            Chief Complaint
                                                        </p>
                                                        <p className="font-medium">
                                                            {
                                                                triage.chief_complaint
                                                            }
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">
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
                                                        <p className="text-sm text-muted-foreground">
                                                            Consultation Status
                                                        </p>
                                                        <p className="font-medium">
                                                            {consultation?.completed_at
                                                                ? 'Finalized'
                                                                : consultation
                                                                  ? 'Draft in progress'
                                                                  : 'Ready to start'}
                                                        </p>
                                                    </div>
                                                </div>
                                                {canManageConsultation ? (
                                                    <Form
                                                        method={
                                                            consultation
                                                                ? 'put'
                                                                : 'post'
                                                        }
                                                        action={`/doctors/consultations/${visit.id}`}
                                                    >
                                                        {({
                                                            processing,
                                                            errors,
                                                        }) => (
                                                            <div className="space-y-4 rounded-lg border p-4">
                                                                <input
                                                                    type="hidden"
                                                                    name="outcome"
                                                                    value={
                                                                        outcome
                                                                    }
                                                                />
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="chief_complaint">
                                                                        Chief
                                                                        Complaint
                                                                    </Label>
                                                                    <Textarea
                                                                        id="chief_complaint"
                                                                        name="chief_complaint"
                                                                        rows={3}
                                                                        defaultValue={
                                                                            consultation?.chief_complaint ??
                                                                            triage.chief_complaint
                                                                        }
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.chief_complaint
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label htmlFor="history_of_present_illness">
                                                                        History
                                                                        of
                                                                        Presenting
                                                                        Illness
                                                                    </Label>
                                                                    <Textarea
                                                                        id="history_of_present_illness"
                                                                        name="history_of_present_illness"
                                                                        rows={4}
                                                                        defaultValue={
                                                                            consultation?.history_of_present_illness ??
                                                                            triage.history_of_presenting_illness ??
                                                                            ''
                                                                        }
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.history_of_present_illness ??
                                                                            errors.history_of_presenting_illness
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="review_of_systems">
                                                                            Review
                                                                            of
                                                                            Systems
                                                                        </Label>
                                                                        <Textarea
                                                                            id="review_of_systems"
                                                                            name="review_of_systems"
                                                                            rows={
                                                                                4
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.review_of_systems ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.review_of_systems
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="past_medical_history_summary">
                                                                            Past
                                                                            Medical
                                                                            History
                                                                            Summary
                                                                        </Label>
                                                                        <Textarea
                                                                            id="past_medical_history_summary"
                                                                            name="past_medical_history_summary"
                                                                            rows={
                                                                                4
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.past_medical_history_summary ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.past_medical_history_summary
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="family_history">
                                                                            Family
                                                                            History
                                                                        </Label>
                                                                        <Textarea
                                                                            id="family_history"
                                                                            name="family_history"
                                                                            rows={
                                                                                3
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.family_history ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.family_history
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="social_history">
                                                                            Social
                                                                            History
                                                                        </Label>
                                                                        <Textarea
                                                                            id="social_history"
                                                                            name="social_history"
                                                                            rows={
                                                                                3
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.social_history ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.social_history
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="subjective_notes">
                                                                            Subjective
                                                                            Notes
                                                                        </Label>
                                                                        <Textarea
                                                                            id="subjective_notes"
                                                                            name="subjective_notes"
                                                                            rows={
                                                                                4
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.subjective_notes ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.subjective_notes
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="objective_findings">
                                                                            Objective
                                                                            Findings
                                                                        </Label>
                                                                        <Textarea
                                                                            id="objective_findings"
                                                                            name="objective_findings"
                                                                            rows={
                                                                                4
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.objective_findings ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.objective_findings
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="assessment">
                                                                            Assessment
                                                                        </Label>
                                                                        <Textarea
                                                                            id="assessment"
                                                                            name="assessment"
                                                                            rows={
                                                                                4
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.assessment ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.assessment
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="plan">
                                                                            Plan
                                                                        </Label>
                                                                        <Textarea
                                                                            id="plan"
                                                                            name="plan"
                                                                            rows={
                                                                                4
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.plan ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.plan
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="primary_diagnosis">
                                                                            Primary
                                                                            Diagnosis
                                                                        </Label>
                                                                        <Input
                                                                            id="primary_diagnosis"
                                                                            name="primary_diagnosis"
                                                                            defaultValue={
                                                                                consultation?.primary_diagnosis ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.primary_diagnosis
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="primary_icd10_code">
                                                                            Primary
                                                                            ICD-10
                                                                            Code
                                                                        </Label>
                                                                        <Input
                                                                            id="primary_icd10_code"
                                                                            name="primary_icd10_code"
                                                                            defaultValue={
                                                                                consultation?.primary_icd10_code ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.primary_icd10_code
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="space-y-4 rounded-lg border p-4">
                                                                    <div>
                                                                        <h3 className="font-medium">
                                                                            Disposition
                                                                        </h3>
                                                                        <p className="text-sm text-muted-foreground">
                                                                            Use
                                                                            these
                                                                            fields
                                                                            when
                                                                            finalizing
                                                                            the
                                                                            consultation.
                                                                        </p>
                                                                    </div>
                                                                    <div className="grid gap-4 md:grid-cols-2">
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Outcome
                                                                            </Label>
                                                                            <Select
                                                                                value={
                                                                                    outcome
                                                                                }
                                                                                onValueChange={
                                                                                    setOutcome
                                                                                }
                                                                            >
                                                                                <SelectTrigger>
                                                                                    <SelectValue placeholder="Select outcome" />
                                                                                </SelectTrigger>
                                                                                <SelectContent>
                                                                                    {consultationOutcomes.map(
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
                                                                                    errors.outcome
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label htmlFor="follow_up_days">
                                                                                Follow-up
                                                                                Days
                                                                            </Label>
                                                                            <Input
                                                                                id="follow_up_days"
                                                                                name="follow_up_days"
                                                                                type="number"
                                                                                min={
                                                                                    1
                                                                                }
                                                                                max={
                                                                                    365
                                                                                }
                                                                                defaultValue={
                                                                                    consultation?.follow_up_days ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.follow_up_days
                                                                                }
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="follow_up_instructions">
                                                                            Follow-up
                                                                            Instructions
                                                                        </Label>
                                                                        <Textarea
                                                                            id="follow_up_instructions"
                                                                            name="follow_up_instructions"
                                                                            rows={
                                                                                3
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.follow_up_instructions ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.follow_up_instructions
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-4 md:grid-cols-2">
                                                                        <div className="grid gap-2">
                                                                            <Label htmlFor="referred_to_department">
                                                                                Referred
                                                                                To
                                                                                Department
                                                                            </Label>
                                                                            <Input
                                                                                id="referred_to_department"
                                                                                name="referred_to_department"
                                                                                defaultValue={
                                                                                    consultation?.referred_to_department ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.referred_to_department
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label htmlFor="referred_to_facility">
                                                                                Referred
                                                                                To
                                                                                Facility
                                                                            </Label>
                                                                            <Input
                                                                                id="referred_to_facility"
                                                                                name="referred_to_facility"
                                                                                defaultValue={
                                                                                    consultation?.referred_to_facility ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.referred_to_facility
                                                                                }
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label htmlFor="referral_reason">
                                                                            Referral
                                                                            Reason
                                                                        </Label>
                                                                        <Textarea
                                                                            id="referral_reason"
                                                                            name="referral_reason"
                                                                            rows={
                                                                                3
                                                                            }
                                                                            defaultValue={
                                                                                consultation?.referral_reason ??
                                                                                ''
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.referral_reason
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <label className="flex items-center gap-2 text-sm">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="is_referred"
                                                                            value="1"
                                                                            defaultChecked={
                                                                                consultation?.is_referred ??
                                                                                false
                                                                            }
                                                                            className="h-4 w-4"
                                                                        />
                                                                        Mark
                                                                        this
                                                                        consultation
                                                                        as a
                                                                        referral
                                                                    </label>
                                                                </div>
                                                                <div className="flex items-center justify-between gap-3">
                                                                    <p className="text-sm text-muted-foreground">
                                                                        {isConsultationFinalized
                                                                            ? 'This consultation has been finalized and is now read-only from this stage of the workflow.'
                                                                            : consultation
                                                                              ? 'Save draft as you work, then use the order tabs and finalize once the plan is complete.'
                                                                              : 'Start the consultation draft here, then use the other tabs for downstream clinical work.'}
                                                                    </p>
                                                                    <div className="flex gap-2">
                                                                        <Button
                                                                            type="submit"
                                                                            name="intent"
                                                                            value="save_draft"
                                                                            disabled={
                                                                                processing ||
                                                                                isConsultationFinalized
                                                                            }
                                                                        >
                                                                            {consultation
                                                                                ? 'Save Draft'
                                                                                : 'Start Consultation'}
                                                                        </Button>
                                                                        {consultation ? (
                                                                            <Button
                                                                                type="submit"
                                                                                name="intent"
                                                                                value="complete"
                                                                                disabled={
                                                                                    processing ||
                                                                                    isConsultationFinalized
                                                                                }
                                                                            >
                                                                                {isConsultationFinalized
                                                                                    ? 'Consultation Finalized'
                                                                                    : 'Finalize Consultation'}
                                                                            </Button>
                                                                        ) : null}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </Form>
                                                ) : (
                                                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                                        You can review this
                                                        consultation workspace,
                                                        but you do not have
                                                        permission to edit the
                                                        consultation note.
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="lab" className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            Laboratory Requests
                                        </CardTitle>
                                        <p className="text-sm text-muted-foreground">
                                            Order lab tests against this
                                            consultation and keep the running
                                            request history together.
                                        </p>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders ? (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        ) : (
                                            <form
                                                className="space-y-4 rounded-lg border p-4"
                                                onSubmit={(event) => {
                                                    event.preventDefault();
                                                    labForm.post(
                                                        `/doctors/consultations/${visit.id}/lab-requests`,
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                            onError: () =>
                                                                setSelectedTab(
                                                                    'lab',
                                                                ),
                                                            onSuccess: () => {
                                                                setSelectedTab(
                                                                    'lab',
                                                                );
                                                                labForm.reset(
                                                                    'test_ids',
                                                                );
                                                            },
                                                        },
                                                    );
                                                }}
                                            >
                                                <div className="space-y-3">
                                                    <div>
                                                        <Label>
                                                            Select Tests
                                                        </Label>
                                                        <p className="text-sm text-muted-foreground">
                                                            Choose one or more
                                                            active laboratory
                                                            tests for this
                                                            patient.
                                                        </p>
                                                    </div>
                                                    {Object.entries(
                                                        labCatalogByCategory,
                                                    ).map(
                                                        ([category, tests]) => (
                                                            <div
                                                                key={category}
                                                                className="rounded-lg border p-3"
                                                            >
                                                                <p className="mb-3 text-sm font-medium">
                                                                    {category}
                                                                </p>
                                                                <div className="grid gap-2 md:grid-cols-2">
                                                                    {tests.map(
                                                                        (
                                                                            test,
                                                                        ) => (
                                                                            <label
                                                                                key={
                                                                                    test.id
                                                                                }
                                                                                className="flex items-start gap-3 rounded-md border px-3 py-2 text-sm"
                                                                            >
                                                                                <input
                                                                                    type="checkbox"
                                                                                    checked={labForm.data.test_ids.includes(
                                                                                        test.id,
                                                                                    )}
                                                                                    onChange={(
                                                                                        event,
                                                                                    ) =>
                                                                                        toggleLabTest(
                                                                                            test.id,
                                                                                            event
                                                                                                .target
                                                                                                .checked,
                                                                                        )
                                                                                    }
                                                                                    className="mt-1 h-4 w-4"
                                                                                />
                                                                                <span>
                                                                                    <span className="block font-medium">
                                                                                        {
                                                                                            test.test_name
                                                                                        }
                                                                                        {test.test_code
                                                                                            ? ` (${test.test_code})`
                                                                                            : ''}
                                                                                    </span>
                                                                                    <span className="block text-muted-foreground">
                                                                                        Quoted
                                                                                        price:{' '}
                                                                                        {formatMoney(
                                                                                            test.quoted_price ??
                                                                                                test.base_price,
                                                                                        )}
                                                                                        {test.price_source ===
                                                                                        'insurance_package'
                                                                                            ? ' (insurance package)'
                                                                                            : ' (catalog)'}
                                                                                    </span>
                                                                                </span>
                                                                            </label>
                                                                        ),
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ),
                                                    )}
                                                    <InputError
                                                        message={
                                                            labForm.errors
                                                                .test_ids
                                                        }
                                                    />
                                                </div>
                                                <div className="flex justify-end">
                                                    <Button
                                                        type="submit"
                                                        disabled={
                                                            labForm.processing
                                                        }
                                                    >
                                                        Request Lab Tests
                                                    </Button>
                                                </div>
                                            </form>
                                        )}

                                        {labRequests.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No laboratory requests have been
                                                placed yet.
                                            </p>
                                        ) : (
                                            <div className="space-y-4">
                                                {labRequests.map((request) => (
                                                    <div
                                                        key={request.id}
                                                        className="rounded-lg border p-4"
                                                    >
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <h3 className="font-medium">
                                                                {request.items
                                                                    .map(
                                                                        (
                                                                            item,
                                                                        ) =>
                                                                            item
                                                                                .test
                                                                                ?.test_name,
                                                                    )
                                                                    .filter(
                                                                        Boolean,
                                                                    )
                                                                    .join(
                                                                        ', ',
                                                                    ) ||
                                                                    'Lab request'}
                                                            </h3>
                                                            <Badge
                                                                className={cn(
                                                                    'border-0',
                                                                    statusBadgeClasses(
                                                                        request.status,
                                                                    ),
                                                                )}
                                                            >
                                                                {labelize(
                                                                    request.status,
                                                                )}
                                                            </Badge>
                                                        </div>
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            Ordered by{' '}
                                                            {staffName(
                                                                request.requestedBy,
                                                            )}{' '}
                                                            on{' '}
                                                            {formatDateTime(
                                                                request.request_date,
                                                            )}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            Priority:{' '}
                                                            {labelize(
                                                                request.priority,
                                                            )}
                                                            {request.is_stat
                                                                ? ' | STAT'
                                                                : ''}
                                                        </p>
                                                        {request.clinical_notes ? (
                                                            <p className="mt-2 text-sm text-muted-foreground">
                                                                Clinical notes:{' '}
                                                                {
                                                                    request.clinical_notes
                                                                }
                                                            </p>
                                                        ) : null}
                                                        <div className="mt-4 space-y-3">
                                                            {request.items.map(
                                                                (item) => {
                                                                    const releasedValues =
                                                                        labItemResultValues(
                                                                            item,
                                                                        );

                                                                    return (
                                                                        <div
                                                                            key={
                                                                                item.id
                                                                            }
                                                                            className="rounded-lg border bg-muted/30 p-3"
                                                                        >
                                                                            <div className="flex flex-wrap items-center gap-2">
                                                                                <p className="font-medium">
                                                                                    {item
                                                                                        .test
                                                                                        ?.test_name ??
                                                                                        'Lab test'}
                                                                                </p>
                                                                                <Badge
                                                                                    className={cn(
                                                                                        'border-0',
                                                                                        statusBadgeClasses(
                                                                                            item.status,
                                                                                        ),
                                                                                    )}
                                                                                >
                                                                                    {labelize(
                                                                                        item.workflow_stage ??
                                                                                            item.status,
                                                                                    )}
                                                                                </Badge>
                                                                            </div>
                                                                            {item.result_visible &&
                                                                            releasedValues.length ? (
                                                                                <div className="mt-3 space-y-2">
                                                                                    {releasedValues.map(
                                                                                        (
                                                                                            value,
                                                                                        ) => (
                                                                                            <div
                                                                                                key={
                                                                                                    value.id
                                                                                                }
                                                                                                className="rounded-md border bg-background p-3"
                                                                                            >
                                                                                                <p className="text-sm text-muted-foreground">
                                                                                                    {
                                                                                                        value.label
                                                                                                    }
                                                                                                </p>
                                                                                                <p className="font-medium">
                                                                                                    {value.display_value ??
                                                                                                        value.value_text ??
                                                                                                        value.value_numeric}
                                                                                                    {value.unit
                                                                                                        ? ` ${value.unit}`
                                                                                                        : ''}
                                                                                                </p>
                                                                                                {value.reference_range ? (
                                                                                                    <p className="text-xs text-muted-foreground">
                                                                                                        Reference:{' '}
                                                                                                        {
                                                                                                            value.reference_range
                                                                                                        }
                                                                                                    </p>
                                                                                                ) : null}
                                                                                            </div>
                                                                                        ),
                                                                                    )}
                                                                                </div>
                                                                            ) : (
                                                                                <p className="mt-2 text-sm text-muted-foreground">
                                                                                    Result
                                                                                    not
                                                                                    yet
                                                                                    released.
                                                                                </p>
                                                                            )}
                                                                        </div>
                                                                    );
                                                                },
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent
                                value="prescriptions"
                                className="space-y-6"
                            >
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Prescriptions</CardTitle>
                                        <p className="text-sm text-muted-foreground">
                                            Build the drug plan inside the
                                            consultation workspace so pharmacy
                                            can act on a clear prescription set.
                                        </p>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders ? (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        ) : (
                                            <form
                                                className="space-y-4 rounded-lg border p-4"
                                                onSubmit={(event) => {
                                                    event.preventDefault();
                                                    prescriptionForm.post(
                                                        `/doctors/consultations/${visit.id}/prescriptions`,
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                            onError: () =>
                                                                setSelectedTab(
                                                                    'prescriptions',
                                                                ),
                                                            onSuccess: () => {
                                                                setSelectedTab(
                                                                    'prescriptions',
                                                                );
                                                                prescriptionForm.reset();
                                                            },
                                                        },
                                                    );
                                                }}
                                            >
                                                <div className="space-y-4">
                                                    {prescriptionForm.data.items.map(
                                                        (item, index) => (
                                                            <div
                                                                key={index}
                                                                className="rounded-lg border p-4"
                                                            >
                                                                <div className="mb-4 flex items-center justify-between">
                                                                    <h3 className="font-medium">
                                                                        Drug{' '}
                                                                        {index +
                                                                            1}
                                                                    </h3>
                                                                    {prescriptionForm
                                                                        .data
                                                                        .items
                                                                        .length >
                                                                    1 ? (
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() =>
                                                                                prescriptionForm.setData(
                                                                                    'items',
                                                                                    prescriptionForm.data.items.filter(
                                                                                        (
                                                                                            _,
                                                                                            itemIndex,
                                                                                        ) =>
                                                                                            itemIndex !==
                                                                                            index,
                                                                                    ),
                                                                                )
                                                                            }
                                                                        >
                                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                                            Remove
                                                                        </Button>
                                                                    ) : null}
                                                                </div>
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Drug
                                                                        </Label>
                                                                        <Select
                                                                            value={
                                                                                item.drug_id
                                                                            }
                                                                            onValueChange={(
                                                                                value,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'drug_id',
                                                                                    value,
                                                                                )
                                                                            }
                                                                        >
                                                                            <SelectTrigger>
                                                                                <SelectValue placeholder="Select drug" />
                                                                            </SelectTrigger>
                                                                            <SelectContent>
                                                                                {drugOptions.map(
                                                                                    (
                                                                                        drug,
                                                                                    ) => (
                                                                                        <SelectItem
                                                                                            key={
                                                                                                drug.id
                                                                                            }
                                                                                            value={
                                                                                                drug.id
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                drug.generic_name
                                                                                            }
                                                                                            {drug.brand_name
                                                                                                ? ` (${drug.brand_name})`
                                                                                                : ''}
                                                                                        </SelectItem>
                                                                                    ),
                                                                                )}
                                                                            </SelectContent>
                                                                        </Select>
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.drug_id`
                                                                                ]
                                                                            }
                                                                        />
                                                                        {selectedDrugOptions[
                                                                            index
                                                                        ] ? (
                                                                            <p className="text-xs text-muted-foreground">
                                                                                Quoted
                                                                                price:{' '}
                                                                                {formatMoney(
                                                                                    selectedDrugOptions[
                                                                                        index
                                                                                    ]
                                                                                        ?.quoted_price ??
                                                                                        null,
                                                                                )}
                                                                                {selectedDrugOptions[
                                                                                    index
                                                                                ]
                                                                                    ?.price_source ===
                                                                                'insurance_package'
                                                                                    ? ' (insurance package)'
                                                                                    : ' (no catalog price configured)'}
                                                                            </p>
                                                                        ) : null}
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Dosage
                                                                        </Label>
                                                                        <Input
                                                                            value={
                                                                                item.dosage
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'dosage',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.dosage`
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="mt-4 grid gap-4 md:grid-cols-4">
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Frequency
                                                                        </Label>
                                                                        <Input
                                                                            value={
                                                                                item.frequency
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'frequency',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.frequency`
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Route
                                                                        </Label>
                                                                        <Input
                                                                            value={
                                                                                item.route
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'route',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.route`
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Duration
                                                                            (Days)
                                                                        </Label>
                                                                        <Input
                                                                            type="number"
                                                                            min={
                                                                                1
                                                                            }
                                                                            value={
                                                                                item.duration_days
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'duration_days',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.duration_days`
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Quantity
                                                                        </Label>
                                                                        <Input
                                                                            type="number"
                                                                            min={
                                                                                1
                                                                            }
                                                                            value={
                                                                                item.quantity
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'quantity',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.quantity`
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div className="mt-4 grid gap-2">
                                                                    <Label>
                                                                        Instructions
                                                                    </Label>
                                                                    <Textarea
                                                                        rows={2}
                                                                        value={
                                                                            item.instructions
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updatePrescriptionItem(
                                                                                index,
                                                                                'instructions',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            prescriptionForm
                                                                                .errors[
                                                                                `items.${index}.instructions`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="mt-4 flex flex-wrap gap-4">
                                                                    <label className="flex items-center gap-2 text-sm">
                                                                        <input
                                                                            type="checkbox"
                                                                            checked={
                                                                                item.is_prn
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'is_prn',
                                                                                    event
                                                                                        .target
                                                                                        .checked,
                                                                                )
                                                                            }
                                                                            className="h-4 w-4"
                                                                        />
                                                                        Prescribe
                                                                        as
                                                                        needed
                                                                    </label>
                                                                    <label className="flex items-center gap-2 text-sm">
                                                                        <input
                                                                            type="checkbox"
                                                                            checked={
                                                                                item.is_external_pharmacy
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'is_external_pharmacy',
                                                                                    event
                                                                                        .target
                                                                                        .checked,
                                                                                )
                                                                            }
                                                                            className="h-4 w-4"
                                                                        />
                                                                        External
                                                                        pharmacy
                                                                    </label>
                                                                </div>
                                                                {item.is_prn ? (
                                                                    <div className="mt-4 grid gap-2">
                                                                        <Label>
                                                                            PRN
                                                                            Reason
                                                                        </Label>
                                                                        <Input
                                                                            value={
                                                                                item.prn_reason
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updatePrescriptionItem(
                                                                                    index,
                                                                                    'prn_reason',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                prescriptionForm
                                                                                    .errors[
                                                                                    `items.${index}.prn_reason`
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                ) : null}
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                                <div className="flex flex-wrap items-center justify-between gap-3">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            prescriptionForm.setData(
                                                                'items',
                                                                [
                                                                    ...prescriptionForm
                                                                        .data
                                                                        .items,
                                                                    createPrescriptionItem(),
                                                                ],
                                                            )
                                                        }
                                                    >
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        Add Drug
                                                    </Button>
                                                    <Button
                                                        type="submit"
                                                        disabled={
                                                            prescriptionForm.processing
                                                        }
                                                    >
                                                        Save Prescription
                                                    </Button>
                                                </div>
                                            </form>
                                        )}

                                        {prescriptions.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No prescriptions have been
                                                written yet.
                                            </p>
                                        ) : (
                                            <div className="space-y-4">
                                                {prescriptions.map(
                                                    (prescription) => (
                                                        <div
                                                            key={
                                                                prescription.id
                                                            }
                                                            className="rounded-lg border p-4"
                                                        >
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <h3 className="font-medium">
                                                                    {prescription.primary_diagnosis ||
                                                                        'Prescription'}
                                                                </h3>
                                                                <Badge
                                                                    className={cn(
                                                                        'border-0',
                                                                        statusBadgeClasses(
                                                                            prescription.status,
                                                                        ),
                                                                    )}
                                                                >
                                                                    {labelize(
                                                                        prescription.status,
                                                                    )}
                                                                </Badge>
                                                            </div>
                                                            <p className="mt-1 text-sm text-muted-foreground">
                                                                Prescribed by{' '}
                                                                {staffName(
                                                                    prescription.prescribedBy,
                                                                )}{' '}
                                                                on{' '}
                                                                {formatDateTime(
                                                                    prescription.prescription_date,
                                                                )}
                                                            </p>
                                                            <div className="mt-3 space-y-2 text-sm">
                                                                {prescription.items.map(
                                                                    (item) => (
                                                                        <div
                                                                            key={
                                                                                item.id
                                                                            }
                                                                            className="rounded-md bg-muted/40 px-3 py-2"
                                                                        >
                                                                            <p className="font-medium">
                                                                                {item
                                                                                    .drug
                                                                                    ?.generic_name ??
                                                                                    'Drug'}
                                                                                {item
                                                                                    .drug
                                                                                    ?.brand_name
                                                                                    ? ` (${item.drug.brand_name})`
                                                                                    : ''}
                                                                            </p>
                                                                            <p className="text-muted-foreground">
                                                                                {
                                                                                    item.dosage
                                                                                }{' '}
                                                                                |{' '}
                                                                                {
                                                                                    item.frequency
                                                                                }{' '}
                                                                                |{' '}
                                                                                {
                                                                                    item.route
                                                                                }{' '}
                                                                                |{' '}
                                                                                {
                                                                                    item.duration_days
                                                                                }{' '}
                                                                                day(s)
                                                                                |
                                                                                Qty{' '}
                                                                                {
                                                                                    item.quantity
                                                                                }
                                                                            </p>
                                                                        </div>
                                                                    ),
                                                                )}
                                                            </div>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="imaging" className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Imaging Requests</CardTitle>
                                        <p className="text-sm text-muted-foreground">
                                            Capture radiology and scan requests
                                            with the clinical history and
                                            indication that support the order.
                                        </p>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders ? (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        ) : (
                                            <form
                                                className="space-y-4 rounded-lg border p-4"
                                                onSubmit={(event) => {
                                                    event.preventDefault();
                                                    imagingForm.post(
                                                        `/doctors/consultations/${visit.id}/imaging-requests`,
                                                    );
                                                }}
                                            >
                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <div className="grid gap-2">
                                                        <Label>Modality</Label>
                                                        <Select
                                                            value={
                                                                imagingForm.data
                                                                    .modality
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                imagingForm.setData(
                                                                    'modality',
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {imagingModalities.map(
                                                                    (
                                                                        modality,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                modality.value
                                                                            }
                                                                            value={
                                                                                modality.value
                                                                            }
                                                                        >
                                                                            {
                                                                                modality.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .modality
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="imaging_body_part">
                                                            Body Part
                                                        </Label>
                                                        <Input
                                                            id="imaging_body_part"
                                                            value={
                                                                imagingForm.data
                                                                    .body_part
                                                            }
                                                            onChange={(event) =>
                                                                imagingForm.setData(
                                                                    'body_part',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .body_part
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Laterality
                                                        </Label>
                                                        <Select
                                                            value={
                                                                imagingForm.data
                                                                    .laterality
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                imagingForm.setData(
                                                                    'laterality',
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {imagingLateralities.map(
                                                                    (
                                                                        laterality,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                laterality.value
                                                                            }
                                                                            value={
                                                                                laterality.value
                                                                            }
                                                                        >
                                                                            {
                                                                                laterality.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .laterality
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="imaging_clinical_history">
                                                            Clinical History
                                                        </Label>
                                                        <Textarea
                                                            id="imaging_clinical_history"
                                                            rows={3}
                                                            value={
                                                                imagingForm.data
                                                                    .clinical_history
                                                            }
                                                            onChange={(event) =>
                                                                imagingForm.setData(
                                                                    'clinical_history',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .clinical_history
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="imaging_indication">
                                                            Indication
                                                        </Label>
                                                        <Textarea
                                                            id="imaging_indication"
                                                            rows={3}
                                                            value={
                                                                imagingForm.data
                                                                    .indication
                                                            }
                                                            onChange={(event) =>
                                                                imagingForm.setData(
                                                                    'indication',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .indication
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <div className="grid gap-2">
                                                        <Label>Priority</Label>
                                                        <Select
                                                            value={
                                                                imagingForm.data
                                                                    .priority
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                imagingForm.setData(
                                                                    'priority',
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {imagingPriorities.map(
                                                                    (
                                                                        priority,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                priority.value
                                                                            }
                                                                            value={
                                                                                priority.value
                                                                            }
                                                                        >
                                                                            {
                                                                                priority.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .priority
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Pregnancy Status
                                                        </Label>
                                                        <Select
                                                            value={
                                                                imagingForm.data
                                                                    .pregnancy_status
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                imagingForm.setData(
                                                                    'pregnancy_status',
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {pregnancyStatuses.map(
                                                                    (
                                                                        status,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                status.value
                                                                            }
                                                                            value={
                                                                                status.value
                                                                            }
                                                                        >
                                                                            {
                                                                                status.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .pregnancy_status
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="contrast_allergy_status">
                                                            Contrast Allergy
                                                            Status
                                                        </Label>
                                                        <Input
                                                            id="contrast_allergy_status"
                                                            value={
                                                                imagingForm.data
                                                                    .contrast_allergy_status
                                                            }
                                                            onChange={(event) =>
                                                                imagingForm.setData(
                                                                    'contrast_allergy_status',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                imagingForm
                                                                    .errors
                                                                    .contrast_allergy_status
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <label className="flex items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={
                                                            imagingForm.data
                                                                .requires_contrast
                                                        }
                                                        onChange={(event) =>
                                                            imagingForm.setData(
                                                                'requires_contrast',
                                                                event.target
                                                                    .checked,
                                                            )
                                                        }
                                                        className="h-4 w-4"
                                                    />
                                                    This study requires contrast
                                                </label>
                                                <div className="flex justify-end">
                                                    <Button
                                                        type="submit"
                                                        disabled={
                                                            imagingForm.processing
                                                        }
                                                    >
                                                        Request Imaging
                                                    </Button>
                                                </div>
                                            </form>
                                        )}

                                        {imagingRequests.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No imaging requests have been
                                                placed yet.
                                            </p>
                                        ) : (
                                            <div className="space-y-4">
                                                {imagingRequests.map(
                                                    (request) => (
                                                        <div
                                                            key={request.id}
                                                            className="rounded-lg border p-4"
                                                        >
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <h3 className="font-medium">
                                                                    {labelize(
                                                                        request.modality,
                                                                    )}{' '}
                                                                    {
                                                                        request.body_part
                                                                    }
                                                                </h3>
                                                                <Badge
                                                                    className={cn(
                                                                        'border-0',
                                                                        statusBadgeClasses(
                                                                            request.status,
                                                                        ),
                                                                    )}
                                                                >
                                                                    {labelize(
                                                                        request.status,
                                                                    )}
                                                                </Badge>
                                                            </div>
                                                            <p className="mt-1 text-sm text-muted-foreground">
                                                                Ordered by{' '}
                                                                {staffName(
                                                                    request.requestedBy,
                                                                )}{' '}
                                                                | Priority{' '}
                                                                {labelize(
                                                                    request.priority,
                                                                )}
                                                            </p>
                                                            <p className="mt-2 text-sm text-muted-foreground">
                                                                Clinical
                                                                history:{' '}
                                                                {
                                                                    request.clinical_history
                                                                }
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                Indication:{' '}
                                                                {
                                                                    request.indication
                                                                }
                                                            </p>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="services">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <CreditCard className="h-5 w-5" />
                                            Facility Services
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders ? (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        ) : facilityServiceOptions.length ===
                                          0 ? (
                                            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                                                No facility services are active
                                                in the catalog yet. Add them
                                                from Settings before doctors can
                                                place operational orders.
                                            </div>
                                        ) : (
                                            <form
                                                className="space-y-4 rounded-lg border p-4"
                                                onSubmit={(event) => {
                                                    event.preventDefault();
                                                    serviceForm.post(
                                                        `/doctors/consultations/${visit.id}/facility-service-orders`,
                                                        {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                            onError: () =>
                                                                setSelectedTab(
                                                                    'services',
                                                                ),
                                                            onSuccess: () => {
                                                                setSelectedTab(
                                                                    'services',
                                                                );
                                                                serviceForm.reset(
                                                                    'facility_service_id',
                                                                );
                                                            },
                                                        },
                                                    );
                                                }}
                                            >
                                                <div className="grid gap-4 lg:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Facility Service
                                                        </Label>
                                                        <Select
                                                            value={
                                                                serviceForm.data
                                                                    .facility_service_id
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                serviceForm.setData(
                                                                    'facility_service_id',
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select service" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {facilityServiceOptions.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                option.id
                                                                            }
                                                                            value={
                                                                                option.id
                                                                            }
                                                                            disabled={pendingFacilityServiceIds.has(
                                                                                option.id,
                                                                            )}
                                                                        >
                                                                            {
                                                                                option.name
                                                                            }{' '}
                                                                            (
                                                                            {
                                                                                option.service_code
                                                                            }
                                                                            )
                                                                            {
                                                                                ' - '
                                                                            }
                                                                            {labelize(
                                                                                option.category,
                                                                            )}
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <InputError
                                                            message={
                                                                serviceForm
                                                                    .errors
                                                                    .facility_service_id
                                                            }
                                                        />
                                                    </div>
                                                    <div className="rounded-lg border bg-zinc-50 p-3 text-sm dark:bg-zinc-950">
                                                        <p className="font-medium">
                                                            Service Snapshot
                                                        </p>
                                                        <p className="mt-1 text-muted-foreground">
                                                            {selectedFacilityService
                                                                ? hasPendingSelectedFacilityService
                                                                    ? `${selectedFacilityService.name} already has a pending order for this visit.`
                                                                    : `${selectedFacilityService.name} is ready to order.`
                                                                : 'Select a service to review its catalog details.'}
                                                        </p>
                                                        {selectedFacilityService ? (
                                                            <p className="mt-2 text-muted-foreground">
                                                                Quoted price:{' '}
                                                                {formatMoney(
                                                                    selectedFacilityService.quoted_price ??
                                                                        selectedFacilityService.selling_price ??
                                                                        null,
                                                                )}
                                                                {selectedFacilityService.price_source ===
                                                                'insurance_package'
                                                                    ? ' (insurance package)'
                                                                    : ' (catalog)'}
                                                            </p>
                                                        ) : null}
                                                        {selectedFacilityService ? (
                                                            <div className="mt-2 flex flex-wrap gap-2">
                                                                <Badge variant="outline">
                                                                    {labelize(
                                                                        selectedFacilityService.category,
                                                                    )}
                                                                </Badge>
                                                                <Badge variant="outline">
                                                                    {selectedFacilityService.is_billable
                                                                        ? 'Billable'
                                                                        : 'Non-billable'}
                                                                </Badge>
                                                            </div>
                                                        ) : null}
                                                    </div>
                                                </div>
                                                <div className="flex justify-end">
                                                    <Button
                                                        type="submit"
                                                        disabled={
                                                            serviceForm.processing ||
                                                            serviceForm.data
                                                                .facility_service_id ===
                                                                '' ||
                                                            hasPendingSelectedFacilityService
                                                        }
                                                    >
                                                        Order Facility Service
                                                    </Button>
                                                </div>
                                            </form>
                                        )}

                                        {facilityServiceOrders.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                No facility services have been
                                                ordered yet.
                                            </p>
                                        ) : (
                                            <div className="space-y-4">
                                                {facilityServiceOrders.map(
                                                    (order) => (
                                                        <div
                                                            key={order.id}
                                                            className="rounded-lg border p-4"
                                                        >
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <h3 className="font-medium">
                                                                    {order
                                                                        .service
                                                                        ?.name ??
                                                                        'Facility Service'}
                                                                </h3>
                                                                <Badge
                                                                    className={cn(
                                                                        'border-0',
                                                                        statusBadgeClasses(
                                                                            order.status,
                                                                        ),
                                                                    )}
                                                                >
                                                                    {labelize(
                                                                        order.status,
                                                                    )}
                                                                </Badge>
                                                            </div>
                                                            <p className="mt-1 text-sm text-muted-foreground">
                                                                Ordered by{' '}
                                                                {staffName(
                                                                    order.orderedBy,
                                                                )}{' '}
                                                                on{' '}
                                                                {formatDateTime(
                                                                    order.ordered_at,
                                                                )}
                                                            </p>
                                                            {order.completed_at ? (
                                                                <p className="text-sm text-muted-foreground">
                                                                    Completed:{' '}
                                                                    {formatDateTime(
                                                                        order.completed_at,
                                                                    )}
                                                                </p>
                                                            ) : null}
                                                            {order.status ===
                                                            'pending' ? (
                                                                <div className="mt-3 flex justify-end">
                                                                    <Button
                                                                        type="button"
                                                                        variant="outline"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            router.delete(
                                                                                `/doctors/consultations/${visit.id}/facility-service-orders/${order.id}`,
                                                                                {
                                                                                    preserveState: true,
                                                                                    preserveScroll: true,
                                                                                    onSuccess:
                                                                                        () =>
                                                                                            setSelectedTab(
                                                                                                'services',
                                                                                            ),
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                                        Remove
                                                                        Pending
                                                                        Order
                                                                    </Button>
                                                                </div>
                                                            ) : null}
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Patient Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div>
                                    <p className="text-muted-foreground">
                                        Patient
                                    </p>
                                    <p className="font-medium">
                                        {patientName || 'Unknown patient'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">MRN</p>
                                    <p className="font-medium">
                                        {visit.patient?.patient_number || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Gender
                                    </p>
                                    <p className="font-medium capitalize">
                                        {visit.patient?.gender || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Date of Birth
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.date_of_birth
                                            ? formatDate(
                                                  visit.patient.date_of_birth,
                                              )
                                            : visit.patient?.age
                                              ? `${visit.patient.age} ${visit.patient.age_units}`
                                              : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Phone
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.phone_number || 'N/A'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Triage Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {triage ? (
                                    <>
                                        <div>
                                            <p className="text-muted-foreground">
                                                Triage Grade
                                            </p>
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}
                                            >
                                                {triage.triage_grade}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">
                                                Complaint
                                            </p>
                                            <p className="font-medium">
                                                {triage.chief_complaint}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">
                                                Recorded
                                            </p>
                                            <p className="font-medium">
                                                {formatDateTime(
                                                    triage.triage_datetime,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">
                                                Nurse
                                            </p>
                                            <p className="font-medium">
                                                {staffName(triage.nurse)}
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        No triage record is available for this
                                        visit yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Consultation Status</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div>
                                    <p className="text-muted-foreground">
                                        Draft Status
                                    </p>
                                    <p className="font-medium">
                                        {consultation?.completed_at
                                            ? 'Finalized'
                                            : consultation
                                              ? 'Draft in progress'
                                              : 'Not started'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Outcome
                                    </p>
                                    <p className="font-medium">
                                        {consultation?.outcome
                                            ? consultation.outcome.replaceAll(
                                                  '_',
                                                  ' ',
                                              )
                                            : 'Not set'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Completed At
                                    </p>
                                    <p className="font-medium">
                                        {formatDateTime(
                                            consultation?.completed_at,
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Orders
                                    </p>
                                    <p className="font-medium">
                                        {labRequests.length} lab |{' '}
                                        {imagingRequests.length} imaging |{' '}
                                        {prescriptions.length} prescription
                                        set(s) | {facilityServiceOrders.length}{' '}
                                        services
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Latest Vitals</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {latestVital ? (
                                    vitalSummaryItems(latestVital).map(
                                        (item) => (
                                            <div key={item.label}>
                                                <p className="text-muted-foreground">
                                                    {item.label}
                                                </p>
                                                <p className="font-medium">
                                                    {item.value}
                                                </p>
                                            </div>
                                        ),
                                    )
                                ) : (
                                    <p className="text-muted-foreground">
                                        No vitals recorded yet for this visit.
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
