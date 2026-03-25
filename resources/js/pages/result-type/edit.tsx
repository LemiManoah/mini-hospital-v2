import { LookupForm } from '@/components/laboratory/lookup-form';
import { type ResultTypeEditPageProps } from '@/types/lab-reference';

export default function ResultTypeEdit({
    resultType,
}: ResultTypeEditPageProps) {
    return (
        <LookupForm
            title={`Edit ${resultType.name}`}
            heading="Edit Result Type"
            description={`Update result type details for ${resultType.name}.`}
            breadcrumbs={[
                { title: 'Result Types', href: '/result-types' },
                {
                    title: 'Edit Result Type',
                    href: `/result-types/${resultType.id}/edit`,
                },
            ]}
            action={`/result-types/${resultType.id}`}
            method="put"
            backHref="/result-types"
            submitLabel="Save Changes"
            successMessage="Result type updated successfully."
            values={resultType}
            codeLabel="Code"
        />
    );
}
