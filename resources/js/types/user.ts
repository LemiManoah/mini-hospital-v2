import type { Role } from './role';
import { Staff } from './staff';

export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: Role[];
    staff: Staff | null;
}

export interface PaginatedUsers {
    data: User[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface UserIndexPageProps {
    users: User[] | PaginatedUsers;
    filters: {
        search: string | null;
    };
}

export interface UserCreatePageProps {
    staff: Staff[];
    roles: Role[];
}

export interface UserEditPageProps {
    user: User;
    roles: Role[];
}
