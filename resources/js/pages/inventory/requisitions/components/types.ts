import type { InertiaFormProps } from '@inertiajs/react';

export type ApproveLine = {
    inventory_requisition_item_id: string;
    approved_quantity: string;
};

export type AllocationLine = {
    inventory_batch_id: string;
    quantity: string;
};

export type IssueLine = {
    inventory_requisition_item_id: string;
    issue_quantity: string;
    notes: string;
    allocations: AllocationLine[];
};

export type SubmitForm = InertiaFormProps<Record<string, never>>;

export type ApproveForm = InertiaFormProps<{
    approval_notes: string;
    items: ApproveLine[];
}>;

export type RejectForm = InertiaFormProps<{
    rejection_reason: string;
}>;

export type CancelForm = InertiaFormProps<{
    cancellation_reason: string;
}>;

export type IssueForm = InertiaFormProps<{
    issued_notes: string;
    items: IssueLine[];
}>;
