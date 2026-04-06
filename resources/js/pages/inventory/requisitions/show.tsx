import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type InventoryRequisitionShowPageProps } from '@/types/inventory-requisition';
import { Head, Link, useForm } from '@inertiajs/react';

import { RequisitionIssuePanel } from './components/requisition-issue-panel';
import { RequisitionLinesTable } from './components/requisition-lines-table';
import { RequisitionReviewPanel } from './components/requisition-review-panel';
import { RequesterActionsPanel } from './components/requester-actions-panel';
import { RequisitionSummaryCard } from './components/requisition-summary-card';
import type {
    AllocationLine,
    ApproveLine,
    IssueLine,
} from './components/types';

const emptyAllocation = (): AllocationLine => ({
    inventory_batch_id: '',
    quantity: '',
});

export default function InventoryRequisitionShow({
    navigation,
    requisition,
    availableBatchBalances,
}: InventoryRequisitionShowPageProps) {
    const { hasPermission } = usePermissions();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.requisitions_title,
            href: navigation.requisitions_href,
        },
        {
            title: requisition.requisition_number,
            href: `${navigation.requisitions_href}/${requisition.id}`,
        },
    ];

    const isRequesterWorkspace = navigation.key !== 'inventory';
    const lines = requisition.items ?? [];
    const issueReadyLines = lines.filter((line) => line.remaining_quantity > 0);

    const submitForm = useForm<Record<string, never>>({});
    const approveForm = useForm<{
        approval_notes: string;
        items: ApproveLine[];
    }>({
        approval_notes: requisition.approval_notes ?? '',
        items: lines.map((line) => ({
            inventory_requisition_item_id: line.id,
            approved_quantity: line.requested_quantity.toFixed(3),
        })),
    });
    const rejectForm = useForm({
        rejection_reason: requisition.rejection_reason ?? '',
    });
    const cancelForm = useForm({
        cancellation_reason: requisition.cancellation_reason ?? '',
    });
    const issueForm = useForm<{
        issued_notes: string;
        items: IssueLine[];
    }>({
        issued_notes: requisition.issued_notes ?? '',
        items: issueReadyLines.map((line) => ({
            inventory_requisition_item_id: line.id,
            issue_quantity: '',
            notes: '',
            allocations: [],
        })),
    });

    const canSubmitToMainStore =
        isRequesterWorkspace &&
        hasPermission('inventory_requisitions.submit') &&
        requisition.can_submit &&
        navigation.key !== 'inventory';
    const canCancelRequest =
        isRequesterWorkspace &&
        hasPermission('inventory_requisitions.cancel') &&
        requisition.can_cancel &&
        navigation.key !== 'inventory';
    const canReview =
        navigation.key === 'inventory' &&
        hasPermission('inventory_requisitions.review');
    const canIssue =
        navigation.key === 'inventory' &&
        hasPermission('inventory_requisitions.issue');

    const updateApproveLine = (index: number, value: string) => {
        const updated = [...approveForm.data.items];
        updated[index] = {
            ...updated[index],
            approved_quantity: value,
        };
        approveForm.setData('items', updated);
    };

    const updateIssueLine = (
        index: number,
        field: 'issue_quantity' | 'notes',
        value: string,
    ) => {
        const updated = [...issueForm.data.items];
        updated[index] = {
            ...updated[index],
            [field]: value,
        };
        issueForm.setData('items', updated);
    };

    const addAllocation = (lineIndex: number) => {
        const updated = [...issueForm.data.items];
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations: [...updated[lineIndex].allocations, emptyAllocation()],
        };
        issueForm.setData('items', updated);
    };

    const removeAllocation = (lineIndex: number, allocationIndex: number) => {
        const updated = [...issueForm.data.items];
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations:
                updated[lineIndex].allocations.length === 1
                    ? []
                    : updated[lineIndex].allocations.filter(
                          (_, index) => index !== allocationIndex,
                      ),
        };
        issueForm.setData('items', updated);
    };

    const updateAllocation = (
        lineIndex: number,
        allocationIndex: number,
        field: 'inventory_batch_id' | 'quantity',
        value: string,
    ) => {
        const updated = [...issueForm.data.items];
        const allocations = [...updated[lineIndex].allocations];
        allocations[allocationIndex] = {
            ...allocations[allocationIndex],
            [field]: value,
        };
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations,
        };
        issueForm.setData('items', updated);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`${navigation.requisitions_title}: ${requisition.requisition_number}`}
            />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {requisition.requisition_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {isRequesterWorkspace
                                ? `${requisition.requesting_location?.name ?? '-'} requesting stock from ${requisition.fulfilling_location?.name ?? '-'}`
                                : `Incoming request from ${requisition.requesting_location?.name ?? '-'} to be fulfilled by ${requisition.fulfilling_location?.name ?? '-'}`
                            }
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={navigation.requisitions_href}>Back</Link>
                    </Button>
                </div>

                <RequisitionSummaryCard
                    requisition={requisition}
                    isRequesterWorkspace={isRequesterWorkspace}
                />

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <RequesterActionsPanel
                        navigation={navigation}
                        requisition={requisition}
                        canSubmitToMainStore={canSubmitToMainStore}
                        canCancelRequest={canCancelRequest}
                        submitForm={submitForm}
                        cancelForm={cancelForm}
                    />

                    {navigation.key === 'inventory' ? (
                        <div className="mt-4 space-y-6 border-t pt-4">
                            <RequisitionReviewPanel
                                navigation={navigation}
                                requisition={requisition}
                                canReview={canReview}
                                approveForm={approveForm}
                                rejectForm={rejectForm}
                                onApproveLineChange={updateApproveLine}
                            />

                            <RequisitionIssuePanel
                                requisition={requisition}
                                canIssue={canIssue}
                                availableBatchBalances={availableBatchBalances}
                                issueForm={issueForm}
                                onIssueLineChange={updateIssueLine}
                                onAddAllocation={addAllocation}
                                onRemoveAllocation={removeAllocation}
                                onUpdateAllocation={updateAllocation}
                                submitUrl={`${navigation.requisitions_href}/${requisition.id}/issue`}
                            />
                        </div>
                    ) : null}
                </div>

                <RequisitionLinesTable requisition={requisition} />
            </div>
        </AppLayout>
    );
}
