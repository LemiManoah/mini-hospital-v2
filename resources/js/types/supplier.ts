export interface Supplier {
    id: string;
    name: string;
    contact_person: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    tax_id: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedSupplierList {
    data: Supplier[];
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

export interface SupplierIndexPageProps {
    suppliers: PaginatedSupplierList;
    filters: {
        search: string | null;
    };
}

export interface SupplierEditPageProps {
    supplier: Supplier;
}
