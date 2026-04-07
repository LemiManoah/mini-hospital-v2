export interface InventoryLocationSummaryOption {
    value: string;
    label: string;
}

export interface InventoryLocation {
    id: string;
    name: string;
    location_code: string;
    type: string;
    description: string | null;
    is_dispensing_point: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedInventoryLocationList<T> {
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

export interface InventoryLocationIndexPageProps {
    locations:
        | PaginatedInventoryLocationList<InventoryLocation>
        | InventoryLocation[];
    filters: {
        search: string | null;
        type: string | null;
    };
    locationTypes: InventoryLocationSummaryOption[];
}

export interface InventoryLocationFormPageProps {
    locationTypes: InventoryLocationSummaryOption[];
}

export interface InventoryLocationEditPageProps extends InventoryLocationFormPageProps {
    inventoryLocation: InventoryLocation;
}
