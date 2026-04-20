export interface Currency {
    id: string;
    code: string;
    name: string;
    symbol: string;
    decimal_places: number;
    symbol_position: 'before' | 'after';
    modifiable: boolean;
    created_at: string;
    updated_at: string;
}

export interface CurrencyExchangeRate {
    id: string;
    tenant_id: string;
    from_currency_id: string;
    to_currency_id: string;
    rate: number;
    effective_date: string;
    notes: string | null;
    from_currency: Pick<Currency, 'id' | 'code' | 'name' | 'symbol'>;
    to_currency: Pick<Currency, 'id' | 'code' | 'name' | 'symbol'>;
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
