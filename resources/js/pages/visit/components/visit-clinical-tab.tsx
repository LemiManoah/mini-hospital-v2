import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
    type VitalSign,
} from '@/types/patient';
import { Link } from '@inertiajs/react';
import { HeartPulse, NotebookPen } from 'lucide-react';
import {
    findLabel,
    formatDateTime,
    triageGradeClasses,
    vitalSummaryItems,
} from './visit-show-utils';

type ClinicalTriage = {
    triage_grade: string;
    triage_datetime: string;
    chief_complaint: string;
    history_of_presenting_illness?: string | null;
    nurse_notes?: string | null;
    nurse?: { first_name: string; last_name: string } | null;
    assignedClinic?: { name?: string | null } | null;
    assigned_clinic?: { name?: string | null } | null;
    vitalSigns?: VitalSign[];
    vital_signs?: VitalSign[];
};

type VisitClinicalTabProps = {
    visit: {
        id: string;
        labRequests?: LabRequest[] | null;
        lab_requests?: LabRequest[] | null;
        prescriptions?: Prescription[] | null;
        imagingRequests?: ImagingRequest[] | null;
        imaging_requests?: ImagingRequest[] | null;
        facilityServiceOrders?: FacilityServiceOrder[] | null;
        facility_service_orders?: FacilityServiceOrder[] | null;
    };
    triage: ClinicalTriage | null | undefined;
    consultation:
        | {
              started_at: string;
              primary_diagnosis?: string | null;
              doctor?: { first_name: string; last_name: string } | null;
          }
        | null
        | undefined;
    triageGrades: { value: string; label: string }[];
    canViewTriage: boolean;
    canViewConsultation: boolean;
};

const formatMoney = (amount: number | null | undefined): string =>
    amount === null || amount === undefined
        ? 'Not priced'
        : new Intl.NumberFormat('en-US', {
              style: 'currency',
              currency: 'UGX',
              maximumFractionDigits: 0,
          }).format(amount);

const releasedLabValues = (item: LabRequest['items'][number]) =>
    item.resultEntry?.values ?? item.result_entry?.values ?? [];

