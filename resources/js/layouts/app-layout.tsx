import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem, PageProps } from '@/types';
import { type ReactNode, useEffect } from 'react';
import { toast, Toaster } from 'sonner';
import { usePage } from '@inertiajs/react';
import { ChatbotProvider } from '@/context/ChatbotProvider';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
    const {flash} = usePage<PageProps>().props
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success)
        }
        if (flash?.error) {
            toast.error(flash.error)
        }
        if (flash?.warning) {
            toast.warning(flash.warning)
        }
        if (flash?.info) {
            toast.info(flash.info)
        }
    }, [flash]);

    return (
        <ChatbotProvider>
            <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
                <Toaster position="top-center" richColors closeButton />
                {children}
            </AppLayoutTemplate>
        </ChatbotProvider>
    );
}
