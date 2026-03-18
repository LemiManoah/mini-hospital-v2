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
import { ArrowRight, CreditCard, LoaderCircle } from 'lucide-react';

type SubscriptionActivateProps = {
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

export default function SubscriptionActivate({
    tenant,
    subscription,
}: SubscriptionActivateProps) {
    const isActive = subscription.status === 'active';
    const isPendingCheckout = subscription.status === 'pending_activation';

    return (
        <AuthLayout
            title="Subscription activation"
            description="Review the current package and move the tenant into the checkout handoff."
            contentClassName="max-w-3xl"
        >
            <Head title="Subscription Activation" />

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CreditCard className="h-5 w-5" />
                            {tenant.name}
                        </CardTitle>
                        <CardDescription>
                            Package: {subscription.package.name} for{' '}
                            {subscription.package.users} users
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="rounded-2xl border p-4">
                            <p className="text-sm text-muted-foreground">
                                Subscription status
                            </p>
                            <p className="mt-1 text-lg font-semibold">
                                {subscription.status_label}
                            </p>
                        </div>
                        <div className="rounded-2xl border p-4">
                            <p className="text-sm text-muted-foreground">
                                Package price
                            </p>
                            <p className="mt-1 text-lg font-semibold">
                                {subscription.package.price}
                            </p>
                        </div>
                        <div className="rounded-2xl border p-4">
                            <p className="text-sm text-muted-foreground">
                                Trial ends
                            </p>
                            <p className="mt-1 font-medium">
                                {formatDate(subscription.trial_ends_at)}
                            </p>
                        </div>
                        <div className="rounded-2xl border p-4">
                            <p className="text-sm text-muted-foreground">
                                Checkout reference
                            </p>
                            <p className="mt-1 font-medium">
                                {subscription.checkout_reference ??
                                    'Not requested yet'}
                            </p>
                        </div>
                    </CardContent>
                    <CardFooter className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Subscription activation now moves through a hosted
                            checkout step before final success or failure is
                            recorded.
                        </p>
                        {isActive ? (
                            <Button asChild className="w-full sm:w-auto">
                                <Link href="/modules">Return to modules</Link>
                            </Button>
                        ) : isPendingCheckout ? (
                            <Button asChild className="w-full sm:w-auto">
                                <Link
                                    href={
                                        subscription.checkout_url ??
                                        '/subscription/checkout'
                                    }
                                >
                                    Continue to checkout
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </Button>
                        ) : (
                            <Form
                                method="post"
                                action="/subscription/activate"
                                className="w-full sm:w-auto"
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full sm:w-auto"
                                    >
                                        {processing ? (
                                            <LoaderCircle className="h-4 w-4 animate-spin" />
                                        ) : null}
                                        Request activation handoff
                                    </Button>
                                )}
                            </Form>
                        )}
                    </CardFooter>
                </Card>

                <div className="flex justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/modules">Back to modules</Link>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href="/onboarding">Back to onboarding</Link>
                    </Button>
                </div>
            </div>
        </AuthLayout>
    );
}
