import type { InventoryItem } from './inventory-item';

export interface StockAdjustmentItem {
    id: string;
    stock_adjustment_id: string;
    inventory_item_id: string;
    inventory_batch_id: string | null;
    quantity_delta: string;
    unit_cost: string | null;
    batch_number: string | null;
    expiry_date: string | null;
    notes: string | null;
    inventory_item?: InventoryItem;
    inventory_batch?: {
        id: string;
        batch_number: string | null;
        expiry_date: string | null;
    } | null;
}

export interface StockAdjustment {
    id: string;
    inventory_location_id: string;
    adjustment_number: string;
    status: string;
    adjustment_date: string;
    reason: string;
    notes: string | null;
    posted_at: string | null;
    created_at: string;
    updated_at: string;
    inventory_location?: {
        id: string;
        name: string;
        location_code: string;
    };
    items?: StockAdjustmentItem[];
}

export interface StockAdjustmentOption {
    value: string;
    label: string;
}

export interface StockAdjustmentLocationOption {
    id: string;
    name: string;
    location_code: string;
}

export interface StockAdjustmentLocationBalance {
    inventory_location_id: string;
    inventory_item_id: string;
    quantity: number;
}

export interface StockAdjustmentBatchBalance {
    inventory_batch_id: string;
    inventory_location_id: string;
    inventory_item_id: string;
    batch_number: string | null;
    expiry_date: string | null;
    quantity: number;
    item_name: string | null;
    location_name: string | null;
}

export interface PaginatedStockAdjustmentList {
    data: StockAdjustment[];
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

export interface StockAdjustmentIndexPageProps {
    stockAdjustments: PaginatedStockAdjustmentList;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: StockAdjustmentOption[];
}

export interface StockAdjustmentFormPageProps {
    inventoryLocations: StockAdjustmentLocationOption[];
    inventoryItems: Pick<
        InventoryItem,
        'id' | 'name' | 'generic_name' | 'item_type' | 'default_purchase_price'
    >[];
    locationBalances: StockAdjustmentLocationBalance[];
    batchBalances: StockAdjustmentBatchBalance[];
}

export interface StockAdjustmentShowPageProps {
    stockAdjustment: StockAdjustment;
}
