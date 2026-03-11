import { type PaginatedResponse } from '@/types';

export type UnitType = 'mass' | 'volume' | 'length' | 'temperature' | 'time' | 'count' | 'other';

export interface Unit {
    id: string;
    tenant_id: string | null;
    name: string;
    symbol: string;
    description: string | null;
    type: UnitType;
    created_by: string | null;
    updated_by: string | null;
    created_at: string;
    updated_at: string;
}

export interface UnitIndexPageProps {
    units: PaginatedResponse<Unit> | Unit[];
    filters: {
        search: string | null;
    };
}
