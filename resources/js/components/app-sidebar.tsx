import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Layers, /*LayoutGrid, */MessagesSquare, Settings } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Chatbots',
        href: route('chatbots.index'),
        icon: MessagesSquare,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage();
    const chatbot = props.chatbot as { id: number };

    const chatbotNavItems: NavItem[] = [
        {
            title: 'Chats',
            href: route('chats', { chatbot: chatbot?.id || 0 }),
            icon: MessagesSquare,
        },
        {
            title: 'Template Messages',
            href: route('message-templates.index', { chatbot: chatbot?.id || 0 }),
            icon: Layers,
        },
        {
            title: 'Integrations',
            href: route('chatbots.integrations', { chatbot: chatbot?.id || 0 }),
            icon: Settings,
        },
    ];

    const isChatbotContext = chatbot?.id > 0;
    const navItems = isChatbotContext ? chatbotNavItems : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('dashboard')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
