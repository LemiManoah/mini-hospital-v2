import { AllergenModal } from '@/components/allergen-modal';
import { ImagingOrderModal } from '@/components/orders/imaging-order-modal';
import { LabOrderModal } from '@/components/orders/lab-order-modal';
import { PrescriptionOrderModal } from '@/components/orders/prescription-order-modal';
import { ServiceOrderModal } from '@/components/orders/service-order-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    type DrugOption,
    type FacilityServiceOption,
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
    type VitalSign,
} from '@/types/patient';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    findLabel,
    formatDateTime,
    triageGradeClasses,
    vitalSummaryItems,
} from './visit-show-utils';
import { VisitOrderCenterModal } from './visit-order-center-modal';

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
        patient?: { id: string } | null;
        triage?: ClinicalTriage | null;
        consultation?: { completed_at?: string | null } | null;
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
              completed_at?: string | null;
              primary_diagnosis?: string | null;
              doctor?: { first_name: string; last_name: string } | null;
          }
        | null
        | undefined;
    triageGrades: { value: string; label: string }[];
    canViewTriage: boolean;
    canViewConsultation: boolean;
    canManageOrders: boolean;
    labTestOptions: Array<{
        id: string;
        test_code: string;
        test_name: string;
        category: string | null;
        base_price: number | null;
        quoted_price?: number | null;
        price_source?: string | null;
    }>;
    drugOptions: DrugOption[];
    labPriorities: { value: string; label: string }[];
    imagingModalities: { value: string; label: string }[];
    imagingPriorities: { value: string; label: string }[];
    imagingLateralities: { value: string; label: string }[];
    pregnancyStatuses: { value: string; label: string }[];
    facilityServiceOptions: FacilityServiceOption[];
    allergens: { id: string; name: string; type: string }[];
    severityOptions: { value: string; label: string }[];
    reactionOptions: { value: string; label: string }[];
};

