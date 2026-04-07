import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import type { InventoryNavigationContext } from '@/types/inventory-navigation';
import type { InventoryRequisition } from '@/types/inventory-requisition';

import type { ApproveForm, RejectForm } from './types';

type Props = {
    navigation: InventoryNavigationContext;
    requisition: InventoryRequisition;
    canReview: boolean;
    approveForm: ApproveForm;
    rejectForm: RejectForm;
    onApproveLineChange: (index: number, value: string) => void;
};

export function RequisitionReviewPanel({
    navigation,
    requisition,
    canReview,
    approveForm,
    rejectForm,
    onApproveLineChange,
}: Props) {
    const lines = requisition.items ?? [];

    if (!canReview || (!requisition.can_approve && !requisition.can_reject)) {
        return null;
    }

    return (
        <>
            {requisition.can_approve ? (
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        approveForm.post(
                            `${navigation.requisitions_href}/${requisition.id}/approve`,
                        );
                    }}
                >
                    <div>
                        <h2 className="text-lg font-medium">
                            Review Requested Quantities
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Confirm the quantity each line is allowed to issue
                            from the main store.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead className="text-right">
                                        Requested
                                    </TableHead>
                                    <TableHead className="w-48 text-right">
                                        Approved
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lines.map((line, index) => (
                                    <TableRow key={line.id}>
                                        <TableCell className="font-medium">
                                            {line.inventory_item
                                                ?.generic_name ??
                                                line.inventory_item?.name ??
                                                '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {line.requested_quantity.toFixed(3)}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Input
                                                type="number"
                                                step="any"
                                                min="0"
                                                value={
                                                    approveForm.data.items[
                                                        index
                                                    ]?.approved_quantity ?? ''
                                                }
                                                onChange={(event) =>
                                                    onApproveLineChange(
                                                        index,
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    approveForm.errors[
                                                        `items.${index}.approved_quantity` as keyof typeof approveForm.errors
                                                    ]
                                                }
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="approval_notes">Approval Notes</Label>
                        <Textarea
                            id="approval_notes"
                            rows={3}
                            value={approveForm.data.approval_notes}
                            onChange={(event) =>
                                approveForm.setData(
                                    'approval_notes',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={approveForm.errors.approval_notes}
                        />
                    </div>

                    <Button size="sm" type="submit">
                        Approve Requisition
                    </Button>
                </form>
            ) : null}

            {requisition.can_reject ? (
                <form
                    className="grid gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        rejectForm.post(
                            `${navigation.requisitions_href}/${requisition.id}/reject`,
                        );
                    }}
                >
                    <Label htmlFor="rejection_reason">Rejection Reason</Label>
                    <Input
                        id="rejection_reason"
                        value={rejectForm.data.rejection_reason}
                        onChange={(event) =>
                            rejectForm.setData(
                                'rejection_reason',
                                event.target.value,
                            )
                        }
                    />
                    <InputError message={rejectForm.errors.rejection_reason} />
                    <Button size="sm" variant="destructive" type="submit">
                        Reject Requisition
                    </Button>
                </form>
            ) : null}
        </>
    );
}
