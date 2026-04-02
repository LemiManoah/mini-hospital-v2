import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import {
    type FacilityServiceOrder,
    type ImagingRequest,
    type LabRequest,
    type Prescription,
} from '@/types/patient';
import { Trash2 } from 'lucide-react';

export type OrderTabValue = 'lab' | 'prescriptions' | 'imaging' | 'services';

export const ORDER_TAB_LABELS: Record<OrderTabValue, string> = {
    lab: 'Lab',
    prescriptions: 'Prescriptions',
    imaging: 'Imaging',
    services: 'Services',
};

export const formatMoney = (amount: number | null | undefined): string =>
    amount === null || amount === undefined
        ? 'Not priced'
        : new Intl.NumberFormat('en-US', {
              style: 'currency',
              currency: 'UGX',
              maximumFractionDigits: 0,
          }).format(amount);

export const formatDateTime = (date: string | null | undefined): string =>
    date
        ? new Date(date).toLocaleString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'N/A';

export const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

export const staffName = (
    staff?: { first_name: string; last_name: string } | null,
): string => (staff ? `${staff.first_name} ${staff.last_name}` : 'Unknown');

const statusBadgeClasses = (status: string): string =>
    ({
        requested: 'bg-amber-100 text-amber-900',
        pending: 'bg-amber-100 text-amber-900',
        in_progress: 'bg-blue-100 text-blue-900',
        completed: 'bg-emerald-100 text-emerald-900',
        fully_dispensed: 'bg-emerald-100 text-emerald-900',
        scheduled: 'bg-sky-100 text-sky-900',
        cancelled: 'bg-zinc-200 text-zinc-900',
        rejected: 'bg-rose-100 text-rose-900',
    })[status] ?? 'bg-zinc-100 text-zinc-800';

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

export function OrderSectionHeader({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action?: React.ReactNode;
}) {
    return (
        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="flex flex-col gap-1">
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </div>
            {action}
        </CardHeader>
    );
}

export function OrderAccessMessage({
    canManageOrders,
    isConsultationFinalized,
}: {
    canManageOrders: boolean;
    isConsultationFinalized: boolean;
}) {
    if (isConsultationFinalized) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                This visit already has a finalized consultation, so ordering is
                now read-only from here.
            </div>
        );
    }

    if (!canManageOrders) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                Orders are visible here, but you do not have permission to add
                or change them from this workspace.
            </div>
        );
    }

    return null;
}

