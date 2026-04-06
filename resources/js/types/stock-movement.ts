import type { InventoryNavigationContext } from './inventory-navigation';

export interface StockMovementOption {
    value: string;
    label: string;
}

export interface StockMovementRow {
    id: string;
    item_name: string | null;
    location_name: string | null;
    location_code: string | null;
    movement_type: string | null;
    movement_type_label: string | null;
    quantity: number;
    unit_cost: number | null;
    batch_number: string | null;
    expiry_date: string | null;
    occurred_at: string | null;
}

export interface PaginatedStockMovements {
    data: StockMovementRow[];
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

export interface InventoryMovementReportPageProps {
    movements: PaginatedStockMovements;
    navigation: InventoryNavigationContext;
    filters: {
        search: string | null;
        type: string | null;
        location: string | null;
    };
    movementTypes: StockMovementOption[];
    locations: StockMovementOption[];
}
