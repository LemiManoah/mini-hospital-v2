import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type ConsultationTariffEditPageProps } from '@/types/consultation-tariff';
import { Head, Link } from '@inertiajs/react';
import { ConsultationTariffForm } from './components/consultation-tariff-form';

export default function ConsultationTariffEdit({
    consultationTariff,
    visitTypeOptions,
    consultationTypeOptions,
    facilityServiceOptions,
}: ConsultationTariffEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Consultation Tariffs', href: '/consultation-tariffs' },
        {
            title: 'Edit',
            href: `/consultation-tariffs/${consultationTariff.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Consultation Tariff" />

            <div className="m-4 space-y-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">
                        Edit Consultation Tariff
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Update the consultation billing mapping for the active
                        branch.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Tariff Mapping</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <ConsultationTariffForm
                            action={`/consultation-tariffs/${consultationTariff.id}`}
                            method="put"
                            visitTypeOptions={visitTypeOptions}
                            consultationTypeOptions={consultationTypeOptions}
                            facilityServiceOptions={facilityServiceOptions}
                            initialValues={{
                                visit_type:
                                    consultationTariff.visit_type ?? 'all',
                                consultation_type:
                                    consultationTariff.consultation_type,
                                facility_service_id:
                                    consultationTariff.facility_service_id,
                                is_active: consultationTariff.is_active,
                            }}
                            submitLabel="Save Consultation Tariff"
                        />
                        <Link
                            href="/consultation-tariffs"
                            className="inline-block text-sm text-muted-foreground underline"
                        >
                            Back to consultation tariffs
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
