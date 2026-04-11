import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

interface AdministrationSectionPageProps {
    title: string;
    description: string;
    items: Array<{
        title: string;
        description: string;
        href: string;
    }>;
}

export default function AdministrationSection({
    title,
    description,
    items,
}: AdministrationSectionPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administration', href: '/administration/general-settings' },
        { title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="flex flex-col gap-6 p-6">
                <div className="max-w-3xl">
                    <h1 className="text-2xl font-bold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>

                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {items.map((item) => (
                        <Card
                            key={item.title}
                            className="flex min-h-52 flex-col border-none shadow-sm ring-1 ring-border/50"
                        >
                            <CardHeader className="space-y-3">
                                <CardTitle className="text-lg">
                                    {item.title}
                                </CardTitle>
                                <CardDescription className="text-sm leading-6">
                                    {item.description}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="mt-auto pt-0">
                                <Button
                                    asChild
                                    className="w-full justify-between"
                                >
                                    <Link href={item.href}>
                                        Open {item.title}
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
