import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface GeneralSettingsPageProps {
    categories: Array<{
        title: string;
        description: string;
        examples: string[];
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/administration/general-settings' },
    { title: 'General Settings', href: '/administration/general-settings' },
];

export default function GeneralSettings({
    categories,
}: GeneralSettingsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="General Settings" />

            <div className="flex flex-col gap-6 p-6">
                <div className="max-w-3xl space-y-3">
                    <Badge variant="outline">Administration</Badge>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            General Settings
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            This is the new home for facility-wide operational
                            rules. It will centralize policy decisions like
                            payment-before-service, currency defaults, lab
                            release rules, and pharmacy batch-tracking behavior.
                        </p>
                    </div>
                </div>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Implementation Direction</CardTitle>
                        <CardDescription>
                            The menu structure is now in place first, so the
                            real settings workflow can be added under a clean
                            administrative home instead of the old mixed
                            dropdown.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        The next implementation step is wiring stored settings,
                        validation, branch overrides where needed, and audit
                        history for important rule changes.
                    </CardContent>
                </Card>

                <div className="grid gap-5 md:grid-cols-2">
                    {categories.map((category) => (
                        <Card
                            key={category.title}
                            className="border-none shadow-sm ring-1 ring-border/50"
                        >
                            <CardHeader>
                                <CardTitle className="text-lg">
                                    {category.title}
                                </CardTitle>
                                <CardDescription className="text-sm leading-6">
                                    {category.description}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2 text-sm text-muted-foreground">
                                    {category.examples.map((example) => (
                                        <li key={example}>{example}</li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
