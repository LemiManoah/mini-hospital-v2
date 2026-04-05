export interface InventoryItemSummaryOption {
    value: string;
    label: string;
}

export interface InventoryItemUnitLink {
    id: string;
    name: string;
    symbol: string;
}

export interface InventoryBatch {
    id: string;
    batch_number: string;
    expiry_date: string | null;
    quantity_received: string;
    unit_cost: string;
    received_at: string;
    inventory_location_id: string;
    inventory_location?: {
        id: string;
        name: string;
    } | null;
}

export interface StockMovement {
    id: string;
    movement_type: string;
    quantity: string;
    unit_cost: string | null;
    occurred_at: string;
    created_at: string;
    inventory_location_id: string;
    inventory_location?: {
        id: string;
        name: string;
    } | null;
    user?: {
        id: string;
        name: string;
    } | null;
}

export interface InventoryItem {
    id: string;
    item_type: string;
    name: string;
    generic_name: string | null;
    brand_name: string | null;
    category: string | null;
    description: string | null;
    unit_id: string | null;
    strength: string | null;
    dosage_form: string | null;
    minimum_stock_level: string;
    reorder_level: string;
    default_purchase_price: string | null;
    default_selling_price: string | null;
    manufacturer: string | null;
    expires: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    unit?: InventoryItemUnitLink | null;
    batches?: InventoryBatch[];
    stock_movements?: StockMovement[];
}

export interface InventoryItemShowPageProps {
    inventoryItem: InventoryItem;
}
export interface PaginatedInventoryItemList<T> {
    data: T[];
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

export interface InventoryItemIndexPageProps {
    items: PaginatedInventoryItemList<InventoryItem> | InventoryItem[];
    filters: {
        search: string | null;
        type: string | null;
    };
    itemTypes: InventoryItemSummaryOption[];
}

export interface InventoryItemFormPageProps {
    itemTypes: InventoryItemSummaryOption[];
    unitOptions: InventoryItemSummaryOption[];
    drugCategories: InventoryItemSummaryOption[];
    dosageForms: InventoryItemSummaryOption[];
}

export interface InventoryItemEditPageProps extends InventoryItemFormPageProps {
    inventoryItem: InventoryItem;
}
