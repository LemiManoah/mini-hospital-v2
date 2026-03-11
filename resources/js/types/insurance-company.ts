export interface InsuranceCompany {
    id: string;
    tenant_id: string;
    name: string;
    email: string | null;
    main_contact: string | null;
    other_contact: string | null;
    address_id: string | null;
    status: 'active' | 'inactive' | 'suspended' | 'cancelled' | 'pending';
    created_at: string;
    updated_at: string;
}

export interface InsuranceCompanyIndexPageProps {
    insuranceCompanies:
        | {
              data: InsuranceCompany[];
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
        | InsuranceCompany[];
    filters: {
        search: string | null;
    };
}

export interface InsuranceCompanyCreatePageProps {
    addresses: { id: string; city: string; district: string | null }[];
}

export interface InsuranceCompanyEditPageProps extends InsuranceCompanyCreatePageProps {
    insuranceCompany: InsuranceCompany;
}
