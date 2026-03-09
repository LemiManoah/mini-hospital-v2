import { Country } from './country';

export interface Address {
    id: string;
    city: string;
    district: string | null;
    state: string | null;
    country_id: string | null;
    country?: Country;
    created_at: string;
    updated_at: string;
}

export interface PaginatedAddresses {
    data: Address[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface AddressIndexPageProps {
    addresses: Address[] | PaginatedAddresses;
    filters: {
        search: string | null;
    };
}
