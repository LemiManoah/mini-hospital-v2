import { AuditTimelineCard } from '@/components/audit-timeline-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type VisitShowPageProps } from '@/types/patient';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { VisitClinicalTab } from './components/visit-clinical-tab';
import { VisitHeader } from './components/visit-header';
import { VisitOrdersTab } from './components/visit-orders-tab';
import { VisitOverviewTab } from './components/visit-overview-tab';
import { formatDateTime } from './components/visit-show-utils';

export default function VisitShow({
    visit,
    activeTab,
    audit_activity,
    labTestOptions,
    drugOptions,
    labPriorities,
    imagingModalities,
    imagingPriorities,
    imagingLateralities,
    pregnancyStatuses,
    facilityServiceOptions,
    completionCheck,
    triageGrades,
    allergens,
    severityOptions,
    reactionOptions,
}: VisitShowPageProps) {
    const { hasPermission } = usePermissions();
    const allowedTabs = ['overview', 'clinical', 'orders'];
    const [selectedTab, setSelectedTab] = useState(
        allowedTabs.includes(activeTab) ? activeTab : 'overview',
    );
    const canViewPatient = hasPermission('patients.view');
    const canViewTriage = hasPermission('triage.view');
    const canViewConsultation = hasPermission('consultations.view');
    const canUpdateVisit = hasPermission('visits.update');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Active Visits', href: '/visits' },
        { title: visit.visit_number, href: `/visits/${visit.id}` },
    ];

    const timeline = [
        {
            label: 'Registered',
            value: formatDateTime(visit.registered_at ?? visit.created_at),
        },
        { label: 'In Progress', value: formatDateTime(visit.started_at) },
        { label: 'Completed', value: formatDateTime(visit.completed_at) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Visit ${visit.visit_number}`} />

            <div className="m-4 space-y-6">
                <VisitHeader
                    visit={visit}
                    canViewPatient={canViewPatient}
                    canViewTriage={canViewTriage}
                    canViewConsultation={canViewConsultation}
                    canPrintSummary={hasPermission('visits.view')}
                />

                <Tabs
                    value={selectedTab}
                    onValueChange={setSelectedTab}
                    className="space-y-4"
                >
                    <TabsList variant="line" className="w-full justify-start">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="clinical">Clinical</TabsTrigger>
                        <TabsTrigger value="orders">Visit Services</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-6">
                        <VisitOverviewTab
                            visit={visit}
                            timeline={timeline}
                            completionCheck={completionCheck}
                            canUpdateVisit={canUpdateVisit}
                        />
                        <AuditTimelineCard
                            title="Visit Audit Log"
                            entries={audit_activity}
                            emptyMessage="No audit activity recorded for this visit yet."
                        />
                    </TabsContent>

                    <TabsContent value="clinical" className="space-y-6">
                        <VisitClinicalTab
                            visit={visit}
                            triage={visit.triage}
                            consultation={visit.consultation}
                            triageGrades={triageGrades}
                            canViewTriage={canViewTriage}
                            canViewConsultation={canViewConsultation}
                        />
                    </TabsContent>

                    <TabsContent value="orders" className="space-y-6">
                        <VisitOrdersTab
                            visit={visit}
                            consultation={visit.consultation}
                            canManageOrders={hasPermission(
                                'consultations.update',
                            )}
                            labTestOptions={labTestOptions}
                            drugOptions={drugOptions}
                            labPriorities={labPriorities}
                            imagingModalities={imagingModalities}
                            imagingPriorities={imagingPriorities}
                            imagingLateralities={imagingLateralities}
                            pregnancyStatuses={pregnancyStatuses}
                            facilityServiceOptions={facilityServiceOptions}
                            allergens={allergens}
                            severityOptions={severityOptions}
                            reactionOptions={reactionOptions}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
