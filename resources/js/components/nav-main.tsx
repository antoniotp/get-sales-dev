import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type NavItems } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronRight } from 'lucide-react';
import { useState, useEffect } from 'react';

const isUrlActive = (pageUrl: string, href: string): boolean => {
    if (!href || href === '#') {
        return false;
    }
    try {
        return pageUrl.startsWith(new URL(href).pathname);
    } catch {
        return false;
    }
};

const CollapsibleNavItem = ({ item }: { item: NavItems }) => {
    const page = usePage();
    const isGroupActive = item.items?.some((subItem) => isUrlActive(page.url, subItem.href)) ?? false;

    const [isOpen, setIsOpen] = useState(isGroupActive);

    useEffect(() => {
        setIsOpen(isGroupActive);
    }, [isGroupActive]);

    return (
        <Collapsible
            asChild
            className="group/collapsible"
            open={isOpen}
            onOpenChange={setIsOpen}
        >
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={item.title}>
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {item.items?.map((subItem) => (
                            <SidebarMenuSubItem key={subItem.title}>
                                <SidebarMenuSubButton asChild isActive={isUrlActive(page.url, subItem.href)}>
                                    <Link href={subItem.href}>
                                        <span>{subItem.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
};

export function NavMain({ items = [], groupLabel = '' }: { items: NavItems[], groupLabel?: string }) {
    const page = usePage();
    return (
        <SidebarGroup className="px-2 py-0">
            {groupLabel && <SidebarGroupLabel>{groupLabel}</SidebarGroupLabel>}
            <SidebarMenu>
                {items.map((item) => {
                    return item.items && item.items.length > 0 ? (
                        <CollapsibleNavItem key={item.title} item={item} />
                    ) : (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isUrlActive(page.url, item.href)}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
