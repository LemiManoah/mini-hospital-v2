export interface FacilityBranch {
    id: string;
    tenant_id: string;
    name: string;
    branch_code: string;
    currency_id: string;
    status: 'active' | 'inactive' | 'suspended' | 'cancelled' | 'pending';
    main_contact: string | null;
    other_contact: string | null;
    email: string | null;
    is_main_branch: boolean;
    has_store: boolean;
    created_at: string;
    updated_at: string;
    currency?: {
        id: string;
        code: string;
        name: string;
        symbol: string;
    };
    staff_count?: number;
}

export interface FacilityBranchIndexPageProps {
    branches:
        | {
              data: FacilityBranch[];
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
        | FacilityBranch[];
    filters: {
        search: string | null;
    };
}

export interface FacilityBranchFormPageProps {
    currencies: {
        id: string;
        code: string;
        name: string;
        symbol: string;
    }[];
    defaultCurrencyId?: string | null;
}

export interface FacilityBranchEditPageProps extends FacilityBranchFormPageProps {
    branch: FacilityBranch;
}
