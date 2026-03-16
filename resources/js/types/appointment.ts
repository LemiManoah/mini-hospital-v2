export interface AppointmentClinicOption {
    id: string;
    name: string;
    clinic_name?: string;
}

export interface AppointmentCategory {
    id: string;
    name: string;
    description: string | null;
    clinic_id: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    clinic?: AppointmentClinicOption | null;
}

export interface AppointmentMode {
    id: string;
    name: string;
    description: string | null;
    is_virtual: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface AppointmentPaginatedList<T> {
    data: T[];
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

export interface AppointmentCategoryIndexPageProps {
    appointmentCategories:
        | AppointmentPaginatedList<AppointmentCategory>
        | AppointmentCategory[];
    filters: {
        search: string | null;
    };
}

export interface AppointmentCategoryFormPageProps {
    clinics: AppointmentClinicOption[];
}

export interface AppointmentCategoryEditPageProps
    extends AppointmentCategoryFormPageProps {
    appointmentCategory: AppointmentCategory;
}

export interface AppointmentModeIndexPageProps {
    appointmentModes: AppointmentPaginatedList<AppointmentMode> | AppointmentMode[];
    filters: {
        search: string | null;
    };
}

export interface AppointmentModeEditPageProps {
    appointmentMode: AppointmentMode;
}
