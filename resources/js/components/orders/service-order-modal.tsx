import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    type FacilityServiceOption,
    type FacilityServiceOrder,
    type PatientVisit,
} from '@/types/patient';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { formatMoney, labelize } from '../visit-ordering';

export function ServiceOrderModal({
    open,
    onOpenChange,
    visit,
    serviceOrder,
    facilityServiceOptions,
    redirectTo,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    visit: Pick<
        PatientVisit,
        'id' | 'facilityServiceOrders' | 'facility_service_orders'
    >;
    serviceOrder?: FacilityServiceOrder | null;
    facilityServiceOptions: FacilityServiceOption[];
    redirectTo: 'visit' | 'consultation';
}) {
    const facilityServiceOrders =
        visit.facilityServiceOrders ?? visit.facility_service_orders ?? [];

    const form = useForm({
        facility_service_id: '',
        redirect_to: redirectTo,
    });

    useEffect(() => {
        if (open && serviceOrder) {
            form.setData({
                facility_service_id: serviceOrder.facility_service_id,
                redirect_to: redirectTo,
            });
        } else if (open && !serviceOrder) {
            form.reset();
        }
    }, [open, serviceOrder]);

    const selectedFacilityService = facilityServiceOptions.find(
        (option) => option.id === form.data.facility_service_id,
    );
    const facilityServiceSelectOptions = facilityServiceOptions.map(
        (option) => ({
            value: option.id,
            label: `${option.name}${option.service_code ? ` (${option.service_code})` : ''}`,
        }),
    );
    const pendingFacilityServiceIds = new Set(
        facilityServiceOrders
            .filter((order) => order.status === 'pending')
            .map((order) => order.facility_service_id),
    );
    const hasPendingSelectedFacilityService =
        form.data.facility_service_id !== '' &&
        pendingFacilityServiceIds.has(form.data.facility_service_id) &&
        (!serviceOrder ||
            serviceOrder.facility_service_id !== form.data.facility_service_id);

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (serviceOrder) {
            // Edit logic
        } else {
            form.post(`/visits/${visit.id}/facility-service-orders`, {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    onOpenChange(false);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-none bg-white shadow-2xl sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {serviceOrder
                            ? 'Edit Service Order'
                            : 'New Service Order'}
                    </DialogTitle>
                    <DialogDescription>
                        {serviceOrder
                            ? 'Update the details of this service order.'
                            : 'Order a facility service for this visit.'}
                    </DialogDescription>
                </DialogHeader>

                {facilityServiceOptions.length === 0 ? (
                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                        No facility services are active in the catalog yet.
                    </div>
                ) : (
                    <form className="flex flex-col gap-4" onSubmit={onSubmit}>
                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Facility Service</Label>
                                <SearchableSelect
                                    inputId="facility_service_id"
                                    options={facilityServiceSelectOptions}
                                    value={form.data.facility_service_id}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'facility_service_id',
                                            value,
                                        )
                                    }
                                    placeholder="Select service"
                                    emptyMessage="No services available."
                                    invalid={Boolean(
                                        form.errors.facility_service_id,
                                    )}
                                />
                                <InputError
                                    message={form.errors.facility_service_id}
                                />
                            </div>
                            <div className="rounded-lg border bg-muted/30 p-4">
                                <p className="text-sm text-muted-foreground">
                                    Service preview
                                </p>
                                <p className="mt-1 font-medium">
                                    {selectedFacilityService?.name ??
                                        'Choose a service to preview its billing details.'}
                                </p>
                                {selectedFacilityService ? (
                                    <>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Quoted price:{' '}
                                            {formatMoney(
                                                selectedFacilityService.quoted_price ??
                                                    selectedFacilityService.selling_price,
                                            )}
                                        </p>
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
                                    </>
                                ) : null}
                                {hasPendingSelectedFacilityService ? (
                                    <p className="mt-2 text-sm text-amber-700">
                                        This service already has a pending order
                                        for the visit.
                                    </p>
                                ) : null}
                            </div>
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    form.processing ||
                                    form.data.facility_service_id === '' ||
                                    hasPendingSelectedFacilityService
                                }
                            >
                                {serviceOrder
                                    ? 'Update Order'
                                    : 'Order Service'}
                            </Button>
                        </div>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
