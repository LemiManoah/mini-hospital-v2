import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerPagination } from './components/facility-manager-pagination';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    facilitySupportPriorityOptions,
    facilitySupportStatusOptions,
    toDateTimeLocalValue,
} from './support-workflow';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
    type PaginatedFacilityManagerList,
} from './types';

interface SupportNote {
    id: string;
    title: string | null;
    body: string;
    is_pinned: boolean;
    created_at: string | null;
    updated_at: string | null;
    author?: {
        id: string;
        name: string;
        email: string;
    } | null;
}

interface FacilityManagerSupportNotesProps {
    tenant: FacilityManagerTenantSummary;
    metrics: FacilityManagerMetric[];
    notes: PaginatedFacilityManagerList<SupportNote>;
}

const formatDateTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'Not set';

export default function FacilityManagerSupportNotes({
    tenant,
    metrics,
    notes,
}: FacilityManagerSupportNotesProps) {
    const { hasPermission } = usePermissions();
    const form = useForm({
        title: '',
        body: '',
        is_pinned: false,
    });
    const workflowForm = useForm({
        status: tenant.support_workflow.status,
        priority: tenant.support_workflow.priority,
        follow_up_at: toDateTimeLocalValue(
            tenant.support_workflow.follow_up_at,
        ),
        last_contacted_at: toDateTimeLocalValue(
            tenant.support_workflow.last_contacted_at,
        ),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
        {
            title: 'Support Notes',
            href: `/facility-manager/facilities/${tenant.id}/support-notes`,
        },
    ];

    const submit = () => {
        form.post(`/facility-manager/facilities/${tenant.id}/support-notes`, {
            preserveScroll: true,
            onSuccess: () => form.reset('title', 'body', 'is_pinned'),
        });
    };

    const submitWorkflow = () => {
        workflowForm.patch(
            `/facility-manager/facilities/${tenant.id}/support-workflow`,
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Support Notes`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link
                            href={`/facility-manager/facilities/${tenant.id}`}
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Overview
                        </Link>
                    </Button>
                </div>

                <FacilityManagerTenantHeader tenant={tenant} />

                <FacilityManagerNav tenantId={tenant.id} current="notes" />

                <FacilityManagerMetrics metrics={metrics} />

                <div className="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    {hasPermission('tenants.update') ? (
                        <div className="space-y-6">
                            <Card className="border-none shadow-sm ring-1 ring-border/50">
                                <CardHeader>
                                    <CardTitle>Support Workflow</CardTitle>
                                    <CardDescription>
                                        Track whether this facility needs
                                        follow-up, is waiting on customer input,
                                        or should be escalated.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="support_status">
                                                Status
                                            </Label>
                                            <Select
                                                value={workflowForm.data.status}
                                                onValueChange={(value) =>
                                                    workflowForm.setData(
                                                        'status',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger id="support_status">
                                                    <SelectValue placeholder="Select support status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {facilitySupportStatusOptions.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    workflowForm.errors.status
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="support_priority">
                                                Priority
                                            </Label>
                                            <Select
                                                value={
                                                    workflowForm.data.priority
                                                }
                                                onValueChange={(value) =>
                                                    workflowForm.setData(
                                                        'priority',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger id="support_priority">
                                                    <SelectValue placeholder="Select support priority" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {facilitySupportPriorityOptions.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    workflowForm.errors.priority
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="follow_up_at">
                                                Next Follow-Up
                                            </Label>
                                            <Input
                                                id="follow_up_at"
                                                type="datetime-local"
                                                value={
                                                    workflowForm.data
                                                        .follow_up_at
                                                }
                                                onChange={(event) =>
                                                    workflowForm.setData(
                                                        'follow_up_at',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    workflowForm.errors
                                                        .follow_up_at
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="last_contacted_at">
                                                Last Contacted
                                            </Label>
                                            <Input
                                                id="last_contacted_at"
                                                type="datetime-local"
                                                value={
                                                    workflowForm.data
                                                        .last_contacted_at
                                                }
                                                onChange={(event) =>
                                                    workflowForm.setData(
                                                        'last_contacted_at',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    workflowForm.errors
                                                        .last_contacted_at
                                                }
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        onClick={submitWorkflow}
                                        disabled={workflowForm.processing}
                                    >
                                        Save Support Workflow
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="border-none shadow-sm ring-1 ring-border/50">
                                <CardHeader>
                                    <CardTitle>Add Support Note</CardTitle>
                                    <CardDescription>
                                        Capture internal context for onboarding,
                                        billing, or support follow-up.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">Title</Label>
                                        <Input
                                            id="title"
                                            value={form.data.title}
                                            onChange={(event) =>
                                                form.setData(
                                                    'title',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="e.g. Billing follow-up"
                                        />
                                        <InputError
                                            message={form.errors.title}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="body">Note</Label>
                                        <Textarea
                                            id="body"
                                            value={form.data.body}
                                            onChange={(event) =>
                                                form.setData(
                                                    'body',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Write the internal note for this facility..."
                                            rows={8}
                                        />
                                        <InputError
                                            message={form.errors.body}
                                        />
                                    </div>

                                    <div className="flex items-center gap-2 rounded-lg border border-border bg-muted/40 p-3">
                                        <Checkbox
                                            id="is_pinned"
                                            checked={form.data.is_pinned}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'is_pinned',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor="is_pinned">
                                            Pin this note to keep it at the top
                                        </Label>
                                    </div>

                                    <Button
                                        type="button"
                                        onClick={submit}
                                        disabled={form.processing}
                                    >
                                        Save Support Note
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    ) : null}

                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Note History</CardTitle>
                            <CardDescription>
                                Internal note stream for this facility.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {notes.data.length > 0 ? (
                                notes.data.map((note) => (
                                    <div
                                        key={note.id}
                                        className="rounded-2xl border p-4"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    {note.title ??
                                                        'Untitled note'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {note.author?.name ??
                                                        'Unknown author'}{' '}
                                                    •{' '}
                                                    {formatDateTime(
                                                        note.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            {note.is_pinned ? (
                                                <span className="rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                                                    Pinned
                                                </span>
                                            ) : null}
                                        </div>
                                        <p className="mt-3 text-sm whitespace-pre-wrap text-muted-foreground">
                                            {note.body}
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No support notes have been recorded yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <FacilityManagerPagination
                    links={notes.links}
                    prevPageUrl={notes.prev_page_url}
                    nextPageUrl={notes.next_page_url}
                />
            </div>
        </AppLayout>
    );
}
