import LabTestCatalogForm from '@/components/laboratory/lab-test-catalog-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type LabTestCatalogFormPageProps } from '@/types/lab-test-catalog';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Lab Tests', href: '/lab-test-catalogs' },
    { title: 'Create Lab Test', href: '/lab-test-catalogs/create' },
];

export default function LabTestCatalogCreate(
    props: LabTestCatalogFormPageProps,
) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Lab Test" />
            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold">Create Lab Test</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a test to the laboratory catalog with all supported
                        specimen types and its result-entry structure.
                    </p>
                </div>

                <LabTestCatalogForm
                    {...props}
                    action="/lab-test-catalogs"
                    method="post"
                    submitLabel="Create Lab Test"
                    successMessage="Lab test created successfully."
                    cancelHref="/lab-test-catalogs"
                />
            </div>
        </AppLayout>
    );
}
