import { Department } from './department';

export interface Clinic {
    id: string;
    tenant_id: string;
    branch_id: string;
    clinic_code: string;
    clinic_name: string;
    department_id: string;
    location: string | null;
    phone: string | null;
    status: 'active' | 'inactive' | 'suspended' | 'cancelled' | 'pending';
    created_at: string;
    updated_at: string;
    branch?: {
        id: string;
        name: string;
    };
    department?: Department;
}

export interface ClinicIndexPageProps {
    clinics:
        | {
              data: Clinic[];
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
        | Clinic[];
    filters: {
        search: string | null;
    };
}

export interface ClinicCreatePageProps {
    branches: { id: string; name: string }[];
    departments: Department[];
}

export interface ClinicEditPageProps extends ClinicCreatePageProps {
    clinic: Clinic;
}