export function VisitClinicalTab({
    visit,
    triage,
    consultation,
    triageGrades,
    canViewTriage,
    canViewConsultation,
}: VisitClinicalTabProps) {
    const latestVital = (triage?.vitalSigns ?? triage?.vital_signs ?? [])[0];
    const labRequests = visit.labRequests ?? visit.lab_requests ?? [];
    const prescriptions = visit.prescriptions ?? [];
    const imagingRequests =
        visit.imagingRequests ?? visit.imaging_requests ?? [];
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Triage Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {!triage ? (
                        <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                            Triage is now managed in the dedicated triage
                            workspace for this visit.
                        </div>
                    ) : (
                        <>
                            <div className="flex flex-wrap items-center gap-3">
                                <span
                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}
                                >
                                    {findLabel(
                                        triageGrades,
                                        triage.triage_grade,
                                    )}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    Recorded{' '}
                                    {formatDateTime(triage.triage_datetime)}
                                </span>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Chief Complaint
                                    </p>
                                    <p className="font-medium">
                                        {triage.chief_complaint}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Nurse
                                    </p>
                                    <p className="font-medium">
                                        {triage.nurse
                                            ? `${triage.nurse.first_name} ${triage.nurse.last_name}`
                                            : 'Unknown'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Assigned Clinic
                                    </p>
                                    <p className="font-medium">
                                        {triage.assignedClinic?.name ||
                                            triage.assigned_clinic?.name ||
                                            'Not assigned'}
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
                            </div>
                            <div className="grid gap-3 rounded-lg border p-4">
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
                                    <p className="text-sm text-muted-foreground">
                                        Notes
                                    </p>
                                    <p className="font-medium">
                                        {triage.nurse_notes || 'Not documented'}
                                    </p>
                                </div>
                            </div>
                            {latestVital ? (
                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    {vitalSummaryItems(latestVital).map(
                                        (item) => (
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
                                        ),
                                    )}
                                </div>
                            ) : null}
                        </>
                    )}
                    {canViewTriage ? (
                        <div className="flex justify-end">
                            <Button asChild>
                                <Link href={`/triage/${visit.id}`}>
                                    <HeartPulse className="mr-2 h-4 w-4" />
                                    {triage
                                        ? 'Continue in Triage Workspace'
                                        : 'Open Triage Workspace'}
                                </Link>
                            </Button>
                        </div>
                    ) : null}
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
                                <p className="font-medium">
                                    {formatDateTime(consultation.started_at)}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">
                                    Clinician
                                </p>
                                <p className="font-medium">
                                    {consultation.doctor
                                        ? `${consultation.doctor.first_name} ${consultation.doctor.last_name}`
                                        : 'Assigned clinician'}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">
                                    Primary Diagnosis
                                </p>
                                <p className="font-medium">
                                    {consultation.primary_diagnosis ||
                                        'Not documented yet'}
                                </p>
                            </div>
                            {canViewConsultation ? (
                                <div className="flex justify-end">
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={`/doctors/consultations/${visit.id}`}
                                        >
                                            <NotebookPen className="mr-2 h-4 w-4" />
                                            Continue Consultation
                                        </Link>
                                    </Button>
                                </div>
                            ) : null}
                        </>
                    ) : (
                        <p className="text-muted-foreground">
                            Consultation has not been started yet. Use the
                            dedicated doctors workspace after triage.
                        </p>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Ordered Labs</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    {labRequests.length ? (
                        labRequests.map((request) => (
                            <div
                                key={request.id}
                                className="rounded-lg border p-3"
                            >
                                <p className="font-medium">
                                    {request.items
                                        .map((item) => item.test?.test_name)
                                        .filter(Boolean)
                                        .join(', ') || 'Lab request'}
                                </p>
                                <p className="text-muted-foreground">
                                    Requested{' '}
                                    {formatDateTime(request.request_date)}
                                </p>
                                <p className="text-muted-foreground">
                                    Estimated total:{' '}
                                    {formatMoney(
                                        request.items.reduce(
                                            (total, item) =>
                                                total + (item.price ?? 0),
                                            0,
                                        ),
                                    )}
                                </p>
                                <div className="mt-3 space-y-2">
                                    {request.items.map((item) => (
                                        <div
                                            key={item.id}
                                            className="rounded-md border bg-muted/30 p-3"
                                        >
                                            <p className="font-medium">
                                                {item.test?.test_name ??
                                                    'Lab test'}
                                            </p>
                                            {item.result_visible &&
                                            releasedLabValues(item).length ? (
                                                <div className="mt-2 space-y-2">
                                                    {releasedLabValues(item).map(
                                                        (value) => (
                                                            <div
                                                                key={value.id}
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
                                                <p className="mt-2 text-muted-foreground">
                                                    Result not yet released.
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))
                    ) : (
                        <p className="text-muted-foreground">
                            No lab requests recorded for this visit yet.
                        </p>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Prescriptions</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    {prescriptions.length ? (
                        prescriptions.map((prescription) => (
                            <div
                                key={prescription.id}
                                className="rounded-lg border p-3"
                            >
                                <p className="font-medium">
                                    {prescription.primary_diagnosis ||
                                        'Prescription'}
                                </p>
                                <p className="text-muted-foreground">
                                    Written{' '}
                                    {formatDateTime(
                                        prescription.prescription_date,
                                    )}
                                </p>
                                <p className="text-muted-foreground">
                                    {prescription.items
                                        .map((item) => item.drug?.generic_name)
                                        .filter(Boolean)
                                        .join(', ') || 'No items'}
                                </p>
                            </div>
                        ))
                    ) : (
                        <p className="text-muted-foreground">
                            No prescriptions recorded for this visit yet.
                        </p>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Imaging Requests</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    {imagingRequests.length ? (
                        imagingRequests.map((request) => (
                            <div
                                key={request.id}
                                className="rounded-lg border p-3"
                            >
                                <p className="font-medium">
                                    {request.modality.toUpperCase()}{' '}
                                    {request.body_part}
                                </p>
                                <p className="text-muted-foreground">
                                    Scheduled:{' '}
                                    {formatDateTime(request.scheduled_date)}
                                </p>
                                <p className="text-muted-foreground">
                                    Indication: {request.indication}
                                </p>
                            </div>
                        ))
                    ) : (
                        <p className="text-muted-foreground">
                            No imaging requests recorded for this visit yet.
                        </p>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Facility Services</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    {facilityServiceOrders.length ? (
                        facilityServiceOrders.map((order) => (
                            <div
                                key={order.id}
                                className="rounded-lg border p-3"
                            >
                                <p className="font-medium">
                                    {order.service?.name || 'Facility service'}
                                </p>
                                <p className="text-muted-foreground">
                                    Ordered {formatDateTime(order.ordered_at)}
                                </p>
                                <p className="text-muted-foreground">
                                    Catalog price:{' '}
                                    {formatMoney(
                                        order.service?.selling_price ?? null,
                                    )}
                                </p>
                            </div>
                        ))
                    ) : (
                        <p className="text-muted-foreground">
                            No facility service orders recorded for this visit
                            yet.
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
