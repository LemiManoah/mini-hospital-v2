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

type StockManagementSection = {
    title: string;
    description: string;
    href: string;
    permission: string | null;
};

export default function LaboratoryStockManagement({
    sections,
}: {
    sections: StockManagementSection[];
}) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/dashboard' },
        { title: 'Lab Stock Management', href: '/laboratory/stock-management' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lab Stock Management" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold">
                        Lab Stock Management
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Open the laboratory stock workflows from one place
                        without crowding the main Laboratory navigation.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {sections.map((section) => {
                        const allowed =
                            section.permission === null ||
                            hasPermission(section.permission);

                        return (
                            <Card key={section.title}>
                                <CardHeader className="gap-3">
                                    <div className="flex flex-col gap-1">
                                        <CardTitle>{section.title}</CardTitle>
                                        <CardDescription>
                                            {section.description}
                                        </CardDescription>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {allowed ? (
                                        <Button asChild>
                                            <Link href={section.href}>
                                                Open {section.title}
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button disabled>
                                            Permission Needed
                                        </Button>
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
