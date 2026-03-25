import { LookupForm } from '@/components/laboratory/lookup-form';

export default function ResultTypeCreate() {
    return (
        <LookupForm
            title="Create Result Type"
            heading="Create Result Type"
            description="Add a tenant-specific result capture definition for the lab module."
            breadcrumbs={[
                { title: 'Result Types', href: '/result-types' },
                {
                    title: 'Create Result Type',
                    href: '/result-types/create',
                },
            ]}
            action="/result-types"
            method="post"
            backHref="/result-types"
            submitLabel="Create Result Type"
            successMessage="Result type created successfully."
            codeLabel="Code"
        />
    );
}
