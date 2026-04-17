import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type LaboratoryQueuePageProps } from '@/types/laboratory';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { QueueModal } from './components/queue-modal';
import { QueuePatientCard } from './components/queue-patient-card';
import {
    groupIncomingRequests,
    modalModeForStage,
    withRequestSummary,
    type ActiveModal,
} from './components/queue-utils';

export default function LaboratoryQueuePage({
    page,
    requests,
    filters,
    labReleasePolicy,
}: LaboratoryQueuePageProps) {
    const inertiaPage = usePage<SharedData>();
    const [search, setSearch] = useState(filters.search ?? '');
    const [activeModal, setActiveModal] = useState<ActiveModal>(null);

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                page.route,
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['requests', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, page.route, search]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/dashboard' },
        { title: page.title, href: page.route },
    ];

    const queueRequests = useMemo(
        () =>
            page.stage === 'incoming'
                ? groupIncomingRequests(requests.data)
                : requests.data.map(withRequestSummary),
        [page.stage, requests.data],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={page.title} />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold">{page.title}</h1>
                        {page.stage !== 'view_results' ? (
                            <p className="text-sm text-muted-foreground">
                                {page.description}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        {page.stage !== 'view_results' ? (
                            <Button variant="outline" asChild>
                                <Link href="/laboratory/dashboard">
                                    Dashboard
                                </Link>
                            </Button>
                        ) : null}
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient, visit, or test..."
                        />
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    {queueRequests.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center text-sm text-muted-foreground">
                                No patients matched this queue.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4">
                            {queueRequests.map((request) => (
                                <QueuePatientCard
                                    key={request.id}
                                    pageStage={page.stage}
                                    actionLabel={page.action_label}
                                    request={request}
                                    onAction={(item, selectedRequest) =>
                                        setActiveModal({
                                            mode: modalModeForStage(
                                                page.stage,
                                            ),
                                            item,
                                            request:
                                                item.request ??
                                                selectedRequest,
                                        })
                                    }
                                />
                            ))}
                        </div>
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

            <QueueModal
                activeModal={activeModal}
                onOpenChange={(open) => !open && setActiveModal(null)}
                redirectTo={inertiaPage.url}
                labReleasePolicy={labReleasePolicy}
            />
        </AppLayout>
    );
}
