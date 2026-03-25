import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type LaboratoryQueueRequest,
    type LaboratoryWorklistPageProps,
} from '@/types/laboratory';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Laboratory', href: '/laboratory/dashboard' },
    { title: 'Worklist', href: '/laboratory/worklist' },
];

const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

const workflowVariant = (
    workflowStage: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (workflowStage === 'approved') return 'default';
    if (workflowStage === 'reviewed') return 'secondary';
    if (workflowStage === 'cancelled') return 'destructive';

    return 'outline';
};

const requestVariant = (
    priority: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (priority === 'critical' || priority === 'stat') return 'destructive';
    if (priority === 'urgent') return 'secondary';

    return 'outline';
};

export default function LaboratoryWorklist({
    requests,
    filters,
    statuses,
}: LaboratoryWorklistPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            status === (filters.status ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/laboratory/worklist',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['requests', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, status, filters.search, filters.status]);

    const queue = requests.data ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laboratory Worklist" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold">
                            Laboratory Queue
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Review incoming patient requests, receive tests into
                            the bench workflow, and open each test for result
                            entry and approval.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link href="/laboratory/dashboard">
                                Open Dashboard
                            </Link>
                        </Button>
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient, visit, or test..."
                        />
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger className="min-w-48">
                                <SelectValue placeholder="Filter by status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    {statuses.map((statusOption) => (
                                        <SelectItem
                                            key={statusOption.value}
                                            value={statusOption.value}
                                        >
                                            {statusOption.label}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    {queue.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center text-sm text-muted-foreground">
                                No laboratory requests matched this filter.
                            </CardContent>
                        </Card>
                    ) : (
                        queue.map((request) => (
                            <QueueCard key={request.id} request={request} />
                        ))
                    )}
                </div>

                {(requests.prev_page_url ?? requests.next_page_url) ? (
                    <div className="flex items-center justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            asChild={Boolean(requests.prev_page_url)}
                            disabled={!requests.prev_page_url}
                        >
                            {requests.prev_page_url ? (
                                <Link href={requests.prev_page_url}>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            asChild={Boolean(requests.next_page_url)}
                            disabled={!requests.next_page_url}
                        >
                            {requests.next_page_url ? (
                                <Link href={requests.next_page_url}>Next</Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}

function QueueCard({ request }: { request: LaboratoryQueueRequest }) {
    const patient = request.visit?.patient ?? null;

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div className="flex flex-col gap-1">
                        <CardTitle>
                            {patient
                                ? `${patient.first_name} ${patient.last_name}`
                                : 'Unknown patient'}
                        </CardTitle>
                        <CardDescription>
                            Visit {request.visit?.visit_number ?? 'N/A'} | MRN{' '}
                            {patient?.patient_number ?? 'N/A'} | Requested{' '}
                            {new Date(request.request_date).toLocaleString()}
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant={requestVariant(request.priority)}>
                            {labelize(request.priority)}
                        </Badge>
                        <Badge variant="outline">
                            {labelize(request.status)}
                        </Badge>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                {request.clinical_notes ? (
                    <p className="text-sm text-muted-foreground">
                        {request.clinical_notes}
                    </p>
                ) : null}

                <div className="flex flex-col gap-3">
                    {request.items.map((item) => (
                        <div
                            key={item.id}
                            className="flex flex-col gap-3 rounded-lg border p-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div className="flex flex-col gap-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-medium">
                                        {item.test?.test_name ?? 'Lab test'}
                                    </p>
                                    <Badge
                                        variant={workflowVariant(
                                            item.workflow_stage,
                                        )}
                                    >
                                        {labelize(item.workflow_stage)}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {item.test?.test_code ?? 'N/A'} |{' '}
                                    {item.test?.category ?? 'Uncategorized'} |{' '}
                                    {item.test?.specimen_type ?? 'Specimen not set'}
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/laboratory/request-items/${item.id}`}
                                    >
                                        {item.workflow_stage === 'pending'
                                            ? 'Receive and Open'
                                            : 'Open Workflow'}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

