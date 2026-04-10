import { AllergenModal } from '@/components/allergen-modal';
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
import { OrderAccessMessage } from '@/components/visit-ordering';
import {
    type DrugOption,
    type FacilityServiceOption,
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
} from '@/types/patient';
import { router } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';

type VisitOrdersTabProps = {
    visit: {
        id: string;
        patient?: { id: string } | null;
        consultation?: { completed_at?: string | null } | null;
        labRequests?: LabRequest[] | null;
        lab_requests?: LabRequest[] | null;
        prescriptions?: Prescription[] | null;
        imagingRequests?: ImagingRequest[] | null;
        imaging_requests?: ImagingRequest[] | null;
        facilityServiceOrders?: FacilityServiceOrder[] | null;
        facility_service_orders?: FacilityServiceOrder[] | null;
    };
    consultation?: { completed_at?: string | null } | null;
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

type OrderSectionProps = {
    title: string;
    description: string;
    action?: ReactNode;
    children: ReactNode;
};

function OrderSection({
    title,
    description,
    action,
    children,
}: OrderSectionProps) {
    return (
        <Card>
            <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <CardTitle>{title}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
                {action}
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">{children}</div>
            </CardContent>
        </Card>
    );
}

export function VisitOrdersTab({
    visit,
    consultation,
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
}: VisitOrdersTabProps) {
    const labRequests = visit.labRequests ?? visit.lab_requests ?? [];
    const prescriptions = visit.prescriptions ?? [];
    const imagingRequests =
        visit.imagingRequests ?? visit.imaging_requests ?? [];
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];
    const isConsultationFinalized =
        (consultation?.completed_at ?? visit.consultation?.completed_at) !=
        null;
    const canEditOrders = canManageOrders && !isConsultationFinalized;

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
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <CardTitle>Visit Services</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Manage medications, investigations, imaging, and
                            other services from one workspace.
                        </p>
                    </div>
                    {canEditOrders ? (
                        <Button
                            variant="outline"
                            onClick={() => setAllergenModalOpen(true)}
                        >
                            Record Allergy
                        </Button>
                    ) : null}
                </CardHeader>
                <CardContent>
                    <OrderAccessMessage
                        canManageOrders={canManageOrders}
                        isConsultationFinalized={isConsultationFinalized}
                    />
                </CardContent>
            </Card>

            <OrderSection
                title="Prescriptions"
                description="Medication orders for this visit."
                action={
                    canEditOrders ? (
                        <Button
                            onClick={() => {
                                setEditingPrescription(null);
                                setPrescriptionModalOpen(true);
                            }}
                        >
                            Order Prescription
                        </Button>
                    ) : null
                }
            >
                <PrescriptionOrdersTable
                    prescriptions={prescriptions}
                    canManageOrders={canEditOrders}
                    onEdit={(prescription) => {
                        setEditingPrescription(prescription);
                        setPrescriptionModalOpen(true);
                    }}
                    onDelete={(prescription) =>
                        deleteVisitOrder(
                            `/visits/${visit.id}/prescriptions/${prescription.id}`,
                            'Are you sure you want to remove this prescription?',
                        )
                    }
                />
            </OrderSection>

            <OrderSection
                title="Laboratory Investigations"
                description="Lab requests for this encounter."
                action={
                    canEditOrders ? (
                        <Button
                            onClick={() => {
                                setEditingLabRequest(null);
                                setLabModalOpen(true);
                            }}
                        >
                            Order Lab
                        </Button>
                    ) : null
                }
            >
                <LabOrdersTable
                    labRequests={labRequests}
                    canManageOrders={canEditOrders}
                    onEdit={(request) => {
                        setEditingLabRequest(request);
                        setLabModalOpen(true);
                    }}
                    onDelete={(request) =>
                        deleteVisitOrder(
                            `/visits/${visit.id}/lab-requests/${request.id}`,
                            'Are you sure you want to remove this lab request?',
                        )
                    }
                    onDeleteItem={(request, item) =>
                        deleteVisitOrder(
                            `/visits/${visit.id}/lab-requests/${request.id}/items/${item.id}`,
                            'Are you sure you want to remove this test from the lab request?',
                        )
                    }
                />
            </OrderSection>

            <OrderSection
                title="Imaging"
                description="Radiology and imaging requests for this visit."
                action={
                    canEditOrders ? (
                        <Button
                            onClick={() => {
                                setEditingImagingRequest(null);
                                setImagingModalOpen(true);
                            }}
                        >
                            Order Imaging
                        </Button>
                    ) : null
                }
            >
                <ImagingOrdersTable
                    imagingRequests={imagingRequests}
                    canManageOrders={canEditOrders}
                    onEdit={(request) => {
                        setEditingImagingRequest(request);
                        setImagingModalOpen(true);
                    }}
                    onDelete={(request) =>
                        deleteVisitOrder(
                            `/visits/${visit.id}/imaging-requests/${request.id}`,
                            'Are you sure you want to remove this imaging request?',
                        )
                    }
                />
            </OrderSection>

            <OrderSection
                title="Other Services"
                description="Service requests for procedures and non-medication work."
                action={
                    canEditOrders ? (
                        <Button
                            onClick={() => {
                                setEditingServiceOrder(null);
                                setServiceOrderModalOpen(true);
                            }}
                        >
                            Order Service
                        </Button>
                    ) : null
                }
            >
                <ServiceOrdersTable
                    orders={facilityServiceOrders}
                    canManageOrders={canEditOrders}
                    onEdit={(order) => {
                        setEditingServiceOrder(order);
                        setServiceOrderModalOpen(true);
                    }}
                    onDelete={(order) =>
                        deleteVisitOrder(
                            `/visits/${visit.id}/facility-service-orders/${order.id}`,
                            'Are you sure you want to remove this service order?',
                        )
                    }
                />
            </OrderSection>

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
