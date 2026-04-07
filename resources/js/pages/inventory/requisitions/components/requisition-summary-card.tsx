import { Badge } from '@/components/ui/badge';
import { formatDate, formatDateTime } from '@/lib/date';
import type { InventoryRequisition } from '@/types/inventory-requisition';

const badgeVariant = (
    status: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'fulfilled') {
        return 'default';
    }

    if (status === 'rejected' || status === 'cancelled') {
        return 'destructive';
    }

    if (status === 'approved' || status === 'partially_issued') {
        return 'outline';
    }

    return 'secondary';
};

const workflowMessage = (
    status: string | null,
    isRequesterWorkspace: boolean,
): string => {
    if (isRequesterWorkspace) {
        switch (status) {
            case 'draft':
                return 'This requisition is still a draft. Submit it when you are ready for main store review.';
            case 'submitted':
                return 'Main store has received this requisition and can now review the requested quantities. You can still withdraw it here before review if needed.';
            case 'approved':
                return 'Main store approved this requisition. Stock can now be issued against the approved quantities.';
            case 'partially_issued':
                return 'Main store has issued part of this requisition. The remaining approved quantities are still pending.';
            case 'fulfilled':
                return 'Main store has fully issued this requisition.';
            case 'rejected':
                return 'Main store rejected this requisition. Check the rejection reason before raising another request.';
            case 'cancelled':
                return 'This requisition was cancelled and will not move forward.';
            default:
                return 'Follow this requisition here as it moves from your unit to the main store workflow.';
        }
    }

    switch (status) {
        case 'submitted':
            return 'This incoming requisition is ready for main store review. Approve allowed quantities or reject it.';
        case 'approved':
            return 'This incoming requisition has been approved and is waiting for stock issue from the main store.';
        case 'partially_issued':
            return 'This incoming requisition has been partly issued. Complete the remaining approved quantities when available.';
        case 'fulfilled':
            return 'This incoming requisition has been fully issued.';
        case 'rejected':
            return 'This incoming requisition has been rejected and closed.';
        case 'cancelled':
            return 'This incoming requisition was cancelled before fulfillment.';
        case 'draft':
            return 'This requisition is still in draft and has not yet reached the incoming main store queue.';
        default:
            return 'Review the request, confirm what the main store can support, then issue available stock.';
    }
};

type Props = {
    requisition: InventoryRequisition;
    isRequesterWorkspace: boolean;
};

export function RequisitionSummaryCard({
    requisition,
    isRequesterWorkspace,
}: Props) {
    return (
        <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div className="grid gap-4 md:grid-cols-4">
                <div>
                    <span className="text-sm text-muted-foreground">
                        Status
                    </span>
                    <div className="mt-1">
                        <Badge variant={badgeVariant(requisition.status)}>
                            {requisition.status_label ?? '-'}
                        </Badge>
                    </div>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">
                        Priority
                    </span>
                    <p className="mt-1 font-medium">
                        {requisition.priority_label ?? '-'}
                    </p>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">Date</span>
                    <p className="mt-1 font-medium">
                        {formatDate(requisition.requisition_date)}
                    </p>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">
                        Issued At
                    </span>
                    <p className="mt-1 font-medium">
                        {formatDateTime(requisition.issued_at)}
                    </p>
                </div>
            </div>

            <div className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-2">
                <div>
                    <span className="text-sm text-muted-foreground">
                        Fulfilling Store
                    </span>
                    <p className="mt-1 font-medium">
                        {requisition.fulfilling_location?.name ?? '-'}
                    </p>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">
                        Requesting Unit
                    </span>
                    <p className="mt-1 font-medium">
                        {requisition.requesting_location?.name ?? '-'}
                    </p>
                </div>
            </div>

            <div className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-4">
                <div>
                    <span className="text-sm text-muted-foreground">Notes</span>
                    <p className="mt-1">{requisition.notes ?? '-'}</p>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">
                        Approval Notes
                    </span>
                    <p className="mt-1">{requisition.approval_notes ?? '-'}</p>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">
                        Rejection Reason
                    </span>
                    <p className="mt-1">
                        {requisition.rejection_reason ?? '-'}
                    </p>
                </div>
                <div>
                    <span className="text-sm text-muted-foreground">
                        Cancellation Reason
                    </span>
                    <p className="mt-1">
                        {requisition.cancellation_reason ?? '-'}
                    </p>
                </div>
            </div>

            <div className="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm text-muted-foreground dark:border-zinc-800 dark:bg-zinc-950/50">
                <span className="font-medium text-foreground">
                    {isRequesterWorkspace
                        ? 'Main Store Handling'
                        : 'Incoming Queue Guidance'}
                </span>
                <p className="mt-1">
                    {workflowMessage(requisition.status, isRequesterWorkspace)}
                </p>
            </div>
        </div>
    );
}
