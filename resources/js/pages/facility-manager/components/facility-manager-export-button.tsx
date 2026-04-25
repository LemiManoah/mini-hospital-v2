import { Button } from '@/components/ui/button';

interface FacilityManagerExportButtonProps {
    href: string;
}

export function FacilityManagerExportButton({
    href,
}: FacilityManagerExportButtonProps) {
    return (
        <Button asChild variant="outline">
            <a href={href}>Export CSV</a>
        </Button>
    );
}
