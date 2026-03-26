import { VisitOrderDialog } from '@/components/visit-order-dialog';
import {
    FacilityServiceOrdersList,
    ImagingOrdersList,
    LabOrdersList,
    type OrderTabValue,
    OrderAccessMessage,
    OrderSectionHeader,
    PrescriptionOrdersList,
} from '@/components/visit-ordering';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    type DrugOption,
    type FacilityServiceOption,
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
    type VitalSign,
} from '@/types/patient';
import { router, Link } from '@inertiajs/react';
import { ClipboardPlus, HeartPulse, NotebookPen, Plus } from 'lucide-react';
import { useState } from 'react';
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
    activeOrderTab: string;
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
};

const toOrderTab = (value: string): OrderTabValue =>
    ['lab', 'prescriptions', 'imaging', 'services'].includes(value)
        ? (value as OrderTabValue)
        : 'lab';

export function VisitClinicalTab({
    visit,
    triage,
    consultation,
    triageGrades,
    canViewTriage,
    canViewConsultation,
    canManageOrders,
    activeOrderTab,
    labTestOptions,
    drugOptions,
    labPriorities,
    imagingModalities,
    imagingPriorities,
    imagingLateralities,
    pregnancyStatuses,
    facilityServiceOptions,
}: VisitClinicalTabProps) {
    const latestVital = (triage?.vitalSigns ?? triage?.vital_signs ?? [])[0];
    const labRequests = visit.labRequests ?? visit.lab_requests ?? [];
    const prescriptions = visit.prescriptions ?? [];
    const imagingRequests =
        visit.imagingRequests ?? visit.imaging_requests ?? [];
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];
    const isConsultationFinalized = consultation?.completed_at != null;
    const [selectedOrderTab, setSelectedOrderTab] = useState<OrderTabValue>(
        toOrderTab(activeOrderTab),
    );
    const [orderDialogOpen, setOrderDialogOpen] = useState(false);
    const openOrderDialog = (tab: OrderTabValue) => {
        setSelectedOrderTab(tab);
        setOrderDialogOpen(true);
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
                            <Button asChild>
                                <Link href={`/triage/${visit.id}`}>
                                    <HeartPulse data-icon="inline-start" />
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
                                            <NotebookPen data-icon="inline-start" />
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
                                            <ClipboardPlus data-icon="inline-start" />
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
                <OrderSectionHeader
                    title="Visit Orders"
                    description="Use the order center to add lab tests, imaging, prescriptions, or services during this visit."
                    action={
                        canManageOrders && !isConsultationFinalized ? (
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => openOrderDialog('lab')}
                                >
                                    <Plus data-icon="inline-start" />
                                    Order Lab
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        openOrderDialog('prescriptions')
                                    }
                                >
                                    <Plus data-icon="inline-start" />
                                    Add Prescription
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => openOrderDialog('imaging')}
                                >
                                    <Plus data-icon="inline-start" />
                                    Order Imaging
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => openOrderDialog('services')}
                                >
                                    <Plus data-icon="inline-start" />
                                    Order Service
                                </Button>
                            </div>
                        ) : null
                    }
                />
                <CardContent className="space-y-6">
                    <OrderAccessMessage
                        canManageOrders={canManageOrders}
                        isConsultationFinalized={isConsultationFinalized}
                    />

                    <Tabs
                        value={selectedOrderTab}
                        onValueChange={(value) =>
                            setSelectedOrderTab(value as OrderTabValue)
                        }
                        className="space-y-4"
                    >
                        <TabsList variant="line" className="w-full justify-start">
                            <TabsTrigger value="lab">Lab</TabsTrigger>
                            <TabsTrigger value="prescriptions">
                                Prescriptions
                            </TabsTrigger>
                            <TabsTrigger value="imaging">Imaging</TabsTrigger>
                            <TabsTrigger value="services">Services</TabsTrigger>
                        </TabsList>

                        <TabsContent value="lab">
                            <LabOrdersList
                                labRequests={labRequests}
                                emptyMessage="No lab requests recorded for this visit yet."
                            />
                        </TabsContent>

                        <TabsContent value="prescriptions">
                            <PrescriptionOrdersList
                                prescriptions={prescriptions}
                                emptyMessage="No prescriptions recorded for this visit yet."
                            />
                        </TabsContent>

                        <TabsContent value="imaging">
                            <ImagingOrdersList
                                imagingRequests={imagingRequests}
                                emptyMessage="No imaging requests recorded for this visit yet."
                            />
                        </TabsContent>

                        <TabsContent value="services">
                            <FacilityServiceOrdersList
                                orders={facilityServiceOrders}
                                emptyMessage="No facility service orders recorded for this visit yet."
                                canRemovePending={
                                    canManageOrders && !isConsultationFinalized
                                }
                                onRemovePending={(order) =>
                                    router.delete(
                                        `/visits/${visit.id}/facility-service-orders/${order.id}`,
                                        {
                                            data: { redirect_to: 'visit' },
                                            preserveScroll: true,
                                        },
                                    )
                                }
                            />
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>

            <VisitOrderDialog
                open={orderDialogOpen}
                onOpenChange={setOrderDialogOpen}
                initialTab={selectedOrderTab}
                redirectTo="visit"
                visit={visit}
                labTestOptions={labTestOptions}
                drugOptions={drugOptions}
                labPriorities={labPriorities}
                imagingModalities={imagingModalities}
                imagingPriorities={imagingPriorities}
                imagingLateralities={imagingLateralities}
                pregnancyStatuses={pregnancyStatuses}
                facilityServiceOptions={facilityServiceOptions}
            />
        </div>
    );
}
