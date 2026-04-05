import type { InventoryItem } from './inventory-item';

export interface StockCountItem {
    id: string;
    stock_count_id: string;
    inventory_item_id: string;
    expected_quantity: string;
    counted_quantity: string;
    variance_quantity: string;
    notes: string | null;
    inventory_item?: InventoryItem;
}

export interface StockCount {
    id: string;
    inventory_location_id: string;
    count_number: string;
    status: string;
    count_date: string;
    notes: string | null;
    posted_at: string | null;
    created_at: string;
    updated_at: string;
    inventory_location?: {
        id: string;
        name: string;
        location_code: string;
    };
    items?: StockCountItem[];
}

export interface StockCountOption {
    value: string;
    label: string;
}

export interface StockCountLocationOption {
    id: string;
    name: string;
    location_code: string;
}

export interface StockCountLocationBalance {
    inventory_location_id: string;
    inventory_item_id: string;
    quantity: number;
}

export interface PaginatedStockCountList {
    data: StockCount[];
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

export interface StockCountIndexPageProps {
    stockCounts: PaginatedStockCountList;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: StockCountOption[];
}

export interface StockCountFormPageProps {
    inventoryLocations: StockCountLocationOption[];
    inventoryItems: Pick<
        InventoryItem,
        'id' | 'name' | 'generic_name' | 'item_type'
    >[];
    locationBalances: StockCountLocationBalance[];
}

export interface StockCountShowPageProps {
    stockCount: StockCount;
}
