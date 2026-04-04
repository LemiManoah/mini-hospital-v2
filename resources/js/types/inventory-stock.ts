export interface InventoryStockRow {
    item_id: string;
    item_name: string;
    item_type: string | null;
    unit: string | null;
    total_quantity: number;
    minimum_stock_level: number;
    location_quantities: Record<string, number>;
}

export interface InventoryStockLocation {
    id: string;
    name: string;
    code: string;
    type: string | null;
    label: string;
}

export interface InventoryStockOption {
    value: string;
    label: string;
}

export interface PaginatedInventoryStockRows {
    data: InventoryStockRow[];
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

export interface InventoryStockByLocationPageProps {
    rows: PaginatedInventoryStockRows;
    filters: {
        search: string | null;
        type: string | null;
    };
    itemTypes: InventoryStockOption[];
    locations: InventoryStockLocation[];
    note: string;
}
