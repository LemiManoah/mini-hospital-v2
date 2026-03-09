export interface StaffPosition {
    id: string;
    tenant_id: string;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface StaffPositionIndexPageProps {
    positions:
        | {
              data: StaffPosition[];
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
        | StaffPosition[];
    filters: {
        search: string | null;
    };
}

export interface StaffPositionEditPageProps {
    staff_position: StaffPosition;
}
