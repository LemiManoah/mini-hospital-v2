import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CircleAlert, CreditCard, LoaderCircle } from 'lucide-react';

type SubscriptionCheckoutProps = {
    tenant: {
        id: string;
        name: string;
    };
    subscription: {
        id: string;
        status: string;
        status_label: string;
        starts_at: string | null;
        trial_ends_at: string | null;
        activated_at: string | null;
        current_period_starts_at: string | null;
        current_period_ends_at: string | null;
        checkout_provider: string | null;
        checkout_reference: string | null;
        checkout_url: string | null;
        package: {
            id: string;
            name: string;
            users: number;
            price: string;
        };
    };
};

function formatDate(value: string | null): string {
    if (!value) return 'Not set';

    return new Date(value).toLocaleDateString('en-UG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function SubscriptionCheckout({
    tenant,
    subscription,
}: SubscriptionCheckoutProps) {
    return (
        <AuthLayout
            title="Subscription checkout"
            description="This is the hosted activation handoff for the current subscription package."
            contentClassName="max-w-4xl"
        >
            <Head title="Subscription Checkout" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="space-y-4">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <CreditCard className="h-5 w-5" />
                                    {tenant.name}
                                </CardTitle>
                                <CardDescription>
                                    Complete the package handoff for{' '}
                                    {subscription.package.name}.
                                </CardDescription>
                            </div>
                            <Badge variant="secondary">
                                {subscription.status_label}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                        <div className="space-y-4 rounded-2xl border p-5">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Package
                                </p>
                                <p className="text-lg font-semibold">
                                    {subscription.package.name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {subscription.package.users} licensed users
                                </p>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-2xl bg-muted/40 p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Amount due
                                    </p>
                                    <p className="mt-1 text-lg font-semibold">
                                        {subscription.package.price}
                                    </p>
                                </div>
                                <div className="rounded-2xl bg-muted/40 p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Reference
                                    </p>
                                    <p className="mt-1 font-medium">
                                        {subscription.checkout_reference ??
                                            'Will be generated after handoff'}
                                    </p>
                                </div>
                                <div className="rounded-2xl bg-muted/40 p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Trial ends
                                    </p>
                                    <p className="mt-1 font-medium">
                                        {formatDate(subscription.trial_ends_at)}
                                    </p>
                                </div>
                                <div className="rounded-2xl bg-muted/40 p-4">
                                    <p className="text-sm text-muted-foreground">
                                        Provider
                                    </p>
                                    <p className="mt-1 font-medium">
                                        {subscription.checkout_provider ??
                                            'Manual placeholder'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-dashed p-5">
                            <div className="flex items-start gap-3">
                                <CircleAlert className="mt-0.5 h-5 w-5 text-amber-600" />
                                <div className="space-y-2 text-sm text-muted-foreground">
                                    <p className="font-medium text-foreground">
                                        Milestone 3 checkout placeholder
                                    </p>
                                    <p>
                                        This screen completes the SaaS billing
                                        lifecycle inside the app while the real
                                        payment gateway is still pending.
                                    </p>
                                    <p>
                                        Use the buttons below to simulate a
                                        successful payment callback or a failed
                                        checkout recovery path.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter className="flex flex-col gap-3 sm:flex-row">
                        <Form
                            method="post"
                            action="/subscription/checkout/success"
                            className="w-full"
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    ) : null}
                                    Simulate successful payment
                                </Button>
                            )}
                        </Form>
                        <Form
                            method="post"
                            action="/subscription/checkout/failure"
                            className="w-full"
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    variant="outline"
                                    className="w-full"
                                >
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    ) : null}
                                    Simulate payment failure
                                </Button>
                            )}
                        </Form>
                    </CardFooter>
                </Card>

                <div className="flex justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/subscription/activate">
                            <ArrowLeft className="h-4 w-4" />
                            Back to activation
                        </Link>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href="/modules">Back to modules</Link>
                    </Button>
                </div>
            </div>
        </AuthLayout>
    );
}
