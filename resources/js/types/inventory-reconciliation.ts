import type { InventoryItem } from './inventory-item';

export interface InventoryReconciliationItem {
    id: string;
    inventory_item_id: string;
    inventory_batch_id: string | null;
    expected_quantity: string | null;
    actual_quantity: string | null;
    variance_quantity: string | null;
    quantity_delta: string;
    unit_cost: string | null;
    batch_number: string | null;
    expiry_date: string | null;
    notes: string | null;
    inventory_item?: Pick<InventoryItem, 'id' | 'name' | 'generic_name'> | null;
    inventory_batch?: {
        id: string;
        batch_number: string | null;
        expiry_date: string | null;
    } | null;
}

export interface InventoryReconciliation {
    id: string;
    adjustment_number: string;
    workflow_status: string;
    adjustment_date: string;
    reason: string;
    notes: string | null;
    review_notes: string | null;
    approval_notes: string | null;
    rejection_reason: string | null;
    submitted_at: string | null;
    reviewed_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    posted_at: string | null;
    can_submit: boolean;
    can_review: boolean;
    can_approve: boolean;
    can_reject: boolean;
    can_post: boolean;
    inventory_location?: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    items?: InventoryReconciliationItem[];
}

export interface InventoryReconciliationOption {
    value: string;
    label: string;
}

export interface InventoryReconciliationLocationOption {
    id: string;
    name: string;
    location_code: string;
}

export interface InventoryReconciliationLocationBalance {
    inventory_location_id: string;
    inventory_item_id: string;
    quantity: number;
}

export interface InventoryReconciliationBatchBalance {
    inventory_batch_id: string;
    inventory_location_id: string;
    inventory_item_id: string;
    batch_number: string | null;
    expiry_date: string | null;
    quantity: number;
    item_name: string | null;
    location_name: string | null;
}

export interface PaginatedInventoryReconciliationList {
    data: InventoryReconciliation[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface InventoryReconciliationIndexPageProps {
    reconciliations: PaginatedInventoryReconciliationList;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: InventoryReconciliationOption[];
}

export interface InventoryReconciliationFormPageProps {
    inventoryLocations: InventoryReconciliationLocationOption[];
    inventoryItems: Pick<
        InventoryItem,
        'id' | 'name' | 'generic_name' | 'item_type' | 'default_purchase_price'
    >[];
    locationBalances: InventoryReconciliationLocationBalance[];
    batchBalances: InventoryReconciliationBatchBalance[];
}

export interface InventoryReconciliationShowPageProps {
    reconciliation: InventoryReconciliation;
}
