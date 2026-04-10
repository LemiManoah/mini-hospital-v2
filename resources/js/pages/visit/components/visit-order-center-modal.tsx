import { ImagingOrdersTable } from '@/components/orders/imaging-orders-table';
import { LabOrdersTable } from '@/components/orders/lab-orders-table';
import { PrescriptionOrdersTable } from '@/components/orders/prescription-orders-table';
import { ServiceOrdersTable } from '@/components/orders/service-orders-table';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { OrderAccessMessage } from '@/components/visit-ordering';
import {
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
} from '@/types/patient';
import { type ReactNode } from 'react';

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
        <section className="space-y-4 rounded-xl border p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 className="font-semibold">{title}</h3>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
                {action}
            </div>
            <div className="overflow-x-auto">{children}</div>
        </section>
    );
}

type VisitOrderCenterModalProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    canManageOrders: boolean;
    isConsultationFinalized: boolean;
    labRequests: LabRequest[];
    prescriptions: Prescription[];
    imagingRequests: ImagingRequest[];
    serviceOrders: FacilityServiceOrder[];
    onRecordAllergy: () => void;
    onOrderLab: () => void;
    onOrderPrescription: () => void;
    onOrderImaging: () => void;
    onOrderService: () => void;
    onEditLabRequest: (request: LabRequest) => void;
    onDeleteLabRequest: (request: LabRequest) => void;
    onEditPrescription: (prescription: Prescription) => void;
    onDeletePrescription: (prescription: Prescription) => void;
    onEditImagingRequest: (request: ImagingRequest) => void;
    onDeleteImagingRequest: (request: ImagingRequest) => void;
    onEditServiceOrder: (order: FacilityServiceOrder) => void;
    onDeleteServiceOrder: (order: FacilityServiceOrder) => void;
};

export function VisitOrderCenterModal({
    open,
    onOpenChange,
    canManageOrders,
    isConsultationFinalized,
    labRequests,
    prescriptions,
    imagingRequests,
    serviceOrders,
    onRecordAllergy,
    onOrderLab,
    onOrderPrescription,
    onOrderImaging,
    onOrderService,
    onEditLabRequest,
    onDeleteLabRequest,
    onEditPrescription,
    onDeletePrescription,
    onEditImagingRequest,
    onDeleteImagingRequest,
    onEditServiceOrder,
    onDeleteServiceOrder,
}: VisitOrderCenterModalProps) {
    const canEditOrders = canManageOrders && !isConsultationFinalized;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-hidden sm:max-w-6xl">
                <DialogHeader>
                    <DialogTitle>Visit Order Center</DialogTitle>
                    <DialogDescription>
                        Review current orders and place new prescriptions, lab
                        investigations, imaging studies, or other service
                        requests from one workspace.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 overflow-y-auto pr-1">
                    <div className="flex flex-wrap justify-end gap-2">
                        {canEditOrders ? (
                            <Button variant="outline" onClick={onRecordAllergy}>
                                Record Allergy
                            </Button>
                        ) : null}
                    </div>

                    <OrderAccessMessage
                        canManageOrders={canManageOrders}
                        isConsultationFinalized={isConsultationFinalized}
                    />

                    <OrderSection
                        title="Prescriptions"
                        description="Medication orders for this visit."
                        action={
                            canEditOrders ? (
                                <Button
                                    variant="outline"
                                    onClick={onOrderPrescription}
                                >
                                    Add Prescription
                                </Button>
                            ) : null
                        }
                    >
                        <PrescriptionOrdersTable
                            prescriptions={prescriptions}
                            canManageOrders={canEditOrders}
                            onEdit={onEditPrescription}
                            onDelete={onDeletePrescription}
                        />
                    </OrderSection>

                    <OrderSection
                        title="Laboratory Investigations"
                        description="Tests requested for this encounter."
                        action={
                            canEditOrders ? (
                                <Button variant="outline" onClick={onOrderLab}>
                                    Order Investigation
                                </Button>
                            ) : null
                        }
                    >
                        <LabOrdersTable
                            labRequests={labRequests}
                            canManageOrders={canEditOrders}
                            onEdit={onEditLabRequest}
                            onDelete={onDeleteLabRequest}
                        />
                    </OrderSection>

                    <OrderSection
                        title="Imaging"
                        description="Radiology and other imaging requests."
                        action={
                            canEditOrders ? (
                                <Button
                                    variant="outline"
                                    onClick={onOrderImaging}
                                >
                                    Order Imaging
                                </Button>
                            ) : null
                        }
                    >
                        <ImagingOrdersTable
                            imagingRequests={imagingRequests}
                            canManageOrders={canEditOrders}
                            onEdit={onEditImagingRequest}
                            onDelete={onDeleteImagingRequest}
                        />
                    </OrderSection>

                    <OrderSection
                        title="Other Services"
                        description="Billable and non-billable service orders."
                        action={
                            canEditOrders ? (
                                <Button
                                    variant="outline"
                                    onClick={onOrderService}
                                >
                                    Order Service
                                </Button>
                            ) : null
                        }
                    >
                        <ServiceOrdersTable
                            orders={serviceOrders}
                            canManageOrders={canEditOrders}
                            onEdit={onEditServiceOrder}
                            onDelete={onDeleteServiceOrder}
                        />
                    </OrderSection>
                </div>
            </DialogContent>
        </Dialog>
    );
}
