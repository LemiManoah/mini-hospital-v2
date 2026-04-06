import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type ManagementSection = {
    title: string;
    description: string;
    href: string | null;
    permission: string | null;
    status: 'live' | 'coming_soon';
};

export default function LaboratoryManagement({
    sections,
}: {
    sections: ManagementSection[];
}) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/dashboard' },
        { title: 'Lab Management', href: '/laboratory/management' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lab Management" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold">Lab Management</h1>
                    <p className="text-sm text-muted-foreground">
                        Manage the laboratory setup behind services, specimen
                        collection, and result workflows from one place. Stock
                        management is reserved for the next phase.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {sections.map((section) => {
                        const allowed =
                            section.permission === null ||
                            hasPermission(section.permission);

                        return (
                            <Card key={section.title}>
                                <CardHeader className="gap-3">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex flex-col gap-1">
                                            <CardTitle>
                                                {section.title}
                                            </CardTitle>
                                            <CardDescription>
                                                {section.description}
                                            </CardDescription>
                                        </div>
                                        <Badge
                                            variant={
                                                section.status === 'live'
                                                    ? 'outline'
                                                    : 'secondary'
                                            }
                                        >
                                            {section.status === 'live'
                                                ? 'Live'
                                                : 'Coming Soon'}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {section.href && allowed ? (
                                        <Button asChild>
                                            <Link href={section.href}>
                                                Open {section.title}
                                            </Link>
                                        </Button>
                                    ) : section.href ? (
                                        <Button disabled>
                                            Permission Needed
                                        </Button>
                                    ) : (
                                        <Button disabled>Coming Soon</Button>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
