import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

export default function AuthLayout({
    children,
    title,
    description,
    contentClassName,
    ...props
}: {
    children: React.ReactNode;
    title: string;
    description: string;
    contentClassName?: string;
}) {
    return (
        <AuthLayoutTemplate
            title={title}
            description={description}
            contentClassName={contentClassName}
            {...props}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
