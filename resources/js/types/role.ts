export interface Permission {
    id: string;
    name: string;
}

export interface Role {
    id: string;
    name: string;
    permissions: Permission[];
}

export interface PaginatedLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedRoles {
    data: Role[];
    links: PaginatedLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
}

export type PermissionGroups = Record<string, Permission[]>;

export interface RoleIndexPageProps {
    roles: Role[] | PaginatedRoles;
    filters: {
        search: string;
    };
}

export interface RoleCreatePageProps {
    permissionGroups: PermissionGroups;
}

export interface RoleEditPageProps {
    role: Role;
    permissionGroups: PermissionGroups;
}
