import type { InventoryItem } from './inventory-item';
import type { InventoryNavigationContext } from './inventory-navigation';

export interface InventoryRequisitionItemIssueHistory {
    quantity: number;
    batch_number: string | null;
    expiry_date: string | null;
    occurred_at: string | null;
}

export interface InventoryRequisitionItem {
    id: string;
    inventory_item_id: string;
    requested_quantity: number;
    approved_quantity: number;
    issued_quantity: number;
    remaining_quantity: number;
    notes: string | null;
    inventory_item?: Pick<InventoryItem, 'id' | 'name' | 'generic_name'> | null;
    issue_history?: InventoryRequisitionItemIssueHistory[];
}

export interface InventoryRequisition {
    id: string;
    requisition_number: string;
    status: string | null;
    status_label: string | null;
    priority: string | null;
    priority_label: string | null;
    requisition_date: string | null;
    notes: string | null;
    approval_notes: string | null;
    rejection_reason: string | null;
    issued_notes: string | null;
    submitted_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    issued_at: string | null;
    can_submit: boolean;
    can_approve: boolean;
    can_reject: boolean;
    can_issue: boolean;
    source_location?: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    destination_location?: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    items?: InventoryRequisitionItem[];
}

export interface InventoryRequisitionOption {
    value: string;
    label: string;
}

export interface InventoryRequisitionLocationOption {
    id: string;
    name: string;
    location_code: string;
}

export interface InventoryRequisitionAvailableBatch {
    inventory_batch_id: string;
    inventory_item_id: string;
    batch_number: string | null;
    expiry_date: string | null;
    quantity: number;
    item_name: string | null;
}

export interface PaginatedInventoryRequisitionList {
    data: InventoryRequisition[];
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

export interface InventoryRequisitionIndexPageProps {
    requisitions: PaginatedInventoryRequisitionList;
    navigation: InventoryNavigationContext;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: InventoryRequisitionOption[];
}

export interface InventoryRequisitionFormPageProps {
    navigation: InventoryNavigationContext;
    sourceInventoryLocations: InventoryRequisitionLocationOption[];
    destinationInventoryLocations: InventoryRequisitionLocationOption[];
    inventoryItems: Pick<
        InventoryItem,
        'id' | 'name' | 'generic_name' | 'item_type'
    >[];
    priorityOptions: InventoryRequisitionOption[];
}

export interface InventoryRequisitionShowPageProps {
    navigation: InventoryNavigationContext;
    requisition: InventoryRequisition;
    availableBatchBalances: InventoryRequisitionAvailableBatch[];
}
