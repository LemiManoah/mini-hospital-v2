import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AuditTimelineEntry } from '@/types/audit';

type VisitTimelineTableProps = {
    entries: AuditTimelineEntry[];
    emptyMessage: string;
};

const formatTimelineTimestamp = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'N/A';

export function VisitTimelineTable({
    entries,
    emptyMessage,
}: VisitTimelineTableProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Visit Timeline</CardTitle>
                <CardDescription>
                    Audit activity for clinical, pharmacy, laboratory, billing,
                    and appointment events.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {entries.length === 0 ? (
                    <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                        {emptyMessage}
                    </div>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Time</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Area</TableHead>
                                <TableHead>Actor</TableHead>
                                <TableHead>Details</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell className="min-w-40 text-muted-foreground">
                                        {formatTimelineTimestamp(
                                            entry.created_at,
                                        )}
                                    </TableCell>
                                    <TableCell className="min-w-64">
                                        <div className="flex flex-col gap-1">
                                            <span className="font-medium">
                                                {entry.title}
                                            </span>
                                            {entry.event ? (
                                                <span className="text-xs text-muted-foreground">
                                                    {entry.event}
                                                </span>
                                            ) : null}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {entry.log_name ? (
                                            <Badge
                                                variant="outline"
                                                className="capitalize"
                                            >
                                                {entry.log_name}
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                N/A
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="min-w-36">
                                        {entry.actor ?? (
                                            <span className="text-muted-foreground">
                                                System
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="min-w-72 whitespace-normal">
                                        <div className="flex flex-col gap-1">
                                            <span>
                                                {entry.description ??
                                                    'No additional details'}
                                            </span>
                                            {entry.reason ? (
                                                <span className="text-xs text-muted-foreground">
                                                    Reason: {entry.reason}
                                                </span>
                                            ) : null}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}
