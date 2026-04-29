export interface AuditTimelineEntry {
    id: string;
    log_name: string | null;
    event: string | null;
    title: string;
    description: string | null;
    actor: string | null;
    reason: string | null;
    created_at: string | null;
}
