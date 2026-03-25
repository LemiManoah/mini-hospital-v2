import { LookupForm } from '@/components/laboratory/lookup-form';

export default function LabTestCategoryCreate() {
    return (
        <LookupForm
            title="Create Lab Test Category"
            heading="Create Lab Test Category"
            description="Add a tenant-specific lab test grouping for your catalog."
            breadcrumbs={[
                { title: 'Lab Test Categories', href: '/lab-test-categories' },
                {
                    title: 'Create Lab Test Category',
                    href: '/lab-test-categories/create',
                },
            ]}
            action="/lab-test-categories"
            method="post"
            backHref="/lab-test-categories"
            submitLabel="Create Lab Test Category"
            successMessage="Lab test category created successfully."
        />
    );
}
