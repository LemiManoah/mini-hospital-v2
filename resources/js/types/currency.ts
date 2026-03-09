export interface Currency {
    id: string;
    code: string;
    name: string;
    symbol: string;
    modifiable: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedCurrencies {
    data: Currency[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface CurrencyIndexPageProps {
    currencies: Currency[] | PaginatedCurrencies;
    filters: {
        search: string | null;
    };
}
