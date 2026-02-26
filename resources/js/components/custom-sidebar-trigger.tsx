import React from 'react';
import { useSidebar } from '@/components/ui/sidebar';
import { Menu, PanelLeft } from "lucide-react"
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

export function CustomSidebarTrigger({
                                   className,
                                   onClick,
                                   ...props
                               }: React.ComponentProps<typeof Button>) {
    const { toggleSidebar } = useSidebar()
    const { t } = useTranslation('common');

    return (
        <Button
            data-sidebar="trigger"
            data-slot="sidebar-trigger"
            variant="ghost"
            size="icon"
            className={cn("h-7 w-7", className)}
            onClick={(event) => {
                onClick?.(event)
                toggleSidebar()
            }}
            {...props}
        >
            {/* Mobile: burger menu */}
            <Menu className="md:hidden size-6" />

            {/* Desktop: panel icon */}
            <PanelLeft className="hidden md:block" />
            <span className="sr-only">
                {t('sidebar.toggle')}
            </span>
        </Button>
    )
}
