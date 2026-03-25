import { LookupForm } from '@/components/laboratory/lookup-form';

export default function SpecimenTypeCreate() {
    return (
        <LookupForm
            title="Create Specimen Type"
            heading="Create Specimen Type"
            description="Add a tenant-specific specimen option for the lab workflow."
            breadcrumbs={[
                { title: 'Specimen Types', href: '/specimen-types' },
                {
                    title: 'Create Specimen Type',
                    href: '/specimen-types/create',
                },
            ]}
            action="/specimen-types"
            method="post"
            backHref="/specimen-types"
            submitLabel="Create Specimen Type"
            successMessage="Specimen type created successfully."
        />
    );
}