export function VisitClinicalTab({
    visit,
    triage,
    consultation,
    triageGrades,
    canViewTriage,
    canViewConsultation,
    canManageOrders,
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
}: VisitClinicalTabProps) {
    const latestVital = (triage?.vitalSigns ?? triage?.vital_signs ?? [])[0];
    const labRequests = visit.labRequests ?? visit.lab_requests ?? [];
    const prescriptions = visit.prescriptions ?? [];
    const imagingRequests =
        visit.imagingRequests ?? visit.imaging_requests ?? [];
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];
    const isConsultationFinalized = consultation?.completed_at != null;

    const [orderCenterOpen, setOrderCenterOpen] = useState(false);
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

    const openOrderDialog = (
        tab: 'lab' | 'prescriptions' | 'imaging' | 'services',
    ) => {
        setOrderCenterOpen(false);

        if (tab === 'lab') {
            setLabModalOpen(true);
        }

        if (tab === 'prescriptions') {
            setPrescriptionModalOpen(true);
        }

        if (tab === 'imaging') {
            setImagingModalOpen(true);
        }

        if (tab === 'services') {
            setServiceOrderModalOpen(true);
        }
    };

    const deleteVisitOrder = (
        path: string,
        confirmationMessage: string,
    ): void => {
        if (!confirm(confirmationMessage)) {
            return;
        }

        router.delete(path, {
            data: { redirect_to: 'visit' },
            preserveScroll: true,
        });
    };

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
                            <Button variant="outline" asChild>
                                <Link href={`/triage/${visit.id}`}>
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
                                            Continue Consultation
                                        </Link>
                                    </Button>
                                </div>
                            ) : null}
                        </>
                    ) : (
                        <div className="space-y-3">
                            <p className="text-muted-foreground">
                                Consultation has not been started yet. You can
                                still place visit orders from here for test-only
                                or procedure-only visits.
                            </p>
                            {canViewConsultation ? (
                                <div className="flex justify-end">
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={`/doctors/consultations/${visit.id}`}
                                        >
                                            Open Consultation Workspace
                                        </Link>
                                    </Button>
                                </div>
                            ) : null}
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <CardTitle>Order Center</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Keep ordering work in one place while the main page
                            stays focused on the visit itself.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canManageOrders && !isConsultationFinalized ? (
                            <Button
                                variant="outline"
                                onClick={() => setAllergenModalOpen(true)}
                            >
                                Record Allergy
                            </Button>
                        ) : null}
                        <Button onClick={() => setOrderCenterOpen(true)}>
                            Open Order Center
                        </Button>
                    </div>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-xl border p-4">
                        <p className="text-sm text-muted-foreground">
                            Prescriptions
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {prescriptions.length}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Medication orders on this visit.
                        </p>
                    </div>
                    <div className="rounded-xl border p-4">
                        <p className="text-sm text-muted-foreground">
                            Laboratory
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {labRequests.length}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Investigation requests recorded so far.
                        </p>
                    </div>
                    <div className="rounded-xl border p-4">
                        <p className="text-sm text-muted-foreground">
                            Imaging
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {imagingRequests.length}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Radiology and imaging orders.
                        </p>
                    </div>
                    <div className="rounded-xl border p-4">
                        <p className="text-sm text-muted-foreground">
                            Other Services
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {facilityServiceOrders.length}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Service orders linked to this encounter.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <VisitOrderCenterModal
                open={orderCenterOpen}
                onOpenChange={setOrderCenterOpen}
                canManageOrders={canManageOrders}
                isConsultationFinalized={isConsultationFinalized}
                labRequests={labRequests}
                prescriptions={prescriptions}
                imagingRequests={imagingRequests}
                serviceOrders={facilityServiceOrders}
                onRecordAllergy={() => {
                    setOrderCenterOpen(false);
                    setAllergenModalOpen(true);
                }}
                onOrderLab={() => {
                    setEditingLabRequest(null);
                    openOrderDialog('lab');
                }}
                onOrderPrescription={() => {
                    setEditingPrescription(null);
                    openOrderDialog('prescriptions');
                }}
                onOrderImaging={() => {
                    setEditingImagingRequest(null);
                    openOrderDialog('imaging');
                }}
                onOrderService={() => {
                    setEditingServiceOrder(null);
                    openOrderDialog('services');
                }}
                onEditLabRequest={(request) => {
                    setEditingLabRequest(request);
                    openOrderDialog('lab');
                }}
                onDeleteLabRequest={(request) =>
                    deleteVisitOrder(
                        `/visits/${visit.id}/lab-requests/${request.id}`,
                        'Are you sure you want to remove this lab request?',
                    )
                }
                onEditPrescription={(prescription) => {
                    setEditingPrescription(prescription);
                    openOrderDialog('prescriptions');
                }}
                onDeletePrescription={(prescription) =>
                    deleteVisitOrder(
                        `/visits/${visit.id}/prescriptions/${prescription.id}`,
                        'Are you sure you want to remove this prescription?',
                    )
                }
                onEditImagingRequest={(request) => {
                    setEditingImagingRequest(request);
                    openOrderDialog('imaging');
                }}
                onDeleteImagingRequest={(request) =>
                    deleteVisitOrder(
                        `/visits/${visit.id}/imaging-requests/${request.id}`,
                        'Are you sure you want to remove this imaging request?',
                    )
                }
                onEditServiceOrder={(order) => {
                    setEditingServiceOrder(order);
                    openOrderDialog('services');
                }}
                onDeleteServiceOrder={(order) =>
                    deleteVisitOrder(
                        `/visits/${visit.id}/facility-service-orders/${order.id}`,
                        'Are you sure you want to remove this service order?',
                    )
                }
            />

            <LabOrderModal
                open={labModalOpen}
                onOpenChange={setLabModalOpen}
                visit={visit as any}
                labRequest={editingLabRequest}
                labTestOptions={labTestOptions}
                labPriorities={labPriorities}
                redirectTo="visit"
            />
            <PrescriptionOrderModal
                open={prescriptionModalOpen}
                onOpenChange={setPrescriptionModalOpen}
                visit={visit as any}
                prescription={editingPrescription}
                drugOptions={drugOptions}
                redirectTo="visit"
            />
            <ImagingOrderModal
                open={imagingModalOpen}
                onOpenChange={setImagingModalOpen}
                visit={visit as any}
                imagingRequest={editingImagingRequest}
                imagingModalities={imagingModalities}
                imagingPriorities={imagingPriorities}
                imagingLateralities={imagingLateralities}
                pregnancyStatuses={pregnancyStatuses}
                redirectTo="visit"
            />
            <ServiceOrderModal
                open={serviceOrderModalOpen}
                onOpenChange={setServiceOrderModalOpen}
                visit={visit as any}
                serviceOrder={editingServiceOrder}
                facilityServiceOptions={facilityServiceOptions}
                redirectTo="visit"
            />
            <AllergenModal
                open={allergenModalOpen}
                onOpenChange={setAllergenModalOpen}
                patientId={visit.patient?.id || ''}
                allergens={allergens}
                severityOptions={severityOptions}
                reactionOptions={reactionOptions}
            />
        </div>
    );
}
