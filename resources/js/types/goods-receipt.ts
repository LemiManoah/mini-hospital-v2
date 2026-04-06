import type { InventoryItem } from './inventory-item';
import type { InventoryNavigationContext } from './inventory-navigation';
import type { PurchaseOrder, PurchaseOrderItem } from './purchase-order';

export interface GoodsReceiptItem {
    id: string;
    goods_receipt_id: string;
    purchase_order_item_id: string;
    inventory_item_id: string;
    quantity_received: string;
    unit_cost: string;
    batch_number: string | null;
    expiry_date: string | null;
    notes: string | null;
    inventory_item?: InventoryItem;
    purchase_order_item?: PurchaseOrderItem;
}

export interface InventoryLocationOption {
    id: string;
    name: string;
    location_code: string;
}

export interface GoodsReceipt {
    id: string;
    purchase_order_id: string;
    inventory_location_id: string;
    receipt_number: string;
    status: string;
    receipt_date: string;
    supplier_invoice_number: string | null;
    supplier_delivery_note: string | null;
    notes: string | null;
    posted_at: string | null;
    created_at: string;
    updated_at: string;
    purchase_order?: PurchaseOrder & { supplier?: { id: string; name: string } };
    inventory_location?: InventoryLocationOption;
    items?: GoodsReceiptItem[];
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface PaginatedGoodsReceiptList {
    data: GoodsReceipt[];
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

export interface GoodsReceiptIndexPageProps {
    goodsReceipts: PaginatedGoodsReceiptList;
    navigation: InventoryNavigationContext;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: SelectOption[];
}

export interface GoodsReceiptFormPageProps {
    navigation: InventoryNavigationContext;
    purchaseOrders: PurchaseOrder[];
    selectedPurchaseOrder: PurchaseOrder | null;
    inventoryLocations: InventoryLocationOption[];
}

export interface GoodsReceiptShowPageProps {
    navigation: InventoryNavigationContext;
    goodsReceipt: GoodsReceipt;
}
