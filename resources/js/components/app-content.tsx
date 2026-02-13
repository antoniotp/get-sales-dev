import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';
import { cn } from '@/lib/utils';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
    customMainClassName?: string;
}

export function AppContent({ variant = 'header', children, className, customMainClassName, ...props }: AppContentProps) {
    if (variant === 'sidebar') {
        console.log('customMainClassName', customMainClassName)
        return <SidebarInset className={cn(className, customMainClassName)} {...props}>{children}</SidebarInset>;
    }

    return (
        <main className={cn('mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl', className, customMainClassName)} {...props}>
            {children}
        </main>
    );
}
