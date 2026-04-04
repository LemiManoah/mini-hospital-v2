import type { InventoryItem } from './inventory-item';
import type { Supplier } from './supplier';

export interface PurchaseOrderItem {
    id: string;
    purchase_order_id: string;
    inventory_item_id: string;
    quantity_ordered: string;
    unit_cost: string;
    total_cost: string;
    quantity_received: string;
    notes: string | null;
    inventory_item?: InventoryItem;
}

export interface PurchaseOrder {
    id: string;
    supplier_id: string;
    order_number: string;
    status: string;
    order_date: string;
    expected_delivery_date: string | null;
    notes: string | null;
    total_amount: string;
    approved_at: string | null;
    created_at: string;
    updated_at: string;
    supplier?: Pick<Supplier, 'id' | 'name'>;
    items?: PurchaseOrderItem[];
    goods_receipts?: GoodsReceiptSummary[];
}

export interface GoodsReceiptSummary {
    id: string;
    receipt_number: string;
    status: string;
    receipt_date: string;
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface PaginatedPurchaseOrderList {
    data: PurchaseOrder[];
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

export interface PurchaseOrderIndexPageProps {
    purchaseOrders: PaginatedPurchaseOrderList;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: SelectOption[];
}

export interface PurchaseOrderFormPageProps {
    suppliers: Pick<Supplier, 'id' | 'name'>[];
    inventoryItems: Pick<InventoryItem, 'id' | 'name' | 'generic_name' | 'item_type'>[];
}

export interface PurchaseOrderEditPageProps extends PurchaseOrderFormPageProps {
    purchaseOrder: PurchaseOrder;
}

export interface PurchaseOrderShowPageProps {
    purchaseOrder: PurchaseOrder;
}
