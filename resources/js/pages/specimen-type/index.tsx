import { LookupIndex } from '@/components/laboratory/lookup-index';
import { type SpecimenTypeIndexPageProps } from '@/types/lab-reference';

export default function SpecimenTypeIndex({
    specimenTypes,
    filters,
}: SpecimenTypeIndexPageProps) {
    return (
        <LookupIndex
            title="Specimen Types"
            createLabel="+ Add Specimen Type"
            createHref="/specimen-types/create"
            baseHref="/specimen-types"
            editBaseHref="/specimen-types"
            deleteResourceName="Specimen type"
            breadcrumbs={[
                { title: 'Specimen Types', href: '/specimen-types' },
            ]}
            records={specimenTypes}
            filters={filters}
            createPermission="specimen_types.create"
            updatePermission="specimen_types.update"
            deletePermission="specimen_types.delete"
        />
    );
}
