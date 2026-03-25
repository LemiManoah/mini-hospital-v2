import { LookupForm } from '@/components/laboratory/lookup-form';
import { type SpecimenTypeEditPageProps } from '@/types/lab-reference';

export default function SpecimenTypeEdit({
    specimenType,
}: SpecimenTypeEditPageProps) {
    return (
        <LookupForm
            title={`Edit ${specimenType.name}`}
            heading="Edit Specimen Type"
            description={`Update specimen type details for ${specimenType.name}.`}
            breadcrumbs={[
                { title: 'Specimen Types', href: '/specimen-types' },
                {
                    title: 'Edit Specimen Type',
                    href: `/specimen-types/${specimenType.id}/edit`,
                },
            ]}
            action={`/specimen-types/${specimenType.id}`}
            method="put"
            backHref="/specimen-types"
            submitLabel="Save Changes"
            successMessage="Specimen type updated successfully."
            values={specimenType}
        />
    );
}
