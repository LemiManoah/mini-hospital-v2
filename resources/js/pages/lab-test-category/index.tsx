import { LookupIndex } from '@/components/laboratory/lookup-index';
import { type LabTestCategoryIndexPageProps } from '@/types/lab-reference';

export default function LabTestCategoryIndex({
    categories,
    filters,
}: LabTestCategoryIndexPageProps) {
    return (
        <LookupIndex
            title="Lab Test Categories"
            createLabel="+ Add Category"
            createHref="/lab-test-categories/create"
            baseHref="/lab-test-categories"
            editBaseHref="/lab-test-categories"
            deleteResourceName="Lab test category"
            breadcrumbs={[
                { title: 'Lab Test Categories', href: '/lab-test-categories' },
            ]}
            records={categories}
            filters={filters}
            createPermission="lab_test_categories.create"
            updatePermission="lab_test_categories.update"
            deletePermission="lab_test_categories.delete"
        />
    );
}
