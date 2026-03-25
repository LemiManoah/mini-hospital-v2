import { LookupIndex } from '@/components/laboratory/lookup-index';
import { type ResultTypeIndexPageProps } from '@/types/lab-reference';

export default function ResultTypeIndex({
    resultTypes,
    filters,
}: ResultTypeIndexPageProps) {
    return (
        <LookupIndex
            title="Result Types"
            createLabel="+ Add Result Type"
            createHref="/result-types/create"
            baseHref="/result-types"
            editBaseHref="/result-types"
            deleteResourceName="Result type"
            breadcrumbs={[{ title: 'Result Types', href: '/result-types' }]}
            records={resultTypes}
            filters={filters}
            createPermission="result_types.create"
            updatePermission="result_types.update"
            deletePermission="result_types.delete"
            codeColumnLabel="Code"
        />
    );
}
