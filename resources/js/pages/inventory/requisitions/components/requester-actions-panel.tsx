import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InventoryNavigationContext } from '@/types/inventory-navigation';
import type { InventoryRequisition } from '@/types/inventory-requisition';

import type { CancelForm, SubmitForm } from './types';

type Props = {
    navigation: InventoryNavigationContext;
    requisition: InventoryRequisition;
    canSubmitToMainStore: boolean;
    canCancelRequest: boolean;
    submitForm: SubmitForm;
    cancelForm: CancelForm;
};

export function RequesterActionsPanel({
    navigation,
    requisition,
    canSubmitToMainStore,
    canCancelRequest,
    submitForm,
    cancelForm,
}: Props) {
    if (!canSubmitToMainStore && !canCancelRequest) {
        return null;
    }

    return (
        <>
            {canSubmitToMainStore ? (
                <div className="mt-4 space-y-3 border-t pt-4">
                    <p className="text-sm text-muted-foreground">
                        Submit this draft when it is ready for the main store to
                        review and issue.
                    </p>
                    <Button
                        size="sm"
                        onClick={() =>
                            submitForm.post(
                                `${navigation.requisitions_href}/${requisition.id}/submit`,
                            )
                        }
                    >
                        Submit To Main Store
                    </Button>
                </div>
            ) : null}

            {canCancelRequest ? (
                <form
                    className="mt-4 space-y-3 border-t pt-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        cancelForm.post(
                            `${navigation.requisitions_href}/${requisition.id}/cancel`,
                        );
                    }}
                >
                    <p className="text-sm text-muted-foreground">
                        Cancel this requisition if it should no longer continue
                        to main store review or fulfillment.
                    </p>
                    <div className="grid gap-2">
                        <Label htmlFor="cancellation_reason">
                            Cancellation Reason
                        </Label>
                        <Input
                            id="cancellation_reason"
                            value={cancelForm.data.cancellation_reason}
                            onChange={(event) =>
                                cancelForm.setData(
                                    'cancellation_reason',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={cancelForm.errors.cancellation_reason}
                        />
                    </div>
                    <Button size="sm" variant="destructive" type="submit">
                        Cancel Requisition
                    </Button>
                </form>
            ) : null}
        </>
    );
}
