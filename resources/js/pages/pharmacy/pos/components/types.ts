import {
    BadgePercent,
    ClipboardCheck,
    Receipt,
    ShoppingCart,
    WalletCards,
} from 'lucide-react';

export interface DispensingLocation {
    id: string;
    name: string;
    location_code: string;
    is_dispensing_point: boolean;
}

export interface SearchableItem {
    id: string;
    name: string;
    generic_name: string | null;
    brand_name: string | null;
    strength: string | null;
    dosage_form: string | null;
    unit_price: number;
    available_quantity: number;
}

export interface CartItem {
    id: string;
    inventory_item_id: string;
    item_name: string | null;
    generic_name: string | null;
    brand_name: string | null;
    strength: string | null;
    dosage_form: string | null;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    line_total: number;
    available_quantity: number;
    notes: string | null;
}

export interface ActiveCart {
    id: string;
    cart_number: string;
    status: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    notes: string | null;
    inventory_location_id: string | null;
    inventory_location: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    items: CartItem[];
    gross_amount: number;
    discount_amount: number;
    total_amount: number;
}

export interface HeldCart {
    id: string;
    cart_number: string;
    held_at: string | null;
    customer_name: string | null;
}

export const CATALOG_PAGE_SIZE = 12;

export const saleSteps = [
    {
        title: 'Start Sale',
        icon: ShoppingCart,
    },
    {
        title: 'Build Cart',
        icon: ClipboardCheck,
    },
    {
        title: 'Review Totals',
        icon: BadgePercent,
    },
    {
        title: 'Take Payment',
        icon: WalletCards,
    },
    {
        title: 'Receipt',
        icon: Receipt,
    },
];

export function formatMoney(amount: number) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function formatQuantity(quantity: number) {
    return Number.isInteger(quantity)
        ? quantity.toString()
        : quantity.toFixed(3);
}

export function stockTone(quantity: number) {
    if (quantity <= 20) {
        return {
            border: 'border-rose-200 dark:border-rose-900/70',
            badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
            label: 'Low stock',
        };
    }

    if (quantity <= 75) {
        return {
            border: 'border-amber-200 dark:border-amber-900/70',
            badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
            label: 'Monitor',
        };
    }

    return {
        border: 'border-emerald-200 dark:border-emerald-900/70',
        badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        label: 'Available',
    };
}
