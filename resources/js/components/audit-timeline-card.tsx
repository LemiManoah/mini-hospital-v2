import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AuditTimelineEntry } from '@/types/audit';

interface AuditTimelineCardProps {
    title?: string;
    entries: AuditTimelineEntry[];
    emptyMessage?: string;
}

const formatAuditTimestamp = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '-';

export function AuditTimelineCard({
    title = 'Audit Log',
    entries,
    emptyMessage = 'No audit activity recorded yet.',
}: AuditTimelineCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {entries.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {emptyMessage}
                    </p>
                ) : (
                    entries.map((entry) => (
                        <div
                            key={entry.id}
                            className="space-y-1 border-b border-zinc-200 pb-3 last:border-b-0 last:pb-0 dark:border-zinc-800"
                        >
                            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm font-medium">
                                    {entry.title}
                                </p>
                                <span className="text-xs text-muted-foreground">
                                    {formatAuditTimestamp(entry.created_at)}
                                </span>
                            </div>
                            <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                {entry.actor && (
                                    <span>Actor: {entry.actor}</span>
                                )}
                                {entry.log_name && (
                                    <span className="capitalize">
                                        Log: {entry.log_name}
                                    </span>
                                )}
                            </div>
                            {entry.reason && (
                                <p className="text-sm text-zinc-600 dark:text-zinc-300">
                                    Reason: {entry.reason}
                                </p>
                            )}
                        </div>
                    ))
                )}
            </CardContent>
        </Card>
    );
}
