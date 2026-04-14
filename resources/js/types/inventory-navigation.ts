export type InventoryNavigationContextKey =
    | 'inventory'
    | 'laboratory'
    | 'pharmacy';

export interface InventoryNavigationContext {
    key: InventoryNavigationContextKey;
    section_title: string;
    section_href: string;
    management_title?: string | null;
    management_href?: string | null;
    queue_title?: string | null;
    queue_href?: string | null;
    stock_title: string;
    stock_href: string;
    requisitions_title: string;
    requisitions_href: string;
    requisition_create_title: string;
    movements_title: string;
    movements_href: string;
    receipts_title: string;
    receipts_href: string;
    receipt_create_title: string;
    dispenses_title?: string | null;
    dispenses_href?: string | null;
}

export const withInventoryContext = (
    href: string,
    navigation: InventoryNavigationContext,
): string => {
    if (navigation.key === 'inventory') {
        return href;
    }

    const separator = href.includes('?') ? '&' : '?';

    return `${href}${separator}context=${navigation.key}`;
};
