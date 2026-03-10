export interface Department {
    id: string;
    tenant_id: string;
    department_code: string;
    department_name: string;
    location: string | null;
    is_clinical: boolean;
    is_active: boolean;
    contact_info: Record<string, any> | null;
    created_at: string;
    updated_at: string;
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

export interface DepartmentCreatePageProps {}

export interface DepartmentEditPageProps {
    department: Department;
}
