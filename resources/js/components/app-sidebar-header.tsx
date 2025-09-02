import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from './app-logo-icon';

interface Organization {
    id: number;
    name: string;
}

interface Organizations {
    list: Organization[];
    current: Organization;
}

interface PageProps {
    organization: Organizations;
    [key: string]: unknown;
}

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {

    const { props } = usePage<PageProps>();
    const { organization } = props;
    breadcrumbs = [{title:organization.current.name,href:''}, ...breadcrumbs]

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Link href={route('dashboard')} prefetch>
                    <div className="hidden md:flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground bg-white border border-grey-100 dark:[border-color:var(--sidebar-foreground)]">
                        <AppLogoIcon className="fill-current text-white dark:text-black" />
                    </div>
                </Link>
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}
