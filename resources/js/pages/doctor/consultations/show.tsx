import { AllergenModal } from '@/components/allergen-modal';
import { AllergyAlert } from '@/components/allergy-alert';
import InputError from '@/components/input-error';
import { ImagingOrderModal } from '@/components/orders/imaging-order-modal';
import { ImagingOrdersTable } from '@/components/orders/imaging-orders-table';
import { LabOrderModal } from '@/components/orders/lab-order-modal';
import { LabOrdersTable } from '@/components/orders/lab-orders-table';
import { PrescriptionOrderModal } from '@/components/orders/prescription-order-modal';
import { PrescriptionOrdersTable } from '@/components/orders/prescription-orders-table';
import { ServiceOrderModal } from '@/components/orders/service-order-modal';
import { ServiceOrdersTable } from '@/components/orders/service-orders-table';
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
import { type OrderTabValue } from '@/components/visit-ordering';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import {
    type Consultation,
    type DoctorConsultationShowPageProps,
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
    type TriageRecord,
    type VitalSign,
} from '@/types/patient';
import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ClipboardPen, CreditCard, Plus } from 'lucide-react';
import { useState } from 'react';

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
    allergens,
    severityOptions,
    reactionOptions,
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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Doctors', href: '/doctors/consultations' },
        { title: 'Consultation', href: '/doctors/consultations' },
        {
            title: visit.visit_number,
            href: `/doctors/consultations/${visit.id}`,
        },
    ];

    const canPlaceOrders = !isConsultationFinalized && canUpdateConsultation;

    const [labModalOpen, setLabModalOpen] = useState(false);
    const [prescriptionModalOpen, setPrescriptionModalOpen] = useState(false);
    const [imagingModalOpen, setImagingModalOpen] = useState(false);
    const [serviceOrderModalOpen, setServiceOrderModalOpen] = useState(false);
    const [allergenModalOpen, setAllergenModalOpen] = useState(false);

    const [editingLabRequest, setEditingLabRequest] =
        useState<LabRequest | null>(null);
    const [editingPrescription, setEditingPrescription] =
        useState<Prescription | null>(null);
    const [editingImagingRequest, setEditingImagingRequest] =
        useState<ImagingRequest | null>(null);
    const [editingServiceOrder, setEditingServiceOrder] =
        useState<FacilityServiceOrder | null>(null);

    const openOrderDialog = (tab: OrderTabValue) => {
        if (tab === 'lab') setLabModalOpen(true);
        if (tab === 'prescriptions') setPrescriptionModalOpen(true);
        if (tab === 'imaging') setImagingModalOpen(true);
        if (tab === 'services') setServiceOrderModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Consultation ${visit.visit_number}`} />
            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div>
                                <h1 className="text-2xl font-semibold">
                                    Consultation Workspace
                                </h1>
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span>
                                        {formatDate(visit.registered_at)} for{' '}
                                        {patientName || 'Unknown patient'}
                                    </span>
                                    <AllergyAlert
                                        allergies={(
                                            visit.patient?.activeAllergies ??
                                            visit.patient?.allergies
                                        )?.map((a) => ({
                                            id: a.id,
                                            allergen_name:
                                                a.allergen?.name || 'Unknown',
                                            severity: a.severity || 'unknown',
                                            reaction: a.reaction,
                                        }))}
                                    />
                                </div>
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
                            {/* <span>
                                Started:{' '}
                                {formatDateTime(
                                    consultation?.started_at ??
                                        triage?.triage_datetime ??
                                        visit.registered_at,
                                )}
                            </span> */}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/doctors/consultations">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Consultation Queue
                            </Link>
                        </Button>

                        {canPlaceOrders ? (
                            <Button
                                variant="outline"
                                onClick={() => setAllergenModalOpen(true)}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Record Allergy
                            </Button>
                        ) : null}
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
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <div>
                                            <CardTitle>
                                                Laboratory Requests
                                            </CardTitle>
                                            <p className="text-sm text-muted-foreground">
                                                Order lab tests against this
                                                consultation and keep the
                                                running request history
                                                together.
                                            </p>
                                        </div>
                                        {canPlaceOrders && (
                                            <Button
                                                onClick={() => {
                                                    setEditingLabRequest(null);
                                                    setLabModalOpen(true);
                                                }}
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                New Lab Request
                                            </Button>
                                        )}
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders && (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        )}

                                        <LabOrdersTable
                                            labRequests={labRequests}
                                            canManageOrders={canPlaceOrders}
                                            onEdit={(request) => {
                                                setEditingLabRequest(request);
                                                setLabModalOpen(true);
                                            }}
                                            onDelete={(request) => {
                                                if (
                                                    confirm(
                                                        'Are you sure you want to remove this lab request?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        `/visits/${visit.id}/lab-requests/${request.id}`,
                                                        {
                                                            data: {
                                                                redirect_to:
                                                                    'consultation',
                                                            },
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent
                                value="prescriptions"
                                className="space-y-6"
                            >
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <div>
                                            <CardTitle>Prescriptions</CardTitle>
                                            <p className="text-sm text-muted-foreground">
                                                Build the drug plan inside the
                                                consultation workspace so
                                                pharmacy can act on a clear
                                                prescription set.
                                            </p>
                                        </div>
                                        {canPlaceOrders && (
                                            <Button
                                                onClick={() => {
                                                    setEditingPrescription(
                                                        null,
                                                    );
                                                    setPrescriptionModalOpen(
                                                        true,
                                                    );
                                                }}
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                New Prescription
                                            </Button>
                                        )}
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders && (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        )}

                                        <PrescriptionOrdersTable
                                            prescriptions={prescriptions}
                                            canManageOrders={canPlaceOrders}
                                            onEdit={(prescription) => {
                                                setEditingPrescription(
                                                    prescription,
                                                );
                                                setPrescriptionModalOpen(true);
                                            }}
                                            onDelete={(prescription) => {
                                                if (
                                                    confirm(
                                                        'Are you sure you want to remove this prescription?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        `/visits/${visit.id}/prescriptions/${prescription.id}`,
                                                        {
                                                            data: {
                                                                redirect_to:
                                                                    'consultation',
                                                            },
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="imaging" className="space-y-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <div>
                                            <CardTitle>
                                                Imaging Requests
                                            </CardTitle>
                                            <p className="text-sm text-muted-foreground">
                                                Capture radiology and scan
                                                requests with the clinical
                                                history and indication that
                                                support the order.
                                            </p>
                                        </div>
                                        {canPlaceOrders && (
                                            <Button
                                                onClick={() => {
                                                    setEditingImagingRequest(
                                                        null,
                                                    );
                                                    setImagingModalOpen(true);
                                                }}
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                New Imaging Request
                                            </Button>
                                        )}
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders && (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        )}

                                        <ImagingOrdersTable
                                            imagingRequests={imagingRequests}
                                            canManageOrders={canPlaceOrders}
                                            onEdit={(request) => {
                                                setEditingImagingRequest(
                                                    request,
                                                );
                                                setImagingModalOpen(true);
                                            }}
                                            onDelete={(request) => {
                                                if (
                                                    confirm(
                                                        'Are you sure you want to remove this imaging request?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        `/visits/${visit.id}/imaging-requests/${request.id}`,
                                                        {
                                                            data: {
                                                                redirect_to:
                                                                    'consultation',
                                                            },
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="services" className="space-y-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <CreditCard className="h-5 w-5" />
                                                Facility Services
                                            </CardTitle>
                                            <p className="text-sm text-muted-foreground">
                                                Order facility services for this
                                                visit.
                                            </p>
                                        </div>
                                        {canPlaceOrders && (
                                            <Button
                                                onClick={() => {
                                                    setEditingServiceOrder(
                                                        null,
                                                    );
                                                    setServiceOrderModalOpen(
                                                        true,
                                                    );
                                                }}
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                New Service Order
                                            </Button>
                                        )}
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {!canPlaceOrders && (
                                            <OrderGuard
                                                consultation={consultation}
                                                canManageOrders={
                                                    canUpdateConsultation
                                                }
                                            />
                                        )}

                                        <ServiceOrdersTable
                                            orders={facilityServiceOrders}
                                            canManageOrders={canPlaceOrders}
                                            onEdit={(order) => {
                                                setEditingServiceOrder(order);
                                                setServiceOrderModalOpen(true);
                                            }}
                                            onDelete={(order) => {
                                                if (
                                                    confirm(
                                                        'Are you sure you want to remove this service order?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        `/visits/${visit.id}/facility-service-orders/${order.id}`,
                                                        {
                                                            data: {
                                                                redirect_to:
                                                                    'consultation',
                                                            },
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>

                        {canPlaceOrders && (
                            <>
                                <LabOrderModal
                                    open={labModalOpen}
                                    onOpenChange={setLabModalOpen}
                                    visit={visit}
                                    labRequest={editingLabRequest}
                                    labTestOptions={labTestOptions}
                                    labPriorities={labPriorities}
                                    redirectTo="consultation"
                                />
                                <PrescriptionOrderModal
                                    open={prescriptionModalOpen}
                                    onOpenChange={setPrescriptionModalOpen}
                                    visit={visit}
                                    prescription={editingPrescription}
                                    drugOptions={drugOptions}
                                    redirectTo="consultation"
                                />
                                <ImagingOrderModal
                                    open={imagingModalOpen}
                                    onOpenChange={setImagingModalOpen}
                                    visit={visit}
                                    imagingRequest={editingImagingRequest}
                                    imagingModalities={imagingModalities}
                                    imagingPriorities={imagingPriorities}
                                    imagingLateralities={imagingLateralities}
                                    pregnancyStatuses={pregnancyStatuses}
                                    redirectTo="consultation"
                                />
                                <ServiceOrderModal
                                    open={serviceOrderModalOpen}
                                    onOpenChange={setServiceOrderModalOpen}
                                    visit={visit}
                                    serviceOrder={editingServiceOrder}
                                    facilityServiceOptions={
                                        facilityServiceOptions
                                    }
                                    redirectTo="consultation"
                                />
                                <AllergenModal
                                    open={allergenModalOpen}
                                    onOpenChange={setAllergenModalOpen}
                                    patientId={visit.patient?.id || ''}
                                    allergens={allergens}
                                    severityOptions={severityOptions}
                                    reactionOptions={reactionOptions}
                                />
                            </>
                        )}
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