export function LabOrdersList({
    labRequests,
    emptyMessage,
}: {
    labRequests: LabRequest[];
    emptyMessage: string;
}) {
    if (labRequests.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
    }

    return (
        <div className="flex flex-col gap-4">
            {labRequests.map((request) => (
                <div key={request.id} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-medium">
                            {request.items
                                .map((item) => item.test?.test_name)
                                .filter(Boolean)
                                .join(', ') || 'Lab request'}
                        </h3>
                        <Badge
                            className={cn(
                                'border-0',
                                statusBadgeClasses(request.status),
                            )}
                        >
                            {labelize(request.status)}
                        </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ordered by {staffName(request.requestedBy)} on{' '}
                        {formatDateTime(request.request_date)}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Priority: {labelize(request.priority)}
                        {request.is_stat ? ' | STAT' : ''}
                    </p>
                    {request.clinical_notes ? (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Clinical notes: {request.clinical_notes}
                        </p>
                    ) : null}
                    <div className="mt-4 flex flex-col gap-3">
                        {request.items.map((item) => {
                            const releasedValues = labItemResultValues(item);

                            return (
                                <div
                                    key={item.id}
                                    className="rounded-lg border bg-muted/30 p-3"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">
                                            {item.test?.test_name ?? 'Lab test'}
                                        </p>
                                        <Badge
                                            className={cn(
                                                'border-0',
                                                statusBadgeClasses(item.status),
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
                                        <div className="mt-3 flex flex-col gap-2">
                                            {releasedValues.map((value) => (
                                                <div
                                                    key={value.id}
                                                    className="rounded-md border bg-background p-3"
                                                >
                                                    <p className="text-sm text-muted-foreground">
                                                        {value.label}
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
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Result not yet released.
                                        </p>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            ))}
        </div>
    );
}

export function PrescriptionOrdersList({
    prescriptions,
    emptyMessage,
}: {
    prescriptions: Prescription[];
    emptyMessage: string;
}) {
    if (prescriptions.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
    }

    return (
        <div className="flex flex-col gap-4">
            {prescriptions.map((prescription) => (
                <div key={prescription.id} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-medium">
                            {prescription.primary_diagnosis || 'Prescription'}
                        </h3>
                        <Badge
                            className={cn(
                                'border-0',
                                statusBadgeClasses(prescription.status),
                            )}
                        >
                            {labelize(prescription.status)}
                        </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Prescribed by {staffName(prescription.prescribedBy)} on{' '}
                        {formatDateTime(prescription.prescription_date)}
                    </p>
                    <div className="mt-3 flex flex-col gap-2 text-sm">
                        {prescription.items.map((item) => (
                            <div
                                key={item.id}
                                className="rounded-md bg-muted/40 px-3 py-2"
                            >
                                <p className="font-medium">
                                    {item.inventory_item?.generic_name ??
                                        'Drug'}
                                    {item.inventory_item?.brand_name
                                        ? ` (${item.inventory_item.brand_name})`
                                        : ''}
                                </p>
                                <p className="text-muted-foreground">
                                    {item.dosage} | {item.frequency} |{' '}
                                    {item.route} | {item.duration_days} day(s) |
                                    Qty {item.quantity}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

export function ImagingOrdersList({
    imagingRequests,
    emptyMessage,
}: {
    imagingRequests: ImagingRequest[];
    emptyMessage: string;
}) {
    if (imagingRequests.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
    }

    return (
        <div className="flex flex-col gap-4">
            {imagingRequests.map((request) => (
                <div key={request.id} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-medium">
                            {labelize(request.modality)} {request.body_part}
                        </h3>
                        <Badge
                            className={cn(
                                'border-0',
                                statusBadgeClasses(request.status),
                            )}
                        >
                            {labelize(request.status)}
                        </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ordered by {staffName(request.requestedBy)} | Priority{' '}
                        {labelize(request.priority)}
                    </p>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Clinical history: {request.clinical_history}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Indication: {request.indication}
                    </p>
                </div>
            ))}
        </div>
    );
}

export function FacilityServiceOrdersList({
    orders,
    emptyMessage,
    canRemovePending = false,
    onRemovePending,
}: {
    orders: FacilityServiceOrder[];
    emptyMessage: string;
    canRemovePending?: boolean;
    onRemovePending?: (order: FacilityServiceOrder) => void;
}) {
    if (orders.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
    }

    return (
        <div className="flex flex-col gap-4">
            {orders.map((order) => (
                <div key={order.id} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-medium">
                            {order.service?.name ?? 'Facility service'}
                        </h3>
                        <Badge
                            className={cn(
                                'border-0',
                                statusBadgeClasses(order.status),
                            )}
                        >
                            {labelize(order.status)}
                        </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ordered by {staffName(order.orderedBy)} on{' '}
                        {formatDateTime(order.ordered_at)}
                    </p>
                    {order.completed_at ? (
                        <p className="text-sm text-muted-foreground">
                            Completed: {formatDateTime(order.completed_at)}
                        </p>
                    ) : null}
                    <p className="text-sm text-muted-foreground">
                        Quoted price:{' '}
                        {formatMoney(
                            order.service?.quoted_price ??
                                order.service?.selling_price ??
                                null,
                        )}
                    </p>
                    {canRemovePending &&
                    order.status === 'pending' &&
                    onRemovePending ? (
                        <div className="mt-3 flex justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => onRemovePending(order)}
                            >
                                <Trash2 data-icon="inline-start" />
                                Remove Pending Order
                            </Button>
                        </div>
                    ) : null}
                </div>
            ))}
        </div>
    );
}
