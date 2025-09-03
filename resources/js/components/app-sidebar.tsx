import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { type NavItems, NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { Layers, /*LayoutGrid, */ MessagesSquare, Settings, BotMessageSquare, Users } from 'lucide-react';
import { OrgSwitcher } from '@/components/org-switcher';
import {PageProps} from '@/types';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage<PageProps>()
    const chatbot = props.chatbot as { id: number };

    const mainNavItems: NavItems[] = [
        {
            title: 'Agents',
            href: route('chatbots.index'),
            icon: BotMessageSquare,
        },{
            title: 'All Contacts',
            href: route('contacts.index'),
            icon: Users,
        },{
            title: 'Org. Settings',
            icon: Settings,
            href: '#',
            items: [
                {
                    title: 'General',
                    href: route('organizations.edit'),
                    isActive: false,
                },
                {
                    title: 'Members',
                    href: route('organizations.members.index'),
                    isActive: false,
                },
            ],
        },
    ];

    const chatbotNavItems: NavItems[] = [
        {
            title: 'Agents',
            href: route('chatbots.index'),
            icon: BotMessageSquare,
        },
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
        {
            title: 'Contacts',
            href: route('contacts.index'),
            icon: Users,
        },
    ];

    const isChatbotContext = chatbot?.id > 0;
    const navItems = isChatbotContext ? chatbotNavItems : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="relative">
                <OrgSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} groupLabel={isChatbotContext ? 'Agent' : 'General'} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
