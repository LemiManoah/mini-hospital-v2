import { type PaginatedResponse } from '@/types';

export interface Notification {
    id: string;
    type: string | null;
    title: string | null;
    message: string | null;
    action_url: string | null;
    resource_id: string | null;
    resource_type: string | null;
    occurred_at: string | null;
    read_at: string | null;
    created_at: string | null;
}

export interface NotificationIndexPageProps {
    notifications: PaginatedResponse<Notification>;
}
