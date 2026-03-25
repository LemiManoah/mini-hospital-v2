import { LookupForm } from '@/components/laboratory/lookup-form';
import { type LabTestCategoryEditPageProps } from '@/types/lab-reference';

export default function LabTestCategoryEdit({
    category,
}: LabTestCategoryEditPageProps) {
    return (
        <LookupForm
            title={`Edit ${category.name}`}
            heading="Edit Lab Test Category"
            description={`Update lab test category details for ${category.name}.`}
            breadcrumbs={[
                { title: 'Lab Test Categories', href: '/lab-test-categories' },
                {
                    title: 'Edit Lab Test Category',
                    href: `/lab-test-categories/${category.id}/edit`,
                },
            ]}
            action={`/lab-test-categories/${category.id}`}
            method="put"
            backHref="/lab-test-categories"
            submitLabel="Save Changes"
            successMessage="Lab test category updated successfully."
            values={category}
        />
    );
}
