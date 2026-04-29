import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type ConsultationTariffFormPageProps } from '@/types/consultation-tariff';
import { Head, Link } from '@inertiajs/react';
import { ConsultationTariffForm } from './components/consultation-tariff-form';

export default function ConsultationTariffCreate({
    visitTypeOptions,
    consultationTypeOptions,
    facilityServiceOptions,
}: ConsultationTariffFormPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Consultation Tariffs', href: '/consultation-tariffs' },
        { title: 'Create', href: '/consultation-tariffs/create' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Consultation Tariff" />

            <div className="m-4 space-y-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">
                        Create Consultation Tariff
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Define how a consultation type and visit-type scope map
                        to a billable consultation tariff in the active branch.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Tariff Mapping</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <ConsultationTariffForm
                            action="/consultation-tariffs"
                            method="post"
                            visitTypeOptions={visitTypeOptions}
                            consultationTypeOptions={consultationTypeOptions}
                            facilityServiceOptions={facilityServiceOptions}
                            submitLabel="Create Consultation Tariff"
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
