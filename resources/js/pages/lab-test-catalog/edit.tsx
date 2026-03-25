import LabTestCatalogForm from '@/components/laboratory/lab-test-catalog-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type LabTestCatalogEditPageProps } from '@/types/lab-test-catalog';
import { Head } from '@inertiajs/react';

export default function LabTestCatalogEdit({
    labTestCatalog,
    ...props
}: LabTestCatalogEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Lab Tests', href: '/lab-test-catalogs' },
        {
            title: 'Edit Lab Test',
            href: `/lab-test-catalogs/${labTestCatalog.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Lab Test: ${labTestCatalog.test_name}`} />
            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold">Edit Lab Test</h1>
                    <p className="text-sm text-muted-foreground">
                        Update the supported specimen types and result setup for{' '}
                        {labTestCatalog.test_name}.
                    </p>
                </div>

                <LabTestCatalogForm
                    {...props}
                    labTestCatalog={labTestCatalog}
                    action={`/lab-test-catalogs/${labTestCatalog.id}`}
                    method="put"
                    submitLabel="Save Changes"
                    successMessage="Lab test updated successfully."
                    cancelHref="/lab-test-catalogs"
                />
            </div>
        </AppLayout>
    );
}
