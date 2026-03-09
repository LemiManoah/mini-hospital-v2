export interface Department {
    id: string;
    tenant_id: string;
    department_code: string;
    department_name: string;
    location: string | null;
    head_of_department_id: string | null;
    is_clinical: boolean;
    is_active: boolean;
    contact_info: Record<string, any> | null;
    created_at: string;
    updated_at: string;
    head_of_department?: {
        id: string;
        first_name: string;
        last_name: string;
    } | null;
}

export interface DepartmentIndexPageProps {
    departments:
        | {
              data: Department[];
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
        | Department[];
    filters: {
        search: string | null;
    };
}

export interface DepartmentCreatePageProps {
    staff: {
        id: string;
        first_name: string;
        last_name: string;
    }[];
}

export interface DepartmentEditPageProps {
    department: Department;
    staff: {
        id: string;
        first_name: string;
        last_name: string;
    }[];
}
