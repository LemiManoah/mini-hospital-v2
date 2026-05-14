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
import { VisitTimelineTable } from './components/visit-timeline-table';

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
    imagingStudyOptions,
    facilityServiceOptions,
    completionCheck,
    triageGrades,
    allergens,
    severityOptions,
    reactionOptions,
}: VisitShowPageProps) {
    const { hasPermission } = usePermissions();
    const allowedTabs = ['overview', 'timeline', 'orders'];
    const initialTab = activeTab === 'clinical' ? 'overview' : activeTab;
    const [selectedTab, setSelectedTab] = useState(
        allowedTabs.includes(initialTab) ? initialTab : 'overview',
    );
    const canViewPatient = hasPermission('patients.view');
    const canViewTriage = hasPermission('triage.view');
    const canViewConsultation = hasPermission('consultations.view');
    const canUpdateVisit = hasPermission('visits.update');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Active Visits', href: '/visits' },
        { title: visit.visit_number, href: `/visits/${visit.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Visit ${visit.visit_number}`} />

            <div className="m-4 flex flex-col gap-6">
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
                    className="flex flex-col gap-4"
                >
                    <TabsList variant="line" className="w-full justify-start">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="orders">Visit Services</TabsTrigger>
                        <TabsTrigger value="timeline">
                            Visit Timeline
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent
                        value="overview"
                        className="flex flex-col gap-6"
                    >
                        <VisitOverviewTab
                            visit={visit}
                            completionCheck={completionCheck}
                            canUpdateVisit={canUpdateVisit}
                        />
                        <VisitClinicalTab
                            visit={visit}
                            triage={visit.triage}
                            consultation={visit.consultation}
                            triageGrades={triageGrades}
                            canViewTriage={canViewTriage}
                            canViewConsultation={canViewConsultation}
                        />
                    </TabsContent>

                    <TabsContent
                        value="timeline"
                        className="flex flex-col gap-6"
                    >
                        <VisitTimelineTable
                            entries={audit_activity}
                            emptyMessage="No audit activity recorded for this visit yet."
                        />
                    </TabsContent>

                    <TabsContent value="orders" className="flex flex-col gap-6">
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
                            imagingStudyOptions={imagingStudyOptions}
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
