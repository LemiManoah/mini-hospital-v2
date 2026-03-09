export interface Allergen {
    id: string;
    name: string;
    description: string | null;
    type: 'medication' | 'food' | 'environmental' | 'latex' | 'contrast';
    created_at: string;
    updated_at: string;
}

export interface PaginatedAllergens {
    data: Allergen[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface AllergenIndexPageProps {
    allergens: Allergen[] | PaginatedAllergens;
    filters: {
        search: string | null;
    };
}
