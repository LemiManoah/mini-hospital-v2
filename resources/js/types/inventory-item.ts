export interface InventoryItemSummaryOption {
    value: string;
    label: string;
}

export interface InventoryItemUnitLink {
    id: string;
    name: string;
    symbol: string;
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
