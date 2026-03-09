export interface Country {
    id: string;
    country_name: string;
    country_code: string;
    dial_code: string;
    currency: string;
    currency_symbol: string;
    created_at: string;
    updated_at: string;
}

export interface PaginatedCountries {
    data: Country[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface CountryIndexPageProps {
    countries: Country[] | PaginatedCountries;
    filters: {
        search: string | null;
    };
}
