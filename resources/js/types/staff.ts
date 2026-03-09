import type { Department } from './department';
import type { StaffPosition } from './staff-position';

export interface Staff {
    id: string;
    tenant_id: string;
    employee_number: string;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    email: string;
    phone: string | null;
    address_id: string | null;
    department_id: string | null;
    staff_position_id: string | null;
    type: string;
    license_number: string | null;
    specialty: string | null;
    hire_date: string | null;
    termination_date: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    department?: Department;
    position?: StaffPosition;
    branches?: { id: string; name: string }[];
}

export interface PaginatedStaff {
    data: Staff[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface StaffIndexPageProps {
    staff: Staff[] | PaginatedStaff;
    filters: {
        search: string | null;
    };
}

export interface StaffCreatePageProps {
    departments: { id: string; department_name: string }[];
    positions: { id: string; name: string }[];
    branches: { id: string; name: string }[];
}

export interface StaffEditPageProps {
    staff: Staff;
    departments: { id: string; department_name: string }[];
    positions: { id: string; name: string }[];
    branches: { id: string; name: string }[];
}
